<?php
namespace Mewz\WCAS\Util;

use Mewz\Framework\Util\WooCommerce;

class Settings
{
	public static $cache = [];

	/**
	 * @param string $key
	 * @param mixed $default
	 * @param bool $use_cache
	 *
	 * @return mixed
	 */
	public static function get($key, $default = null, $use_cache = false)
	{
		if ($use_cache) {
			if (!isset(self::$cache[$key])) {
				self::$cache[$key] = get_option('mewz_wcas_' . $key, $default);
			}

			return self::$cache[$key];
		} else {
			return get_option('mewz_wcas_' . $key, $default);
		}
	}

	public static function modify_product_stock($use_cache = false)
	{
	    return self::get('modify_product_stock', 'auto', $use_cache);
	}

	public static function allow_backorders($use_cache = false)
	{
	    return self::get('allow_backorders', 'no', $use_cache);
	}

	public static function product_stock_format($use_cache = false)
	{
		return self::get('product_stock_format', '', $use_cache);
	}

	public static function outofstock_variations($use_cache = false)
	{
	    return self::get('outofstock_variations', 'outofstock', $use_cache);
	}

	public static function unmatched_any_variations($use_cache = false)
	{
	    return self::get('unmatched_any_variations', 'no', $use_cache);
	}

	public static function sync_product_visibility($use_cache = false)
	{
		return self::get('sync_product_visibility', 'auto', $use_cache);
	}

	public static function trigger_product_stock_actions($use_cache = false)
	{
	    return self::get('trigger_product_stock_actions', 'yes', $use_cache);
	}

	public static function ajax_variation_threshold($use_cache = false)
	{
		return self::get('ajax_variation_threshold', '', $use_cache);
	}

	public static function components_max_depth($use_cache = false)
	{
		$key = 'components_max_depth';

		if ($use_cache && isset(self::$cache[$key])) {
			return self::$cache[$key];
		}

		$value = self::get($key, '');
		$value = $value === '' ? 5 : min(max(0, (int)$value), 28); // cap to not exceed max MySQL joins

		if ($use_cache) {
			self::$cache[$key] = $value;
		}

		return $value;
	}

	// AGGREGATED

	public static function sync_product_visibility_bool($use_cache = false, $value = null)
	{
		$value ??= self::sync_product_visibility($use_cache);

		if ($value === 'auto') {
			if ($use_cache) {
				$cache_key = 'woocommerce_hide_out_of_stock';

				if (!isset(self::$cache[$cache_key])) {
					self::$cache[$cache_key] = WooCommerce::hide_out_of_stock();
				}

				return self::$cache[$cache_key];
			} else {
				return WooCommerce::hide_out_of_stock();
			}
		}

		return $value === 'yes';
	}
}
