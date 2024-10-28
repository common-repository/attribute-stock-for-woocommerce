<?php
namespace Mewz\WCAS\Compatibility\Classes;

use Mewz\WCAS\Util\Limits;

class WCCartStockReducerSessions extends \WC_CSR_Sessions
{
	/** @var WCCartStockReducer */
	public $csr;

	public $current_customer_id = 0;
	public $sessions = [];
	public $items;

	/**
	 * @param WCCartStockReducer $csr
	 *
	 * @throws \ReflectionException
	 */
	public function __construct($csr = null) {
		parent::__construct($csr);
		$this->csr = $csr;

		// set private properties
		$reflection = new \ReflectionClass(parent::class);

		foreach (['sessions', 'current_customer_id'] as $prop) {
			$reflection_prop = $reflection->getProperty($prop);
			$reflection_prop->setAccessible(true);
			$this->{$prop} = $reflection_prop->getValue($this);
		}
	}

	public function get_all_cart_items($ignore = false)
	{
		if ($this->items === null) {
			$sessions = $this->get_all_items_in_carts();
			$this->items = [];

			foreach ($sessions as $session_id => $session) {
				if ($ignore && $session_id == $this->current_customer_id) {
					continue;
				}

				$cart = $session->cart;
				if (!$cart) continue;

				foreach ($cart as $item) {
					if (!empty($item['csr_expire_time']) && $this->csr->is_expired($item['csr_expire_time'], $session->get('order_awaiting_payment', null))) {
						continue;
					}

					$key = $item['key'];

					if (!isset($this->items[$key])) {
						$item['session_id'] = $session_id;
						$this->items[$key] = $item;
					} else {
						$this->items[$key]['quantity'] += $item['quantity'];
					}
				}
			}
		}

		return $this->items;
	}

	public function get_cart_attribute_stock_quantities($ignore = false)
	{
		$quantities = [];

		foreach ($this->get_all_cart_items($ignore) as $item) {
			$limits = $this->get_cart_item_attribute_stock_limits($item);
			if (!$limits) continue;

			foreach ($limits as $stock_id => $limit) {
				$quantity = $item['quantity'] * $limit['multiplier'];

				if (isset($quantities[$stock_id])) {
					$quantities[$stock_id]['amount'] += $quantity;
				} else {
					$quantities[$stock_id]['amount'] = $quantity;

					if (!empty($item['csr_expire_time']) && !empty($item['session_id'])) {
						$quantities[$stock_id]['expires'] = $item['csr_expire_time'];
						$quantities[$stock_id]['session_id'] = $item['session_id'];
					}
				}
			}
		}

		$valid_quantities = [];

		foreach ($quantities as $stock_id => $quantity) {
			if (!isset($quantity['expires'], $quantity['session_id']) || !$this->csr->is_expired($quantity['expires'], $this->get_order_awaiting_payment($quantity['session_id']))) {
				$valid_quantities[$stock_id] = $quantity['amount'];
			}
		}

		return $valid_quantities;
	}

	public function get_cart_item_attribute_stock_limits($item)
	{
		$product_id = !empty($item['variation_id']) ? $item['variation_id'] : $item['product_id'];

		$product = wc_get_product($product_id);
		if (!$product) return [];

		$variation = !empty($item['variation']) ? $item['variation'] : null;
		$limits = Limits::get_stock_limits($product, $variation);

		return $limits ?: [];
	}

	public function get_order_awaiting_payment($session_id)
	{
	    if (!$session_id || empty($this->sessions[$session_id])) {
	    	return null;
	    }

	    return $this->sessions[$session_id]->get('order_awaiting_payment', null);
	}
}
