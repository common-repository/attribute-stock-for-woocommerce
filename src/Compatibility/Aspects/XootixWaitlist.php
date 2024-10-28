<?php
namespace Mewz\WCAS\Compatibility\Aspects;

use Mewz\Framework\Base\Aspect;

class XootixWaitlist extends Aspect
{
	public function __hooks()
	{
		add_action('mewz_wcas_trigger_product_stock_change', [$this, 'trigger_product_stock_change']);
		add_filter('mewz_wcas_limit_product_stock_auto_hooks', [$this, 'limit_product_stock_auto_hooks']);
	}

	public function trigger_product_stock_change($product)
	{
		do_action('updated_postmeta', 0, $product->get_id(), '_stock_status', $product->get_stock_status());
	}

	public function limit_product_stock_auto_hooks($hooks)
	{
		$hooks[] = 'updated_postmeta';
		$hooks[] = 'wp_ajax_xoo_wl_table_send_email';
		$hooks[] = ['load-wc-waitlist_page_xoo-wl-view-waitlist'];

		return $hooks;
	}
}
