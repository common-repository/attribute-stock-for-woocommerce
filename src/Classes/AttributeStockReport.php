<?php
namespace Mewz\WCAS\Classes;

use Mewz\QueryBuilder\DB;
use Mewz\WCAS\Models\AttributeStock;

require_once WC_ABSPATH . 'includes/admin/reports/class-wc-report-stock.php';

class AttributeStockReport extends \WC_Report_Stock
{
	public $type;

	public function get_columns()
	{
		return apply_filters('mewz_wcas_stock_report_columns', [
			'title' => __('Title'),
			'sku' => __('SKU', 'woocommerce'),
			'quantity' => __('Stock', 'woocommerce'),
			'wc_actions' => __('Actions', 'woocommerce'),
		], $this);
	}

	public function get_items($current_page, $per_page)
	{
		$query = DB::table('posts', 'p')
			->select('p.ID id, p.post_title title, pm_sku.meta_value sku, 0+pm_quantity.meta_value quantity')
			->left_join('postmeta', 'pm_sku')->on("pm_sku.post_id = p.ID AND pm_sku.meta_key = '_sku'")
			->left_join('postmeta', 'pm_quantity')->on("pm_quantity.post_id = p.ID AND pm_quantity.meta_key = '_quantity'")
			->where('p.post_type', AttributeStock::POST_TYPE)
			->where('p.post_status', 'publish')
			->asc('p.post_title');

		if (in_array($this->type, ['low_in_stock', 'most_stocked'])) {
			$low_stock_default = max((float)get_option('woocommerce_notify_low_stock_amount'), 1);
			$low_stock_expr = "IF(CHAR_LENGTH(pm_low_stock.meta_value), pm_low_stock.meta_value, $low_stock_default)";

			$query->left_join('postmeta', 'pm_low_stock')->on("pm_low_stock.post_id = p.ID AND pm_low_stock.meta_key = '_low_stock'");

			if ($this->type === 'low_in_stock') {
				$out_of_stock = max((float)get_option('woocommerce_notify_no_stock_amount'), 0);

				$query->where('0+pm_quantity.meta_value <= ' . $low_stock_expr);
				$query->where('0+pm_quantity.meta_value', '>', $out_of_stock);
			} else {
				$query->where('0+pm_quantity.meta_value > ' . $low_stock_expr);
			}
		} elseif ($this->type === 'out_of_stock') {
			$out_of_stock = max((float)get_option('woocommerce_notify_no_stock_amount'), 0);

			$query->where('0+pm_quantity.meta_value', '<=', $out_of_stock);
		}

		$query = apply_filters('mewz_wcas_stock_report_query', $query, $this, $current_page, $per_page);

		$this->max_items = $query->count();
		$this->items = $query->page($current_page, $per_page);
	}

	public function column_default($item, $column_name)
	{
		$output = '';

		switch ($column_name) {
			case 'title':
				$output = esc_html($item['title']);
				break;

			case 'sku':
				$output = esc_html($item['sku']);
				break;

			case 'quantity':
				$output = $item['quantity'];
				break;

			case 'wc_actions':
				$stock = new AttributeStock($item['id'], 'object');
				$referer = ['_wp_http_referer' => $_SERVER['REQUEST_URI']];

				$output = '<a class="button tips edit" href="' . esc_url($stock->edit_url($referer)) . '" data-tip="' . esc_attr(__('Edit attribute stock', 'woocommerce-attribute-stock')) . '">' . esc_html__('Edit', 'woocommerce') . '</a>';
				break;
		}

		echo apply_filters('mewz_wcas_stock_report_column_output', $output, $column_name, $item, $this);
	}

	public function output_report($type = null)
	{
		if ($type === null) {
			throw new \InvalidArgumentException('Report type must be specified.');
		}

		$this->type = $type;
		$this->prepare_items();

		echo '<div id="poststuff" class="woocommerce-reports-wide mewz-wcas-stock-report ' . esc_attr($this->type) . '">';
		$this->display();
		echo '</div>';
	}

	public function no_items()
	{
		$text = '';

		switch ($this->type) {
			case 'low_in_stock':
				$text = esc_html__('No low in stock attributes found.', 'woocommerce-attribute-stock');
				break;

			case 'out_of_stock':
				$text = esc_html__('No out of stock attributes found.', 'woocommerce-attribute-stock');
				break;

			case 'most_stocked':
				$text = esc_html__('No most stocked attributes found.', 'woocommerce-attribute-stock');
				break;
		}

		echo apply_filters('mewz_wcas_stock_no_items_text', $text, $this);
	}
}
