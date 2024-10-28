<?php
namespace Mewz\WCAS\Aspects\Admin\Attributes;

use Mewz\Framework\Base\Aspect;
use Mewz\Framework\Util\Number;
use Mewz\QueryBuilder\DB;
use Mewz\WCAS\Models\AttributeStock;
use Mewz\WCAS\Util\Attributes;
use Mewz\WCAS\Util\Matches;

class AttributeList extends Aspect
{
	public $enqueue = '@admin/attributes';

	public function __scripts()
	{
		if (!$this->stock_exists()) return;

		$attributes = Attributes::get_attributes();
		if (!$attributes) return;

		$column_data = [];

		foreach ($attributes as $attribute_id => $_) {
			if ($output = $this->get_stock_column_output($attribute_id)) {
				$column_data[$attribute_id] = $output;
			}
		}

		$this->scripts->export_data('attributesTable', [
			'columnHeader' => __('Stock', 'woocommerce'),
			'columnData' => $column_data,
		]);
	}

	public function stock_exists()
	{
		return DB::table('posts', 'p')
			->left_join('postmeta', 'pm_al')->on("pm_al.post_id = p.ID")
			->where('p.post_type', AttributeStock::POST_TYPE)
			->where('p.post_status', 'publish')
			->where('pm_al.meta_key', 'attribute_level')
			->where('pm_al.meta_value > 0')
			->exists();
	}

	public function get_stock_column_output($attribute_id)
	{
		$stock_ids = Matches::query_stock($attribute_id, null, 'view', 'id');
		$stock_count = count($stock_ids);
		$output = '';

		if ($stock_count === 1) {
			$stock = AttributeStock::instance($stock_ids[0]);
			$edit_url = $stock->edit_url(['_wp_http_referer' => $_SERVER['REQUEST_URI']]);

			$output = '<a href="' . esc_url($edit_url) . '" class="mewz-wcas-stock-link" title="' . esc_attr__('Edit stock', 'woocommerce-attribute-stock') . '">' . Number::local_format($stock->quantity()) . '</a>';
		}
		elseif ($stock_count > 1) {
			$qty_range = Matches::get_attribute_display_range($stock_ids, $attribute_id);
			$url = AttributeStock::admin_url(['attribute_level' => $attribute_id]);

			$output = '<a href="' . esc_url($url) . '" class="mewz-wcas-stock-link mewz-wcas-stock-range" title="' . esc_attr__('List stock items', 'woocommerce-attribute-stock') . '">' . $qty_range . '</a>';
		}

		return $output;
	}
}
