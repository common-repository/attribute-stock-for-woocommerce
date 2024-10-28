<?php
namespace Mewz\WCAS\Util;

use Mewz\WCAS\Models\AttributeStock;

class Orders
{
	const REDUCED_STOCK_META = '_mewz_wcas_reduced_stock';

	/**
	 * Update attribute stock for some or all line items of the specified order.
	 *
	 * This WILL add an order note to the order.
	 *
	 * A list of order items can be specified to update only a subset of items.
	 *
	 * Important: All order items must be from the same order else hellfire shall rain from the skies.
	 *
	 * @param \WC_Order|int|null $order
	 * @param string $operation reduce|restore|sync
	 * @param \WC_Order_Item_Product|\WC_Order_Item_Product[]|int|array $order_items
	 * @param string $context
	 *
	 * @return array|false
	 */
	public static function update_order_attribute_stock($order, $operation = 'reduce', $order_items = null, $context = null)
	{
		if ((!$order && !$order_items) || !in_array($operation, ['reduce', 'restore', 'sync'])) {
			return false;
		}

		if ($order && !$order instanceof \WC_Order) {
			$order = wc_get_order($order);
		}

		$partial = (bool)$order_items;

		if ($order_items === null && $order) {
			$order_items = $order->get_items();
		}

		if (!$order && !$order_items) {
			return false;
		}

		if (!is_array($order_items)) {
			$order_items = $order_items ? [$order_items] : [];
		}

		$changes = [];
		$valid_items = [];

		foreach ($order_items as $order_item) {
			if (!$order_item) continue;

			if (is_array($order_item)) {
				if (empty($order_item[0])) continue;

				$quantity = isset($order_item[1]) ? $order_item[1] : null;
				$order_item = $order_item[0];
			} else {
				$quantity = null;
			}

			$order_item = self::get_valid_order_line_item($order_item);
			if (!$order_item) continue;

			$valid_items[] = $order_item;

			if (!$order) {
				$order = $order_item->get_order();
			}

			$change = self::update_order_item_attribute_stock($order_item, $operation, $quantity, $context);

			if ($change) {
				$changes[] = $change;
			}
		}

		if (!$order) {
			return $changes;
		}

		$hook_data = compact('order', 'operation', 'valid_items', 'partial', 'context');
		$hook_data['changes'] =& $changes;

		do_action('mewz_wcas_order_stock_update', $hook_data);
		do_action_deprecated('mewz_wcas_order_after_attribute_stock_update', [$order, $operation, &$changes, $valid_items, $partial], '2.0.0', 'mewz_wcas_order_stock_update');

		if ($changes) {
			$changes = self::merge_changes(...$changes);

			if ($changes) {
				self::add_stock_change_order_note($order, $operation, $changes, $context);
			}
		}

		do_action('mewz_wcas_order_stock_updated', $hook_data);
		do_action_deprecated('mewz_wcas_order_attribute_stock_updated', [$order, $operation, $changes, $valid_items, $partial], '2.0.0', 'mewz_wcas_order_stock_updated');

		return $changes;
	}

	/**
	 * Update attribute stock for a single order line item.
	 *
	 * This will NOT add an order note to the order.
	 *
	 * @param \WC_Order_Item_Product|int $order_item
	 * @param string $operation reduce|restore|sync
	 * @param float $quantity If set, stock is updated by this amount regardless of whether it
	 *                        has been updated before. Useful for incremental stock updates.
	 * @param string $context
	 *
	 * @return array|false
	 */
	public static function update_order_item_attribute_stock($order_item, $operation = 'reduce', $quantity = null, $context = null)
	{
		if (!in_array($operation, ['reduce', 'restore', 'sync'])) {
			return false;
		}

		if (!$order_item = self::get_valid_order_line_item($order_item)) {
			return false;
		}

		$product = $order_item->get_product() ?: new \WC_Product_Simple();

		if (Products::is_product_excluded($product)) {
			return false;
		}

		$reduced_qty = wc_stock_amount(max(0, (float)$order_item->get_meta(self::REDUCED_STOCK_META)));

		if ($operation === 'sync') {
			$item_qty = self::get_effective_order_item_quantity($order_item);

			if ($item_qty > $reduced_qty) {
				$operation = 'reduce';
				$quantity = $item_qty - $reduced_qty;
			} elseif ($reduced_qty > $item_qty) {
				$operation = 'restore';
				$quantity = $reduced_qty - $item_qty;
			} else {
				return [];
			}
		}

		$reduce = $operation === 'reduce';
		$update_allowed = $quantity !== null || $reduce !== (bool)$reduced_qty;

		$hook_data = compact('order_item', 'product', 'operation', 'quantity', 'reduced_qty', 'context');

		$update_allowed = apply_filters('mewz_wcas_order_item_update_allowed', $update_allowed, $hook_data);
		$update_allowed = apply_filters_deprecated('mewz_wcas_update_order_item_attribute_stock', [$update_allowed, $order_item, $product, $operation, $quantity, $reduced_qty], '2.0.0', 'mewz_wcas_order_item_update_allowed');
		if (!$update_allowed) return false;

		$attributes = self::get_order_item_attributes($order_item, $product);
		$matches = Matches::match_product_stock($product, $attributes);

		$hook_data['attributes'] = $attributes;

		$matches = apply_filters('mewz_wcas_order_item_matches', $matches, $hook_data);
		$matches = apply_filters_deprecated('mewz_wcas_order_item_stock_matches', [$matches, $order_item, $attributes, $product, $operation, $quantity], '2.0.0', 'mewz_wcas_order_item_matches');
		if (!$matches) return false;

		$hook_data['matches'] = $matches;

		$quantity ??= $reduce ? self::get_effective_order_item_quantity($order_item) : $reduced_qty;

		$quantity = apply_filters('mewz_wcas_order_item_update_quantity', $quantity, $hook_data);
		$quantity = apply_filters_deprecated('mewz_wcas_update_order_item_quantity', [$quantity, $matches, $order_item, $attributes, $product, $operation], '2.0.0', 'mewz_wcas_order_item_update_quantity');
		if (!$quantity) return false;

		$hook_data['quantity'] = $quantity;

		$comp_tree = Components::get_sorted_tree($matches);

		if ($comp_tree && is_array($comp_tree)) {
			$matches = $comp_tree;
			$hook_data['matches'] = $matches;

			if ($reduce) {
				$used_components = Components::calc_deductions($matches, $quantity)['deducted'];
			} else {
				// restoring component stock should only restore to top-level items + reduced components meta
				$order = new \WC_Order();
				$order->set_id($order_item->get_order_id());
				$order_reduced = $order->get_meta(self::REDUCED_STOCK_META);

				if ($order_reduced && is_array($order_reduced)) {
					// by default restore back to top-level stock items only, if refunding and order has been completed,
					// since if "restock refunded items" is ticked we assume the items have already been created from their components
					// and will be added back to the inventory, for example when receiving a returned product
					$restore_components = $context !== 'refund' || !$order->get_date_completed();
					$restore_components = apply_filters('mewz_wcas_order_item_restore_components', $restore_components, $hook_data + ['order_reduced' => $order_reduced]);
				} else {
					// can't restore components if reduced components meta doesn't exist
					$restore_components = false;
				}

				if ($restore_components) {
					$deduct_items = [];

					foreach ($matches as $stock_id => $match) {
						if (isset($order_reduced[$stock_id])) {
							$match['stock_qty'] = $order_reduced[$stock_id];
						} elseif ($match['comp']['use'] > 0) {
							$match['stock_qty'] = 0;
						} else {
							continue;
						}

						$deduct_items[$stock_id] = $match;
					}

					$used_components = Components::calc_deductions($deduct_items, $quantity)['deducted'];
				} else {
					foreach ($matches as $stock_id => $match) {
						if ($match['comp']['use'] <= 0) {
							unset($matches[$stock_id]);
						}
					}
				}
			}
		}

		$changes = [];

		foreach ($matches as $stock_id => $match) {
			if (isset($used_components) && !isset($used_components[$stock_id])) {
				continue;
			}

			$adjust_amount = $used_components[$stock_id] ?? $quantity * $match['multiplier'];

			$adjust_amount = apply_filters('mewz_wcas_order_item_adjust_amount', $adjust_amount, ['match' => $match] + $hook_data);
			$adjust_amount = apply_filters_deprecated('mewz_wcas_update_order_item_attribute_stock_amount', [$adjust_amount, $match, $matches, $order_item, $attributes, $product, $operation, $quantity], '2.0.0', 'mewz_wcas_order_item_adjust_amount');
			if (!$adjust_amount) continue;

			if ($reduce) {
				$adjust_amount = -$adjust_amount;
			}

			$change = self::update_stock_quantity($stock_id, $adjust_amount);

			if ($change) {
				$changes[] = $change;
			}
		}

		$reduced_qty += $reduce ? $quantity : -$quantity;

		if ($reduced_qty > 0) {
			$order_item->update_meta_data(self::REDUCED_STOCK_META, $reduced_qty);
		} else {
			$order_item->delete_meta_data(self::REDUCED_STOCK_META);
		}

		$order_item->save_meta_data();

		self::update_order_reduced_stock_amounts($order ?? $order_item->get_order_id(), $changes);

		$hook_data['changes'] = $changes;
		$hook_data['reduced_qty'] = $reduced_qty;

		do_action('mewz_wcas_order_item_updated', $hook_data);
		do_action_deprecated('mewz_wcas_order_item_attribute_stock_updated', [$order_item, $operation, $reduced_qty, $changes], '2.0.0', 'mewz_wcas_order_item_updated');

		return $changes;
	}

	/**
	 * @param AttributeStock|int $stock
	 * @param float|int $amount
	 *
	 * @return array|false
	 */
	public static function update_stock_quantity($stock, $amount)
	{
		$stock = AttributeStock::instance($stock, 'edit');

		if (!$stock->valid()) {
			return false;
		}

		$prev_quantity = $stock->quantity();
		$new_quantity = $stock->adjust_quantity($amount);

		if (!$stock->save()) {
			return false;
		}

		if ($new_quantity < $prev_quantity) {
			do_action('mewz_wcas_trigger_stock_notification', $stock, $prev_quantity);
		}

		return [
			'stock_id' => $stock->id(),
			'amount' => $amount,
			'from' => $prev_quantity,
			'to' => $new_quantity,
		];
	}

	/**
	 * @param \WC_Order_Item|int $order_item
	 *
	 * @return \WC_Order_Item_Product|false
	 */
	public static function get_valid_order_line_item($order_item)
	{
		if (!$order_item instanceof \WC_Order_Item && !$order_item = \WC_Order_Factory::get_order_item($order_item)) {
			return false;
		}

		if (!$order_item->get_id() || !$order_item instanceof \WC_Order_Item_Product) {
			return false;
		}

		return $order_item;
	}

	/**
	 * @param \WC_Order_Item_Product $order_item
	 * @param \WC_Product $product
	 *
	 * @return array
	 */
	public static function get_order_item_attributes($order_item, $product = null)
	{
		$product ??= $order_item->get_product();

		// get product attributes
		$attributes = $product ? Products::get_product_attributes($product, false, true) : [];

		// merge order item attributes
		$attributes = array_merge($attributes, self::get_order_item_meta_attributes($order_item));

		return apply_filters('mewz_wcas_order_item_attributes', $attributes, $order_item, $product);
	}

	/**
	 * @param \WC_Order_Item_Product $order_item
	 *
	 * @return array
	 */
	public static function get_order_item_meta_attributes($order_item)
	{
		$attributes = [];

		foreach ($order_item->get_meta_data() as $meta) {
			if (strpos($key = $meta->key, 'pa_') === 0) {
				$attributes[$key] = $meta->value;
			}
		}

		return apply_filters('mewz_wcas_order_item_meta_attributes', $attributes, $order_item);
	}

	/**
	 * @param \WC_Order_Item $order_item
	 *
	 * @return float
	 */
	public static function get_effective_order_item_quantity($order_item)
	{
		$refunded_qty = $order_item->get_order()->get_qty_refunded_for_item($order_item->get_id()); // returns a negative number

		return wc_stock_amount($order_item->get_quantity() + $refunded_qty);
	}

	/**
	 * @param array ...$changes
	 *
	 * @return array
	 */
	public static function merge_changes(...$changes)
	{
		$merged = [];

		foreach ($changes as $changeset) {
			if (!$changeset) continue;

			if (isset($changeset['stock_id'])) {
				$changeset = [$changeset];
			}

			foreach ($changeset as $change) {
				if (!isset($change['stock_id'], $change['amount'], $change['from'], $change['to'])) {
					continue;
				}

				$stock_id = $change['stock_id'];

				if (!isset($merged[$stock_id])) {
					$merged[$stock_id] = $change;
				} else {
					$merged[$stock_id]['amount'] += $change['amount'];
					$merged[$stock_id]['to'] = $change['to'];
				}
			}
		}

		return $merged;
	}

	/**
	 * @param \WC_Order|int $order
	 * @param array $changes
	 *
	 * @return array
	 */
	public static function update_order_reduced_stock_amounts($order, array $changes)
	{
		if (!$order instanceof \WC_Order) {
			$order_id = (int)$order;
			$order = new \WC_Order();
			$order->set_id($order_id);
		}

		$reduced = $order->get_meta(self::REDUCED_STOCK_META, true, 'edit') ?: [];

		foreach ($changes as $change) {
			$stock_id = $change['stock_id'];
			$amount = -$change['amount'];
			$reduced[$stock_id] = isset($reduced[$stock_id]) ? $reduced[$stock_id] + $amount : $amount;

			if ($reduced[$stock_id] <= 0) {
				unset($reduced[$stock_id]);
			}
		}

		if ($reduced) {
			$order->update_meta_data(self::REDUCED_STOCK_META, $reduced);
		} else {
			$order->delete_meta_data(self::REDUCED_STOCK_META);
		}

		$order->save_meta_data();

		return $reduced;
	}

	/**
	 * @param \WC_Order|int $order
	 * @param string $operation
	 * @param array $changes
	 * @param string $context
	 */
	public static function add_stock_change_order_note($order, $operation, array $changes, $context = null)
	{
		if (!$order instanceof \WC_Order) {
			$order = wc_get_order($order);
		}

		if (!$order) return;

		$hook_data = compact('order', 'operation', 'context');

		$changes = apply_filters('mewz_wcas_order_note_changes', $changes, $hook_data);
		$changes = apply_filters_deprecated('mewz_wcas_order_note_stock_changes', [$changes, $order, $operation, $context], '2.0.0', 'mewz_wcas_order_note_changes');
		if (!$changes) return;

		$lines = [];
		$line_format = _x('%1$s: %2$s&rarr;%3$s', 'order note line format', 'woocommerce-attribute-stock');

		foreach ($changes as $stock_id => $change) {
			if (!isset($change['from'], $change['to']) || $change['from'] == $change['to']) {
				continue;
			}

		    $stock = AttributeStock::instance($stock_id);

		    $sku = $stock->sku();
		    $title = $stock->title() . ($sku !== '' ? " ($sku)" : '');

			$lines[] = sprintf($line_format, $title, $change['from'], $change['to']);
		}

		if (!$lines) return;

		if ($operation === 'reduce') {
			$title = __('Stock levels reduced:', 'woocommerce');
		} elseif ($operation === 'restore') {
			$title = __('Stock levels increased:', 'woocommerce');
		} else {
			$title = trim(sprintf(__('Adjusted stock: %s', 'woocommerce'), ''));
		}

		$hook_data['lines'] = $lines;
		$title = apply_filters('mewz_wcas_order_note_title', $title, $hook_data);

		$order_note = $title . "\n" . implode("\n", $lines);

		$hook_data['title'] = $title;
		$order_note = apply_filters('mewz_wcas_order_note_output', $order_note, $hook_data);
		$order_note = apply_filters_deprecated('mewz_wcas_order_note', [$order_note, $order, $operation, $changes, $title, $lines], '2.0.0', 'mewz_wcas_order_note_output');

		if ($order_note) {
			$order->add_order_note($order_note);
		}
	}
}
