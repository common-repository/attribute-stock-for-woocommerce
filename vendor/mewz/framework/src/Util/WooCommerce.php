<?php
namespace Mewz\Framework\Util;

class WooCommerce
{
	public static function get_cache_incr($group)
	{
		return wp_cache_get('wc_' . $group . '_cache_prefix', $group) ?: 0;
	}

	public static function hide_out_of_stock()
	{
	    return wc_string_to_bool(get_option('woocommerce_hide_out_of_stock_items'));
	}

	public static function no_stock_amount()
	{
	    static $value;
	    return $value ??= (float)get_option('woocommerce_notify_no_stock_amount', 0);
	}

	public static function low_stock_amount()
	{
	    static $value;
	    return $value ??= (float)get_option('woocommerce_notify_low_stock_amount', 2);
	}

	public static function stock_format()
	{
	    static $value;
	    return $value ??= get_option('woocommerce_stock_format');
	}
}
