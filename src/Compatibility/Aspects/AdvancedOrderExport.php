<?php
namespace Mewz\WCAS\Compatibility\Aspects;

use Mewz\Framework\Base\Aspect;
use Mewz\WCAS\Util\Products;

class AdvancedOrderExport extends Aspect
{
	public function __hooks()
	{
		add_filter('mewz_wcas_limit_product_stock_auto_hooks', [$this, 'limit_product_stock_auto_hooks']);
		add_filter('woe_fetch_order_product', [$this, 'woe_fetch_order_product'], 0, 5);
	}

	public function limit_product_stock_auto_hooks($hooks)
	{
		$hooks[] = 'woe_fetch_order_product';
		return $hooks;
	}

	public function woe_fetch_order_product($row, $order, $item, $product, $item_meta) {
		if ($product instanceof \WC_Product) {
			// order item products already bypass limits by default to avoid stock calculation issues,
			// so we need to un-bypass limits here to get the correct values
			$bypass_limits = Products::get_prop($product, 'bypass_limits');
			Products::set_prop($product, 'bypass_limits', 0);

			$row['stock_quantity'] = $product->get_stock_quantity();
			$row['stock_status'] = $product->get_stock_status();

			Products::set_prop($product, 'bypass_limits', $bypass_limits);
		}

		return $row;
	}
}
