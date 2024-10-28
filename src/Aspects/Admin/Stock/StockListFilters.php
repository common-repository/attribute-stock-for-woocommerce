<?php
namespace Mewz\WCAS\Aspects\Admin\Stock;

use Mewz\Framework\Base\Aspect;
use Mewz\Framework\Util\Admin;
use Mewz\QueryBuilder\DB;
use Mewz\WCAS\Models\AttributeStock;
use Mewz\WCAS\Util\Attributes;
use Mewz\WCAS\Util\Components;

class StockListFilters extends Aspect
{
	public function __hooks()
	{
		add_filter('views_edit-' . AttributeStock::POST_TYPE, [$this, 'view_filters']);
		add_action('restrict_manage_posts', [$this, 'list_filters']);
	}

	public function __scripts()
	{
		$this->scripts->enqueue_js('wc-enhanced-select');
		$this->scripts->enqueue_css('woocommerce_admin_styles');
	}

	public function view_filters($views)
	{
		unset($views['mine'], $views['private'], $views['future']);

		$replace = [
			'publish' => __('Enabled', 'woocommerce'),
			'draft' => __('Disabled', 'woocommerce'),
		];

		foreach ($replace as $key => $label) {
			if (isset($views[$key])) {
				$views[$key] = preg_replace('/<a(.+?)>.+?<span/s', '<a$1>' . $label . ' <span', $views[$key]);
			}
		}

		return $views;
	}

	public function list_filters($post_type)
	{
		global $wp_query;

	    if ($post_type !== AttributeStock::POST_TYPE) {
		    return;
	    }

	    ob_clean(); // removes all default post list filters

		if (!$wp_query->posts) {
			return;
		}

		$this->list_filter_stock();
		$this->list_filter_product();
		$this->list_filter_category();
		$this->list_filter_component();
		$this->list_filter_attribute();
		$this->list_filter_term();
		$this->list_filter_tag();
	}

	public function placeholder($text)
	{
	    return sprintf(_x('# %s', 'filter placeholder', 'woocommerce-attribute-stock'), $text);
	}

	public function list_filter_stock()
	{
		$this->view->render('admin/stock/list-filter', [
			'name' => 'stock',
			'placeholder' => $this->placeholder(__('Stock', 'woocommerce')),
			'options' => [
				'in-stock' => __('In stock', 'woocommerce'),
				'low-stock' => __('Low in stock', 'woocommerce'),
				'out-of-stock' => __('Out of stock', 'woocommerce'),
			],
		]);
	}

	public function list_filter_product()
	{
		$this->view->render('admin/stock/list-filter-product', [
			'name' => 'product_id',
			'placeholder' => $this->placeholder(__('Product', 'woocommerce')),
		]);
	}

	public function list_filter_category()
	{
		$post_status = !empty($_REQUEST['post_status']) && $_REQUEST['post_status'] !== 'all' ? $_REQUEST['post_status'] : ['publish', 'draft'];

		$results = DB::table('postmeta', 'pm')
			->left_join('posts', 'p')->on('p.ID = pm.post_id')
			->where('p.post_type', AttributeStock::POST_TYPE)
			->where('p.post_status', $post_status)
			->where('pm.meta_key', '_categories')
			->where_not('pm.meta_value', ['', 'a:0:{}'])
			->col('pm.meta_value');

		if (!$results) return;

		$category_ids = [];

		foreach ($results as $result) {
			$ids = unserialize($result);

			if ($ids && is_array($ids)) {
				foreach ($ids as $id) {
					$category_ids[$id] = true;
				}
			}
		}

		$options = Admin::get_category_options();

		if ($category_ids) {
			$options = array_intersect_key($options, $category_ids);
		}

		$this->view->render('admin/stock/list-filter', [
			'name' => 'category',
			'placeholder' => $this->placeholder(__('Category', 'woocommerce')),
			'options' => $options,
		]);
	}

	public function list_filter_component()
	{
		global $wpdb;

		$components_table = DB::prefix(Components::TABLE);
		$post_type = AttributeStock::POST_TYPE;

		$results = $wpdb->get_results("
			SELECT
				c.parent_id,
				c.child_id,
				pp.post_title parent_title,
				pc.post_title child_title,
				ppm.meta_value parent_sku,
				pcm.meta_value child_sku
			
			FROM {$components_table} c
				
			LEFT JOIN {$wpdb->posts} pp ON pp.ID = c.parent_id
			LEFT JOIN {$wpdb->posts} pc ON pc.ID = c.child_id
				
			LEFT JOIN {$wpdb->postmeta} ppm ON ppm.post_id = pp.ID AND ppm.meta_key = '_sku'
			LEFT JOIN {$wpdb->postmeta} pcm ON pcm.post_id = pc.ID AND pcm.meta_key = '_sku'
			
			WHERE pp.post_type = '{$post_type}' AND pc.post_type = '{$post_type}'
			  AND pp.post_status IN ('publish', 'draft') AND pc.post_status IN ('publish', 'draft')
			
			ORDER BY pp.post_title
		");

		if (!$results) return;

		$options = [
			'parent' => [
				'label' => __('Parent components', 'woocommerce-attribute-stock'),
				'options' => [],
			],
			'child' => [
				'label' => __('Child components', 'woocommerce-attribute-stock'),
				'options' => [],
			],
		];

		foreach ($results as $row) {
			if (!isset($options['parent']['options'][$row->parent_id])) {
				$label = $row->parent_title . ($row->parent_sku ? ' [' . $row->parent_sku . ']' : '');
				$options['parent']['options']['parent:' . $row->parent_id] = $label;
			}

			if (!isset($options['child']['options'][$row->child_id])) {
				$label = $row->child_title . ($row->child_sku ? ' [' . $row->child_sku . ']' : '');
				$options['child']['options']['child:' . $row->child_id] = $label;
			}
		}

		asort($options['child']['options'], SORT_STRING);

		$this->view->render('admin/stock/list-filter', [
			'name' => 'component',
			'placeholder' => $this->placeholder(__('Component', 'woocommerce-attribute-stock')),
			'options' => $options,
			'grouped' => true,
		]);
	}

	public function list_filter_tag()
	{
		global $wpdb;

		$tags = $wpdb->get_results("
			SELECT DISTINCT t.name, t.slug
			FROM {$wpdb->terms} t
			LEFT JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id
			LEFT JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
			LEFT JOIN {$wpdb->posts} p ON p.ID = tr.object_id
			WHERE tt.taxonomy = 'product_tag'
			  AND p.post_type = '" . AttributeStock::POST_TYPE . "'
			  AND p.post_status != 'auto-draft'
			ORDER BY t.name
		");

		if (!$tags) return;

		$options = [];

		foreach ($tags as $tag) {
			$options[$tag->slug] = $tag->name;
		}

		$this->view->render('admin/stock/list-filter', [
			'name' => 'product_tag',
			'placeholder' => $this->placeholder(__('Tag', 'woocommerce')),
			'options' => $options,
		]);
	}

	public function list_filter_attribute()
	{
		$attr_options = Attributes::get_used_attribute_options($_REQUEST['post_status'] ?? null);
		if (!$attr_options) return;

		$options = [];

		foreach ($attr_options as $attribute_id => $attribute) {
			$options[$attribute_id] = $attribute['label'];
		}

		$this->view->render('admin/stock/list-filter', [
			'name' => 'attribute',
			'placeholder' => $this->placeholder(__('Attribute', 'woocommerce')),
			'options' => $options,
		]);

		$this->scripts->export_data('attributeOptions', $attr_options);
	}

	public function list_filter_term()
	{
		$attr_options = Attributes::get_used_attribute_options($_REQUEST['post_status'] ?? null);
		if (!$attr_options) return;

		$attr_id = isset($_REQUEST['attribute']) && $_REQUEST['attribute'] !== '' ? (int)$_REQUEST['attribute'] : null;
		$options = [];

		if ($attr_id !== null && isset($attr_options[$attr_id])) {
			foreach ($attr_options[$attr_id]['terms'] as $term) {
				$options[$term[0]] = $term[1];
			}
		}

		$this->view->render('admin/stock/list-filter', [
			'name' => 'term',
			'placeholder' => $this->placeholder(__('Terms', 'woocommerce')),
			'options' => $options,
			'hidden' => !$options,
		]);
	}
}
