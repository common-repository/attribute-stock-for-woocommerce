<?php
namespace Mewz\WCAS\Compatibility\Aspects;

use Mewz\Framework\Base\Aspect;
use Mewz\WCAS\Util\Orders;

class OrderStatusActions extends Aspect
{
	public function __hooks()
	{
		// the plugin "WooCommerce Order Status & Actions Manager" doesn't use the correct function
		// to restore order stock, so we need to explicitly restore attribute stock ourselves
		add_action('woocommerce_order_status_changed', [$this, 'order_status_changed'], 800, 3);
	}

	public function order_status_changed($order_id, $old_status, $new_status)
	{
		$status = wc_sa_get_status_by_name($new_status);

		if ($status && $status->stock_status === 'restore') {
			Orders::update_order_attribute_stock($order_id, 'restore', null, 'cancel');
		}
	}
}
