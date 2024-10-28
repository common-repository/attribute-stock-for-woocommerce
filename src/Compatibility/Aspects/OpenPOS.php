<?php
namespace Mewz\WCAS\Compatibility\Aspects;

use Mewz\Framework\Base\Aspect;

class OpenPOS extends Aspect
{
	public function __hooks()
	{
		add_action('op_add_order_final_after', [$this, 'op_add_order_final_after'], 15);
	}

	public function op_add_order_final_after($data)
	{
		do_action('woocommerce_reduce_order_stock', wc_get_order($data['order_id']));
	}
}
