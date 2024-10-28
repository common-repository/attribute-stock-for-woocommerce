<?php
namespace Mewz\WCAS\Aspects\Common;

use Mewz\Framework\Base\Aspect;
use Mewz\WCAS\Util\Orders;
use Mewz\WCAS\Util\Products;

class UpdateOrderStock extends Aspect
{
	public function __hooks()
	{
		// update attribute stock item quantities
		add_filter('woocommerce_hidden_order_itemmeta', [$this, 'hidden_order_itemmeta']);
		add_action('woocommerce_before_order_item_object_save', [$this, 'before_order_item_object_save'], 10, 2);
	    add_action('woocommerce_reduce_order_stock', [$this, 'reduce_order_stock']);
	    add_action('woocommerce_restore_order_stock', [$this, 'restore_order_stock']);
	    add_action('woocommerce_before_delete_order_item', [$this, 'before_delete_order_item']);
	    add_action('woocommerce_before_save_order_items', [$this, 'before_save_order_items'], 10, 2);
	    add_action('woocommerce_refund_created', [$this, 'refund_created'], 10, 2);

		// update product stock with multipliers
		add_filter('woocommerce_order_item_quantity', [$this, 'order_item_quantity'], 10, 3);
		add_filter('woocommerce_prevent_adjust_line_item_product_stock', [$this, 'prevent_adjust_line_item_product_stock'], 20, 3);
		add_action('woocommerce_product_before_set_stock', [$this, 'product_before_set_stock'], 999);
		add_action('woocommerce_variation_before_set_stock', [$this, 'product_before_set_stock'], 999);
		add_action('woocommerce_product_set_stock', [$this, 'product_set_stock'], -999);
		add_action('woocommerce_variation_set_stock', [$this, 'product_set_stock'], -999);
	}

	public function hidden_order_itemmeta($meta_keys)
	{
		$meta_keys[] = Orders::REDUCED_STOCK_META;

		return $meta_keys;
	}

	public function before_order_item_object_save(\WC_Order_Item $item, $data_store)
	{
		if (!$item instanceof \WC_Order_Item_Product) {
			return;
		}

		// reset spoofed (multiplied) order item quantity before saving
		if (($quantity = Products::get_prop($item, 'original_quantity')) !== null) {
			$item->set_quantity($quantity);
		}

		// ensure new order items are always created without reduced stock meta (e.g. when duplicating orders)
		if (!$item->get_id()) {
			$data = $item->get_data();

			if (empty($data['order_id'])) {
				$item->delete_meta_data(Orders::REDUCED_STOCK_META);
			}
		}
	}

	public function reduce_order_stock($order)
	{
		Orders::update_order_attribute_stock($order, 'reduce', null, 'paid');
	}

	public function restore_order_stock($order)
	{
		Orders::update_order_attribute_stock($order, 'restore', null, 'cancel');
	}

	public function before_delete_order_item($item_id)
	{
		Orders::update_order_attribute_stock(null, 'restore', $item_id, 'delete');
	}

	public function before_save_order_items($order_id, $item_data)
	{
		if (empty($item_data['order_item_id']) || !$order_status = get_post_status($order_id)) {
			return;
		}

		$order_status = substr($order_status, 3);
		$has_valid_status = in_array($order_status, ['processing', 'completed', 'on-hold']);

		$order_items = [];

		foreach ($item_data['order_item_id'] as $item_id) {
			/** @var \WC_Order_Item_Product $item */
			$item = \WC_Order_Factory::get_order_item((int)$item_id);

			if (!$item instanceof \WC_Order_Item_Product) {
				continue;
			}

			if ($has_valid_status || $item->meta_exists(Orders::REDUCED_STOCK_META)) {
				$item->set_quantity((float)$item_data['order_item_qty'][$item_id]);
				$order_items[] = $item;
			}
		}

		if ($order_items) {
			Orders::update_order_attribute_stock($order_id, 'sync', $order_items);
		}
	}

	public function refund_created($refund_id, $args)
	{
	    if (!$refund_id || empty($args['restock_items']) || empty($args['line_items']) || !$order = wc_get_order($args['order_id'])) {
		    return;
	    }

	    $refunded_items = $args['line_items'];
		$order_items = [];

	    /** @var \WC_Order_Item_Product $item */
		foreach ($order->get_items() as $item_id => $item) {
			if (!isset($refunded_items[$item_id]['qty']) || !$item->meta_exists(Orders::REDUCED_STOCK_META)) {
				continue;
			}

			$order_items[] = [$item, (float)$refunded_items[$item_id]['qty']];
		}

		Orders::update_order_attribute_stock($order, 'restore', $order_items, 'refund');
	}

	public function order_item_quantity($quantity, $order, $item)
	{
		if (
			($product = $item->get_product())
			&& ($multiplier = Products::get_multiplier($product, 'product')) !== 1.00
		) {
			$quantity = wc_stock_amount($quantity * $multiplier);
		}

		return $quantity;
	}

	public function prevent_adjust_line_item_product_stock($prevent, $item, $quantity)
	{
		if (!$prevent && $quantity < 0 && $item instanceof \WC_Order_Item_Product && $product = $item->get_product()) {
			$multiplier = Products::get_multiplier($product, 'product');

			if ($multiplier !== 1.00) {
				$original_qty = $item->get_quantity();
				$new_qty = wc_stock_amount($original_qty * $multiplier);

				if ($new_qty != $original_qty) {
					Products::set_prop($item, 'original_quantity', $original_qty);
					$item->set_quantity($new_qty);
				}
			}
		}

		return $prevent;
	}

	public function product_before_set_stock($product_with_stock)
	{
		global $wpdb;

		$trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 6);

		if (!isset($trace[5]) || $trace[4]['function'] !== 'wc_update_product_stock' || $trace[5]['function'] !== 'wc_restock_refunded_items') {
			return;
		}

		$args = $trace[4]['args'];
		$product = $args[0];
		$quantity = $args[1];
		$operation = $args[2] ?? 'set';
		$updating = $args[3] ?? false;

		if ($operation !== 'increase') {
			return;
		}

		$multiplier = Products::get_multiplier([$product, $product_with_stock], 'product');

		if ($multiplier === 1.00) {
			return;
		}

		$new_quantity = wc_stock_amount($quantity * $multiplier);

		if ($new_quantity == $quantity) {
			return;
		}

		Products::set_prop($product_with_stock, 'update_product_stock', [$new_quantity, $updating]);

		// start a transaction before regular stock update so we can rollback
		$wpdb->query('START TRANSACTION');
	}

	public function product_set_stock($product_with_stock)
	{
		global $wpdb;

		$update = Products::get_prop($product_with_stock, 'update_product_stock');
		if (!$update) return;

		Products::set_prop($product_with_stock, 'update_product_stock', null);

		// revert regular stock update so we can repeat it with our multiplied quantity
		$wpdb->query('ROLLBACK');

		[$quantity, $updating] = $update;
		$operation = $quantity > 0 ? 'increase' : 'decrease';

		/** @var \WC_Product_Data_Store_CPT $data_store */
		$data_store = \WC_Data_Store::load('product');
		$new_stock = $data_store->update_product_stock($product_with_stock->get_id(), abs($quantity), $operation);
		$data_store->read_stock_quantity($product_with_stock, $new_stock);

		if (!$updating) {
			$product_with_stock->save();
		}
	}
}
