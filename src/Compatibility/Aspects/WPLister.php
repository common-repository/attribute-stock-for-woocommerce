<?php
namespace Mewz\WCAS\Compatibility\Aspects;

use Mewz\Framework\Base\Aspect;
use Mewz\WCAS\Util;

class WPLister extends Aspect
{
	public function __hooks()
	{
		// enable product limits when viewing or syncing product stock
		add_filter('mewz_wcas_limit_product_stock_auto', [$this, 'limit_product_stock_auto']);
		add_filter('mewz_wcas_limit_product_stock_auto_hooks', [$this, 'limit_product_stock_auto_hooks']);

		// account for attribute stock when calculating total variable product stock
		add_filter('get_post_metadata', [$this, 'filter_product_stock_meta'], 0, 4);

		// sync attribute stock when WP-Lister imports an order
		add_action('wplister_after_create_order', [$this, 'after_create_order']);
		add_action('wple_after_create_order', [$this, 'after_create_order']);
		add_action('wpla_after_create_order', [$this, 'after_create_order']);
	}

	public function limit_product_stock_auto($limit)
	{
		return (
			$limit
			|| ($this->context->ajax && isset($_REQUEST['action']) && strpos($_REQUEST['action'], 'wpl') === 0)
			|| strpos($this->context->plugin_page, 'wpl') === 0
		);
	}

	public function limit_product_stock_auto_hooks($hooks)
	{
		$hooks[] = 'get_post_metadata';

		return $hooks;
	}

	public function filter_product_stock_meta($value, $object_id, $meta_key, $single)
	{
		if ($meta_key === '_stock' && $single) {
			remove_filter('get_post_metadata', [$this, 'filter_product_stock_meta'], 0);

			do_action('mewz_wcas_auto_product_limits_enable');

			$product = wc_get_product($object_id);

			if ($product instanceof \WC_Product_Variation) {
				$value = $product->get_stock_quantity();
			}

			do_action('mewz_wcas_auto_product_limits_disable');

			add_filter('get_post_metadata', [$this, 'filter_product_stock_meta'], 0, 4);
		}

	    return $value;
	}

	public function after_create_order($order_id)
	{
		Util\Orders::update_order_attribute_stock($order_id, 'sync');
	}
}
