<?php
namespace Mewz\WCAS\Aspects\Admin\Attributes;

use Mewz\Framework\Base\Aspect;
use Mewz\Framework\Util\Number;
use Mewz\QueryBuilder\DB;
use Mewz\WCAS\Models\AttributeStock;
use Mewz\WCAS\Util\Attributes;
use Mewz\WCAS\Util\Matches;

class AttributeTermList extends Aspect
{
	public $enqueue = '@admin/attributes';
	public $show_columns;

	public function __hooks()
	{
		$taxonomy = $this->context->taxonomy;

		add_action('admin_head', [$this, 'admin_head']);

		add_filter("manage_edit-{$taxonomy}_columns", [$this, 'columns']);
		add_filter("manage_{$taxonomy}_custom_column", [$this, 'column_output'], 10, 3);
	}

	public function admin_head()
	{
		$this->show_columns = $this->show_columns();

		if (!$this->show_columns) {
			remove_filter("manage_{$this->context->taxonomy}_custom_column", [$this, 'column_output']);
		}

		add_filter('admin_body_class', [$this, 'admin_body_class']);
	}

	public function show_columns()
	{
		global $wp_list_table;

		if ($wp_list_table && empty($wp_list_table->items)) {
			return [];
		}

		$attribute_id = Attributes::get_attribute_id($this->context->taxonomy);

		if (!$attribute_id) {
			return [];
		}

		$show_columns['stock'] = Matches::query('left_join')
			->left_join('posts', 'p')->on('p.ID = r.stock_id')
			->where('p.post_type', AttributeStock::POST_TYPE)
			->where('p.post_status', 'publish')
			->where('a.attribute_id', $attribute_id)
			->where('a.term_id', '>', 0)
			->clear('distinct')
			->exists();

		$show_columns['multiplier'] = DB::table('term_taxonomy', 'tt')
			->left_join('termmeta', 'tm')->on('tm.term_id = tt.term_id')
			->where('tt.taxonomy', $this->context->taxonomy)
			->where('tm.meta_key', 'mewz_wcas_multiplier')
			->where_not('tm.meta_value', '')
			->exists();

		return array_filter($show_columns);
	}

	public function admin_body_class($class)
	{
		if (empty($this->show_columns['stock'])) {
			$class .= ' mewz-wcas-hide-stock-column';
		}

		if (empty($this->show_columns['multiplier'])) {
			$class .= ' mewz-wcas-hide-multiplier-column';
		}

	    return $class;
	}

	public function columns($columns)
	{
		$add_columns = [
			'mewz_wcas_stock' => __('Stock', 'woocommerce'),
			'mewz_wcas_multiplier' => __('Multiplier', 'woocommerce-attribute-stock'),
		];

		// insert before "Count" column
		$pos = array_search('posts', array_keys($columns));

		if ($pos !== false) {
			$columns = array_slice($columns, 0, $pos, true) + $add_columns + array_slice($columns, $pos, null, true);
		} else {
			$columns += $add_columns;
		}

	    return $columns;
	}

	public function column_output($output, $column_name, $term_id)
	{
		switch ($column_name) {
			case 'mewz_wcas_stock':
				$output = $this->output_stock_column($term_id);
				break;

			case 'mewz_wcas_multiplier':
				$output = $this->output_multiplier_column($term_id);
				break;
		}

		return $output;
	}

	public function output_stock_column($term_id)
	{
		if (is_array($this->show_columns) && empty($this->show_columns['stock'])) {
			return '';
		}

		$attribute_id = Attributes::get_attribute_id($this->context->taxonomy);

		$stock_ids = Matches::query_stock($attribute_id, $term_id, 'view', 'id');
		$stock_count = count($stock_ids);

		if ($stock_count === 1) {
			$stock = AttributeStock::instance($stock_ids[0]);

			$edit_url = $stock->edit_url(['_wp_http_referer' => $_SERVER['REQUEST_URI']]);
			$title = in_array($attribute_id, $stock->meta('attribute_level', false)) ? __('Edit attribute-level stock', 'woocommerce-attribute-stock') : __('Edit stock', 'woocommerce-attribute-stock');
			$quantity = Matches::get_term_display_quantity($stock, $attribute_id, $term_id);

			return '<a href="' . esc_url($edit_url) . '" class="mewz-wcas-stock-link" title="' . esc_attr($title) . '">' . $quantity . '</a>';
		}

		if ($stock_count > 1) {
			$qty_range = Matches::get_attribute_display_range($stock_ids, $attribute_id, $term_id);
			$url = AttributeStock::admin_url(['attribute' => $attribute_id, 'term' => $term_id]);

			return '<a href="' . esc_url($url) . '" class="mewz-wcas-stock-link mewz-wcas-stock-range" title="' . esc_attr__('List stock items', 'woocommerce-attribute-stock') . '">' . $qty_range . '</a>';
		}

		return '';
	}

	public function output_multiplier_column($term_id)
	{
		if (is_array($this->show_columns) && empty($this->show_columns['multiplier'])) {
			return '';
		}

		$multiplier = get_term_meta($term_id, 'mewz_wcas_multiplier', true);
		if ($multiplier === '') return '';

		return '<span class="mewz-wcas-term-multiplier"><span class="times">&times;</span>' . Number::local_format($multiplier) . '</span>';
	}
}
