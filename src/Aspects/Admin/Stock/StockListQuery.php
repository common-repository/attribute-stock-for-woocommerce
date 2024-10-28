<?php
namespace Mewz\WCAS\Aspects\Admin\Stock;

use Mewz\Framework\Base\Aspect;
use Mewz\QueryBuilder\DB;
use Mewz\WCAS\Models\AttributeStock;
use Mewz\WCAS\Util\Components;
use Mewz\WCAS\Util\Matches;
use Mewz\WCAS\Util\Products;

class StockListQuery extends Aspect
{
	public function __hooks()
	{
		add_action('pre_get_posts', [$this, 'pre_get_posts'], 0);
		add_filter('posts_clauses', [$this, 'posts_clauses'], 0, 2);
	}

	public function pre_get_posts(\WP_Query $query)
	{
		if (!$query->is_main_query() || $query->get('post_type') !== AttributeStock::POST_TYPE) {
			return;
		}

		// remove any stray meta queries added before this point
		// (we need our meta queries to be first to allow sorting by meta_value)
		$query->set('meta_query', []);

		$this->query_orderby($query);
		$this->query_filter_stock($query);
		$this->query_filter_category($query);
		$this->query_filter_product($query);
		$this->query_filter_component($query);
		$this->query_filter_attribute($query);
	}

	public function posts_clauses($clauses, \WP_Query $query)
	{
		global $wpdb;

		if ($query->get('post_type') !== AttributeStock::POST_TYPE) {
			return $clauses;
		}

		if (strlen($query->get('s'))) {
			$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS mewz_wcas_sku ON (mewz_wcas_sku.post_id = {$wpdb->posts}.ID AND mewz_wcas_sku.meta_key = '_sku')";

			if (empty($clauses['groupby'])) {
				$clauses['groupby'] = "{$wpdb->posts}.ID";
			}

			$clauses['where'] = preg_replace(
				"/\({$wpdb->posts}.post_content LIKE ('[^']+')\)/",
				"$0 OR (mewz_wcas_sku.meta_value LIKE $1)",
				$clauses['where']
			);
		}

		return $clauses;
	}

	public function query_orderby(\WP_Query $query)
	{
		$orderby = $query->get('orderby');
		$_orderby = [];

		if (!$orderby) {
			$orderby = [];
		} elseif (!is_array($orderby)) {
			$orderby = [(string)$orderby => $query->get('order') ?: 'asc'];
		}

		foreach ($orderby as $key => $order) {
			$prop = AttributeStock::get_post_prop($key);

			if (!$prop) {
				$meta_key = '_' . $key;

				$meta_query = [
					'relation' => 'OR',
					['key' => $meta_key, 'compare' => 'NOT EXISTS'],
					['key' => $meta_key],
				];

				$this->add_meta_query($query, $meta_query);

				$prop = $key === 'quantity' ? 'meta_value_num' : 'meta_value';
			}

			if (!isset($_orderby[$prop])) {
				$_orderby[$prop] = $order;
			}
		}

		if (!isset($_orderby['post_title'])) {
			$_orderby['post_title'] = 'asc';
		}

		if (!isset($_orderby['post_date'])) {
			$_orderby['post_date'] = 'desc';
		}

		$query->set('orderby', $_orderby);

		// allow sorting by post_content
		add_filter('posts_orderby', [$this, 'query_orderby_clause'], 10, 2);
	}

	public function query_orderby_clause($clause, \WP_Query $query)
	{
		global $wpdb;

		if (!$query->is_main_query() || $query->get('post_type') !== AttributeStock::POST_TYPE) {
			return $clause;
		}

		$orderby = $query->get('orderby');

		if (!$orderby || !is_array($orderby)) {
			return $clause;
		}

		// if the first (main) orderby field is post_content, add it to the orderby query
		reset($orderby);
		$key = key($orderby);

		if ($key === 'post_content') {
			$clause = "{$wpdb->posts}.{$key} {$orderby[$key]}, {$clause}";
		}

		return $clause;
	}

	public function query_filter_stock(\WP_Query $query)
	{
		if (empty($_REQUEST['stock'])) {
			return;
		}

		switch ($_REQUEST['stock']) {
			case 'in-stock':
				$this->add_meta_query($query, [
					'key' => '_quantity',
					'value' => (float)get_option('woocommerce_notify_no_stock_amount', 0),
					'compare' => '>',
				]);
				break;

			case 'out-of-stock':
				$this->add_meta_query($query, [
					'key' => '_quantity',
					'value' => (float)get_option('woocommerce_notify_no_stock_amount', 0),
					'compare' => '<=',
				]);
				break;

			case 'low-stock':
				$no_stock = (float)get_option('woocommerce_notify_no_stock_amount', 0);
				$low_stock = (float)get_option('woocommerce_notify_low_stock_amount', 2);

				$stock_ids = DB::table('posts', 'p')
					->left_join('postmeta', 'qty')->on("qty.post_id = p.ID AND qty.meta_key = '_quantity'")
					->left_join('postmeta', 'lowstock')->on("lowstock.post_id = p.ID AND lowstock.meta_key = '_low_stock'")
					->where('p.post_type', AttributeStock::POST_TYPE)
					->where('0+qty.meta_value', '>', $no_stock)
					->where('0+qty.meta_value <= 0+COALESCE(lowstock.meta_value, ?)', $low_stock)
					->distinct()
					->col('p.ID');

				$this->set_query_post_ids($query, $stock_ids);
				break;
		}
	}

	public function query_filter_category(\WP_Query $query)
	{
		if (empty($_REQUEST['category'])) {
			return;
		}

		$cat_id = (int)$_REQUEST['category'];
		$post_status = !empty($_REQUEST['post_status']) && $_REQUEST['post_status'] !== 'all' ? $_REQUEST['post_status'] : ['publish', 'draft'];

		$stock_ids = DB::table('posts', 'p')
			->left_join('postmeta', 'pm')->on('pm.post_id = p.ID')
			->where('p.post_type', AttributeStock::POST_TYPE)
			->where('p.post_status', $post_status)
			->where('pm.meta_key', '_categories')
			->like('pm.meta_value', "a:%i:?;%", $cat_id)
			->col('p.ID');

		if ($stock_ids) {
			$this->set_query_post_ids($query, $stock_ids);
		}
	}

	public function query_filter_product(\WP_Query $query)
	{
		if (empty($_REQUEST['product_id'])) {
			return;
		}

		$product_id = (int)$_REQUEST['product_id'];
		$product = wc_get_product($product_id);
		if (!$product) return;

		$attributes = Products::get_product_attributes($product);
		$matches = Matches::match_product_stock($product, $attributes, 'edit');

		$stock_ids = $matches ? array_keys($matches) : [];

		$this->set_query_post_ids($query, $stock_ids);
	}

	public function query_filter_component(\WP_Query $query)
	{
		if (empty($_REQUEST['component'])) {
			return;
		}

		[$type, $id] = explode(':', $_REQUEST['component'], 2);

		if ($id <= 0 || !in_array($type, ['parent', 'child'])) {
			return;
		}

		$list_type = $type === 'parent' ? 'child' : 'parent';

		$stock_ids = DB::table('posts', 'p')
			->left_join(Components::TABLE, 'c')->on("c.{$list_type}_id = p.ID")
			->where('p.post_type', AttributeStock::POST_TYPE)
			->where("c.{$type}_id", (int)$id)
			->col('p.ID');

		$this->set_query_post_ids($query, $stock_ids);
	}

	public function query_filter_attribute(\WP_Query $query)
	{
		if (!empty($_REQUEST['attribute'])) {
			$filter_term = isset($_REQUEST['term']) && $_REQUEST['term'] !== '';

			$db_query = Matches::query()->where('a.attribute_id', (int)$_REQUEST['attribute']);

			if ($filter_term) {
				$db_query->where('a.term_id', (int)$_REQUEST['term']);
			}

			$stock_ids = $db_query->col('r.stock_id');

			if (!$filter_term) {
				$attr_level_ids = DB::table('posts', 'p')
					->left_join('postmeta', 'pm')->on('pm.post_id = p.ID')
					->where('p.post_type', AttributeStock::POST_TYPE)
					->where('pm.meta_key', 'attribute_level')
					->where('pm.meta_value', (int)$_REQUEST['attribute'])
					->distinct()
					->col('p.ID');

				if ($attr_level_ids) {
					$stock_ids = array_merge($stock_ids, $attr_level_ids);
				}
			}

			$this->set_query_post_ids($query, $stock_ids);
		}
		elseif (!empty($_REQUEST['attribute_level'])) {
			$this->add_meta_query($query, [
				'key' => 'attribute_level',
				'value' => (int)$_REQUEST['attribute_level'],
			]);
		}
	}

	public function add_meta_query(\WP_Query $query, array $meta_query)
	{
	    $meta_queries = $query->get('meta_query') ?: [];
		$meta_queries[] = $meta_query;

		$query->set('meta_query', $meta_queries);
	}

	public function set_query_post_ids(\WP_Query $query, $post_ids)
	{
		if ($query->get('p') === -1) return;

		$post_ids = $post_ids ? array_keys(array_flip((array)$post_ids)) : [];
		$current = $query->get('post__in');

		if ($current) {
			$current = (array)$current;

			if (is_string(current($current))) {
				$current = array_keys(array_flip($current));
			}

			$post_ids = $post_ids && $current ? array_intersect($current, $post_ids) : [];
	    }

		if ($post_ids) {
			$query->set('post__in', $post_ids);
		} else {
			unset($query->query_vars['post__in']);
			$query->set('p', -1);
		}
	}
}
