<?php
namespace Mewz\WCAS\Compatibility\Classes;

use Mewz\WCAS\Aspects\Front\ProductLimits;
use Mewz\WCAS\Util\Components;
use Mewz\WCAS\Util\Limits;
use Mewz\WCAS\Util\Matches;
use Mewz\WCAS\Util\Products;

class WCCartStockReducer extends \WC_Cart_Stock_Reducer
{
	/** @var WCCartStockReducerSessions */
	public $sessions;

	public $virtual_depth = 0;
	public $cart_stock_reducer;

	public $skip_attribute_stock_override = false;
	public $skip_managing_stock_override = false;

	public $override_virtual_stock;

	public function woocommerce_init()
	{
		$reflection = new \ReflectionClass(parent::class);
		$dir = dirname($reflection->getFileName());

		require_once $dir . '/class-wc-csr-session.php';
		require_once $dir . '/class-wc-csr-sessions.php';

		$this->sessions = new WCCartStockReducerSessions($this);

		// set private properties
		$sessions_prop = $reflection->getProperty('sessions');
		$sessions_prop->setAccessible(true);
		$sessions_prop->setValue($this, $this->sessions);

		if ($this->cart_stock_reducer === 'yes') {
			add_action('woocommerce_add_to_cart', [$this, 'clear_items_cache'], 100);
			add_action('woocommerce_cart_item_removed', [$this, 'clear_items_cache'], 100);

			add_filter('mewz_wcas_product_stock_limits_uncached', [$this, 'product_attribute_stock_limits'], 0, 3);
			add_filter('mewz_wcas_any_match_data_matches', [$this, 'any_product_match_data'], 0);

			add_action('mewz_wcas_before_validate_add_to_cart', [$this, 'disable_attribute_stock_override'], 0);
			add_action('mewz_wcas_after_validate_add_to_cart', [$this, 'enable_attribute_stock_override'], 0);

			add_action('woocommerce_check_cart_items', [$this, 'disable_attribute_stock_override'], -1);
			add_action('woocommerce_check_cart_items', [$this, 'enable_attribute_stock_override'], 999999);

			add_filter('mewz_wcas_cart_validation_items', [$this, 'attribute_stock_cart_validation_items'], 0);
		}
	}

	public function clear_items_cache()
	{
		$this->sessions->items = null;
	}

	public function disable_attribute_stock_override()
	{
		$this->skip_attribute_stock_override = true;
	}

	public function enable_attribute_stock_override()
	{
		$this->skip_attribute_stock_override = false;
	}

	public function product_attribute_stock_limits($limits, $product, $variation)
	{
		if (!$limits || $this->skip_attribute_stock_override) {
			return $limits;
		}

		$limit_qty = Limits::calc_limit_quantity($limits);

		$never_virtual_whitelist = [
			'wc_reduce_stock_levels',
			'render_product_columns',
			'validate_props',
			'render_is_in_stock_column',
		];

		if ($this->trace_contains(apply_filters('wc_csr_whitelist_get_stock_quantity', $never_virtual_whitelist, $limit_qty, $product))) {
			return $limits;
		}

		$this->skip_attribute_stock_override = true;

		$ignore = is_cart() || is_checkout() || $this->trace_contains(['has_enough_stock']);
		$cart_quantities = $this->sessions->get_cart_attribute_stock_quantities($ignore);

		$this->skip_attribute_stock_override = false;

		if ($cart_quantities) {
			foreach ($limits as $stock_id => &$limit) {
				if (!isset($cart_quantities[$stock_id])) {
					continue;
				}

				$limit['stock_qty'] -= $cart_quantities[$stock_id];
				$limit['limit_qty'] = Matches::calc_limit_qty($limit['stock_qty'], $limit['multiplier']);
			}

			if (!Components::is_sorted_tree($limits)) {
				Limits::sort_by_qty($limits);
			}
		}

		return $limits;
	}

	public function any_product_match_data($match_data)
	{
		if (!$match_data) return $match_data;

		$this->skip_attribute_stock_override = true;

		$cart_quantities = $this->sessions->get_cart_attribute_stock_quantities();

		$this->skip_attribute_stock_override = false;

		foreach ($match_data as $stock_id => &$match) {
			if (isset($cart_quantities[$stock_id])) {
				$match['q'] -= $cart_quantities[$stock_id];
			}
		}

	    return $match_data;
	}

	public function attribute_stock_cart_validation_items($user_cart_items)
	{
		$all_cart_items = $this->sessions->get_all_cart_items();

		foreach ($all_cart_items as $key => &$item) {
			if (!empty($item['data'])) continue;

			if (!empty($user_cart_items[$key]['data'])) {
				$item['data'] = $user_cart_items[$key]['data'];
			} else {
				if (!empty($item['variation_id'])) {
					$item['data'] = wc_get_product($item['variation_id']);
				}

				if (empty($item['data'])) {
					$item['data'] = wc_get_product($item['product_id']);
				}
			}
		}

		return $all_cart_items;
	}

	/**
	 * Determine which item is in control of managing the inventory
	 *
	 * @param \WC_Product $product
	 * @param int $product_id
	 * @param int $variation_id
	 *
	 * @return int|false
	 */
	public function get_item_managing_stock($product = null, $product_id = null, $variation_id = null)
	{
		if ($this->skip_managing_stock_override) {
			return parent::get_item_managing_stock($product, $product_id, $variation_id);
		}

		ProductLimits::$enabled = false;

		$id = parent::get_item_managing_stock($product, $product_id, $variation_id);

		ProductLimits::$enabled = true;

		return $id;
	}

	/**
	 * Get the quantity available of a specific item
	 *
	 * @param \WC_Product $product WooCommerce WC_Product based class, if not passed the item ID will be used to query
	 * @param bool $ignore Cart Item Key to ignore in the count
	 * @param bool $use_cache true if we should use cached data, false will force DB query
	 *
	 * @return int|null Quantity of items in stock
	 */
	public function get_virtual_stock_available($product = null, $ignore = false, $use_cache = true)
	{
		if ($this->override_virtual_stock !== null) {
			return $this->override_virtual_stock;
		}

		if (Products::get_prop($product, 'ignore_csr')) {
			return null;
		}

		ProductLimits::$enabled = false;

		$virtual_stock = parent::get_virtual_stock_available($product, $ignore, $use_cache);

		ProductLimits::$enabled = true;

		return $virtual_stock;
	}

	/**
	 * Called by 'woocommerce_add_cart_item' filter to add expiration time to cart items
	 *
	 * @param int $item Item ID
	 * @param string $key Unique Cart Item ID
	 *
	 * @return mixed
	 */
	public function add_cart_item($item, $key)
	{
		$this->skip_managing_stock_override = true;

		$item = parent::add_cart_item($item, $key);

		$this->skip_managing_stock_override = false;

		return $item;
	}

	public function get_availability_text($text, $product)
	{
		$this->override_virtual_stock = $this->get_attribute_stock_virtual_product_stock_quantity($product);

		$text = parent::get_availability_text($text, $product);

		$this->override_virtual_stock = null;

		return $text;
	}

	public function get_availability_class($class, $product)
	{
		$this->override_virtual_stock = $this->get_attribute_stock_virtual_product_stock_quantity($product);

		$class = parent::get_availability_class($class, $product);

		$this->override_virtual_stock = null;

		return $class;
	}

	public function replace_stock_pending_text($pending_text, $info = null, $product = null)
	{
		if (!$product) {
			return $pending_text;
		}

		$this->skip_managing_stock_override = true;

		$pending_text = parent::replace_stock_pending_text($pending_text, $info, $product);

		$this->skip_managing_stock_override = false;

		return $pending_text;
	}

	public function get_attribute_stock_virtual_product_stock_quantity(\WC_Product $product)
	{
		$virtual_stock = $product->get_stock_quantity();

		$this->skip_attribute_stock_override = true;

		$real_stock = $product->get_stock_quantity();

		$this->skip_attribute_stock_override = false;

		if ($virtual_stock == $real_stock) {
			return null;
		}

		return $virtual_stock;
	}

	/**
	 * Called from 'woocommerce_quantity_input_args' filter to adjust the maximum quantity of items a user can select
	 *
	 * @param array $args
	 * @param \WC_Product $product WC_Product type object
	 *
	 * @return array
	 */
	public function quantity_input_args($args, $product)
	{
		return $args;
	}

	/**
	 * @param array $var
	 * @param \WC_Product_Variable $product
	 * @param \WC_Product_Variation $variation
	 *
	 * @return array
	 */
	public function product_available_variation($var, $product, $variation)
	{
		return $var;
	}

	public function is_expired($expire_time = 'never', $order_awaiting_payment = null)
	{
		static $now;

		if (!$expire_time || $expire_time === 'never' ) {
			return false;
		}

		$now ??= time();

		if ($expire_time > $now) {
			return false;
		}

		if ($order_awaiting_payment === null || empty($this->ignore_status)) {
			return true;
		}

		$order = wc_get_order($order_awaiting_payment);
		if (!$order) return true;

		$order_status = 'wc-' . $order->get_status();

		if (in_array($order_status, apply_filters('wc_csr_expire_ignore_status', $this->ignore_status, $order_status, $expire_time, $order_awaiting_payment))) {
			return false;
		}

		return true;
	}
}
