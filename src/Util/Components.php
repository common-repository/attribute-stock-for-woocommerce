<?php
namespace Mewz\WCAS\Util;

use Mewz\Framework\Util\Number;
use Mewz\QueryBuilder\DB;
use Mewz\WCAS\Models\AttributeStock;

class Components
{
	const TABLE = 'wcas_components';

	/**
	 * @param int $stock_id
	 * @param string $context
	 *
	 * @return array
	 */
	public static function get_components($stock_id, $context = 'view')
	{
		if (!$stock_id) return [];
		$stock_id = (int)$stock_id;

		if ($context === 'view') {
			$cache_key = "components_{$stock_id}";
			$cache_tags = ['components', 'stock'];

			$value = Mewz_WCAS()->cache->get($cache_key, $cache_tags);

			if (is_array($value)) {
				return $value;
			}
		}

		$results = DB::table(self::TABLE, 'c')
			->select('c.parent_id, c.child_id, c.quantity')
			->left_join('posts', 'p')->on('p.ID = IF(c.parent_id = ?, c.child_id, c.parent_id)', $stock_id)
			->where("c.parent_id = ? OR c.child_id = ?", $stock_id)
			->where('p.post_status', ['publish', 'draft'])
			->get();

		$components = [
			'parent' => [],
			'child' => [],
		];

		foreach ($results as $row) {
			$quantity = $context === 'view'
				? ($row->quantity === '' ? 1 : (float)$row->quantity)
				: $row->quantity;

			if ($row->parent_id == $stock_id) {
				$components['child'][$row->child_id] = $quantity;
			} elseif ($row->child_id == $stock_id) {
				$components['parent'][$row->parent_id] = $quantity;
			}
		}

		if ($context === 'view') {
			Mewz_WCAS()->cache->set($cache_key, $components, $cache_tags);
		}

		return $components;
	}

	/**
	 * @param int $stock_id
	 * @param array|false $components ['parent' => [parent_id => quantity], 'child' => [child_id => quantity]]
	 */
	public static function save_components($stock_id, $components)
	{
		$stock_id = (int)$stock_id;

		do_action('mewz_wcas_components_before_save', $stock_id, $components);

		$save_parent = isset($components['parent']);
		$save_child = isset($components['child']);

		if (!$components || ($save_parent && $save_child && !$components['parent'] && !$components['child'])) {
			DB::table(self::TABLE)
				->where('parent_id', $stock_id)
				->or()->where('child_id', $stock_id)
				->delete();

			do_action('mewz_wcas_components_saved', $stock_id, false);
			return;
		}

		if (!is_array($components)) {
			return;
		}

		$save_sets = [];

		foreach ($components as $type => $comps) {
			if (!$comps || !in_array($type, ['parent', 'child'])) {
				continue;
			}

			foreach ($comps as $comp_id => $quantity) {
				$quantity = $quantity <= 0 || $quantity == 1 ? '' : Number::safe_decimal($quantity);

				if ($type === 'parent') {
					$save_sets[$comp_id][$stock_id] = $quantity;
				} else {
					$save_sets[$stock_id][$comp_id] = $quantity;
				}

				// remove circular references
				if (isset($save_sets[$comp_id][$stock_id], $save_sets[$stock_id][$comp_id])) {
					unset($save_sets[$comp_id][$stock_id], $save_sets[$stock_id][$comp_id]);
				}
			}
		}

		$results = DB::table(self::TABLE)
			->where('parent_id', $stock_id)
			->or()->where('child_id', $stock_id)
			->get();

		if ($results) {
			$delete_ids = [];

			foreach ($results as $row) {
				$row_id = (int)$row->id;
				$parent_id = (int)$row->parent_id;
				$child_id = (int)$row->child_id;

				// delete existing row if saving the opposite
				if (isset($save_sets[$child_id][$parent_id])) {
					$delete_ids[] = $row_id;
					continue;
				}

				// delete existing row if it's not present in the rows being saved/updated
				if (!isset($save_sets[$parent_id][$child_id])) {
					if (
						($save_parent && $child_id == $stock_id)
						|| ($save_child && $parent_id == $stock_id)
					) {
						$delete_ids[] = $row_id;
					}

					continue;
				}

				$quantity = $save_sets[$parent_id][$child_id];

				if ($row->quantity !== $quantity) {
					DB::table(self::TABLE)->where('id', $row_id)->update(['quantity' => $quantity]);
				}

				unset($save_sets[$parent_id][$child_id]);
			}

			if ($delete_ids) {
				DB::table(self::TABLE)->where('id', $delete_ids)->delete();
			}
		}

		$insert_rows = [];

		foreach ($save_sets as $parent_id => $child_ids) {
			foreach ($child_ids as $child_id => $quantity) {
				$insert_rows[] = [
					'parent_id' => $parent_id,
					'child_id' => $child_id,
					'quantity' => $quantity,
				];
			}
		}

		if ($insert_rows) {
			DB::insert(self::TABLE, $insert_rows);
		}

		do_action('mewz_wcas_components_saved', $stock_id, $components);
	}

	/**
	 * Quick cached check whether component stock is being used at all.
	 *
	 * @return bool
	 */
	public static function using_components()
	{
		static $value;

		if ($value === null) {
			if (Settings::components_max_depth(true) <= 0) {
				$value = false;
			} else {
				$table = DB::prefix(self::TABLE);
				$value = (bool)DB::$wpdb->get_var("SELECT EXISTS (SELECT * FROM $table LIMIT 1) AS result");
			}
		}

		return $value;
	}

	/**
	 * Simple query to check if (enabled) components exist for the provided stock IDs.
	 *
	 * @param int|int[] $stock_ids
	 * @param string $context
	 *
	 * @return bool
	 */
	public static function has_components($stock_ids, $context = 'view')
	{
		if (!$stock_ids) {
			return false;
		}

		$table = DB::prefix(self::TABLE);
		$condition = DB::where('parent_id', $stock_ids);

		if ($context === 'view') {
			$sql = "
				SELECT * FROM $table AS c
				LEFT JOIN " . DB::$wpdb->posts . " AS p ON p.ID = c.child_id
				WHERE c.$condition AND p.post_status = 'publish'
			";
		} else {
			$sql = "SELECT * FROM $table WHERE $condition";
		}

		$sql = "SELECT EXISTS ($sql LIMIT 1) AS result";

		return (bool)DB::$wpdb->get_var($sql);
	}

	/**
	 * Get a full topologically sorted list of stock items and components.
	 *
	 * @param array $stock_items
	 * @param bool $check_exists Check if components are enabled and exist before doing full query.
	 *
	 * @return array|false|\WP_Error
	 */
	public static function get_sorted_tree($stock_items, $check_exists = true)
	{
		// the majority of stores won't use components, so we want to make sure to have the absolute
		// minimum amount of overhead for checking *every* product for component stock
		if ($check_exists && (!self::using_components() || !self::has_components(array_keys($stock_items)))) {
			return false;
		}

		$tree = self::get_unsorted_tree($stock_items);
		if (!$tree) return false;

		return self::sort_tree($tree);
	}

	/**
	 * Rudimentary check if stock item list has (most likely) been topologically sorted.
	 *
	 * @param array $stock_items
	 *
	 * @return bool
	 */
	public static function is_sorted_tree($stock_items)
	{
		return isset(current($stock_items)['comp']);
	}

	/**
	 * Get a full list of all component tree relationships for one or more stock IDs.
	 *
	 * @param int|int[] $stock_ids
	 * @param int $max_depth
	 *
	 * @return array|false
	 */
	public static function get_relationships($stock_ids, $max_depth = null)
	{
		static $query = [];

		if (!$stock_ids) return false;

		$max_depth ??= Settings::components_max_depth(true);

		if (!is_int($max_depth) || $max_depth <= 0) {
			return false;
		}

		if (!isset($query[$max_depth])) {
			$table_components = DB::prefix(self::TABLE);
			$table_posts = DB::$wpdb->posts;

			$select = 'p1.ID parent_id, p0.ID child_id, c1.quantity';
			$join[] = "FROM $table_components c1";
			$join[] = "JOIN $table_posts p0 ON c1.child_id = p0.ID AND p0.post_status = 'publish'";
			$join[] = "JOIN $table_posts p1 ON c1.parent_id = p1.ID AND p1.post_status = 'publish'";
			$parents[] = 'p1.ID';

			for ($i = 2; $i <= $max_depth; $i++) {
				$p = $i - 1;
				$join[] = "LEFT JOIN $table_components c$i ON p$p.ID IS NOT NULL AND c$p.parent_id = c$i.child_id";
				$join[] = "LEFT JOIN $table_posts p$i ON c$i.parent_id = p$i.ID AND p$i.post_status = 'publish'";
				$parents[] = "p$i.ID";
			}

			$parents = implode(', ', $parents);
			$join[] = "JOIN $table_components s ON s.parent_id IN ($parents) AND s.parent_ID IN ";
			$join = implode("\n", $join);

			$query[$max_depth] = "SELECT DISTINCT $select $join";
		}

		$sql = $query[$max_depth] . DB::value((array)$stock_ids);

		return DB::$wpdb->get_results($sql);
	}

	/**
	 * Get a full *unsorted* tree of stock items and components.
	 *
	 * @param array $stock_items List of stock item matches or limits.
	 * @param bool $get_stock_quantities Whether to load stock quantities for component items.
	 *
	 * @return array|bool
	 */
	public static function get_unsorted_tree($stock_items, $get_stock_quantities = true)
	{
		$relationships = self::get_relationships(array_keys($stock_items));

		if (!$relationships) {
			return false;
		}

		// build full list of stock items + components with component metadata
		foreach ($relationships as $rel) {
			$parent_id = (int)$rel->parent_id;
			$child_id = (int)$rel->child_id;

			if (!isset($stock_items[$parent_id])) {
				$stock_items[$parent_id] = self::get_component_stock_item($parent_id, $get_stock_quantities);
			}

			$quantity = $rel->quantity === '' ? 1 : (float)$rel->quantity;
			$stock_items[$parent_id]['comp']['children'][$child_id] = $quantity;

			if (!isset($stock_items[$child_id]['comp']['parent_id'])) {
				if (!isset($stock_items[$child_id])) {
					$stock_items[$child_id] = self::get_component_stock_item($child_id, $get_stock_quantities);
				} else {
					// child components don't use match rule multipliers
					$stock_items[$child_id]['multiplier'] = 1;
					unset($stock_items[$child_id]['limit_qty']);
				}

				// we need one parent id for sorting, any will do
				$stock_items[$child_id]['comp']['parent_id'] = $parent_id;
			}
		}

		return $stock_items;
	}

	/**
	 * Topologically sort an unsorted tree of stock items and components.
	 *
	 * @param array $unsorted_tree Unsorted tree of stock items and components.
	 *
	 * @return array|\WP_Error
	 */
	public static function sort_tree($unsorted_tree)
	{
		// sort using a variant of kahn's algorithm
		$tree = [];
		$parent_ids = [];
		$next_node = current($unsorted_tree);

		while ($node = $next_node) {
			if (!isset($node['comp'])) {
				$node['comp'] = [];
			}

			$comp = $node['comp'];

			if (!empty($comp['children'])) {
				$parent_ids[$node['stock_id']] = true;

				foreach ($comp['children'] as $child_id => $quantity) {
					// skip child node if already sorted
					if (isset($tree[$child_id])) {
						continue;
					}

					// bail out if recursion is detected
					if (isset($parent_ids[$child_id])) {
						return new \WP_Error('circular_reference', "Circular reference detected in component tree at attribute stock item ID $child_id. Component stock will not apply for the current match set.", $child_id);
					}

					// go to child node next if not already checked
					$next_node = $unsorted_tree[$child_id];
					continue 2;
				}
			}

			if (isset($comp['parent_id'], $unsorted_tree[$comp['parent_id']])) {
				// node is a child component
				$node['comp']['use'] = 0;

				// go to parent node next
				$next_node = $unsorted_tree[$comp['parent_id']];
			} else {
				// node is a top-level stock item
				$node['comp']['use'] = !empty($node['multiplier']) ? $node['multiplier'] : 1;

				// go to the next node in the stack or end
				if (!empty($unsorted_tree)) {
					$next_node = current($unsorted_tree);
				} else {
					$next_node = null;
				}
			}

			// remove this node from the stack and add it to the sorted list
			$stock_id = $node['stock_id'];
			unset($unsorted_tree[$stock_id], $node['comp']['parent_id']);
			$tree[$stock_id] = $node;
			$parent_ids = [];
		}

		$tree = array_reverse($tree, true);

		return $tree;
	}

	/**
	 * Calculates the effective available stock quantity from a sorted component tree.
	 *
	 * @param array $sorted_tree Sorted tree of stock items and components.
	 *
	 * @return int|float
	 */
	public static function calc_limit_quantity($sorted_tree)
	{
		#TODO: Add support for checking if any stock exists with components (for product attributes lookup compatibility)

		$limit_qty = 0;

		for (;;) {
			$deduct = 999999999999;
			$deduct_ids = [];

			foreach ($sorted_tree as $stock_id => &$stock) {
				if ($stock['comp']['use'] <= 0) {
					continue;
				}

				$use = $stock['comp']['use'];
				$stock_qty = !empty($stock['multiplier']) ? $stock['stock_qty'] : 0;

				if ($stock_qty >= $use) {
					$deduct_ids[] = $stock_id;
					$max = (int)($stock_qty / $use);

					if ($max < $deduct) {
						$deduct = $max;
					}
				} elseif (!empty($stock['comp']['children'])) {
					if ($stock_qty > 0) {
						$deduct = 1;
						$deduct_ids[] = $stock_id;

						$child_use = $use - $stock_qty;
						$stock['comp']['use'] = $stock_qty;
					} else {
						$child_use = $use;
						$stock['comp']['use'] = 0;
					}

					foreach ($stock['comp']['children'] as $child_id => $quantity) {
						$sorted_tree[$child_id]['comp']['use'] += $child_use * $quantity;
					}
				} else {
					// we've reached a leaf with no more stock, so we're done calculating
					break 2;
				}
			}

			if ($deduct_ids) {
				// apply the deductions
				foreach ($deduct_ids as $stock_id) {
					$sorted_tree[$stock_id]['stock_qty'] -= $sorted_tree[$stock_id]['comp']['use'] * $deduct;
				}

				$limit_qty += $deduct;
			} else {
				// prevent infinite loop if all items have 'use' = 0
				break;
			}
		}

		return wc_stock_amount($limit_qty);
	}

	/**
	 * @param array $stock_items
	 * @param numeric $deduct_quantity
	 *
	 * @return array
	 */
	public static function calc_deductions($stock_items, $deduct_quantity)
	{
		$deduct_remaining = (float)$deduct_quantity;
		$deducted = [];
		$remainder = [];
		$dead_leaves = [];

		// prepare by marking top items
		foreach ($stock_items as $stock_id => &$stock) {
			if ($stock['comp']['use'] > 0) {
				$stock['comp']['top_use'] = $stock['comp']['use'];
				$stock['comp']['top_ids'] = [$stock_id => true];
			}
		}
		unset($stock);

		for (;;) {
			$deduct_qty = $deduct_remaining;

			if ($deduct_qty > 0) {
				$deduct_ids = [];
				$found_dead_leaf = false;

				foreach ($stock_items as $stock_id => &$stock) {
					if ($stock['comp']['use'] <= 0) {
						continue;
					}

					$comp = $stock['comp'];

					if ($stock['stock_qty'] >= $comp['use']) {
						// item has enough stock, deduct from it directly
						$deduct_ids[$stock_id] = $stock_id;
						$max_qty = (int)($stock['stock_qty'] / $comp['use']);

						if ($max_qty < $deduct_qty) {
							$deduct_qty = $max_qty;
						}
					} elseif (!empty($comp['children'])) {
						// item doesn't have enough stock, source from its components
						if ($stock['stock_qty'] > 0) {
							$deduct_qty = 1;
							$deduct_ids[$stock_id] = $stock_id;
							$children_use = $comp['use'] - $stock['stock_qty'];
							$stock['comp']['use'] = $stock['stock_qty'];
						} else {
							$children_use = $comp['use'];
							$stock['comp']['use'] = 0;
						}

						foreach ($comp['children'] as $child_id => $quantity) {
							$stock_items[$child_id]['comp']['use'] += $children_use * $quantity;
							$stock_items[$child_id]['comp']['top_ids'] = isset($child['top_ids']) ? $child['top_ids'] + $comp['top_ids'] : $comp['top_ids'];
						}
					} else {
						// we've reached a dead leaf, store remainder to be added to top item deductions
						$found_dead_leaf = true;
						$dead_leaves[$stock_id] = $stock_id;

						foreach ($comp['top_ids'] as $top_id => $_) {
							if (!isset($remainder[$top_id])) {
								$remainder[$top_id] = $deduct_remaining;
							}
						}
					}
				}
				unset($stock);

				// if we found any dead leaves, prune the tree and start over
				if ($found_dead_leaf) {
					foreach ($stock_items as $stock_id => &$stock) {
						if (isset($stock['comp']['top_use'])) {
							$stock['comp']['use'] = !isset($remainder[$stock_id]) ? $stock['comp']['top_use'] : 0;
						} else {
							$stock['comp']['use'] = 0;
							$stock['comp']['top_ids'] = [];
						}
					}

					unset($stock);
					continue;
				}

				// apply deductions
				if ($deduct_ids) {
					$deduct_remaining -= $deduct_qty;

					foreach ($deduct_ids as $stock_id) {
						$amount = $deduct_qty * $stock_items[$stock_id]['comp']['use'];
						$stock_items[$stock_id]['stock_qty'] -= $amount;
						$deducted[$stock_id] = isset($deducted[$stock_id]) ? $deducted[$stock_id] + $amount : $amount;
					}

					continue;
				}
			}

			// add remaining deductions to top items that couldn't be sourced from components
			if ($remainder) {
				foreach ($remainder as $stock_id => $amount) {
					$deduct = $amount * $stock_items[$stock_id]['comp']['top_use'];
					$stock_items[$stock_id]['stock_qty'] -= $amount;
					$deducted[$stock_id] = isset($deducted[$stock_id]) ? $deducted[$stock_id] + $deduct : $deduct;
				}
			}

			$remaining = [];

			foreach ($stock_items as $stock_id => $stock) {
				$remaining[$stock_id] = $stock['stock_qty'];
			}

			return [
				'deducted' => $deducted,
				'remaining' => $remaining,
				'dead_leaves' => $dead_leaves,
			];
		}
	}

	/**
	 * Get stock item data for a component by ID.
	 *
	 * @param int $stock_id
	 * @param bool $get_stock_quantity Whether to load stock quantity.
	 *
	 * @return array
	 */
	protected static function get_component_stock_item($stock_id, $get_stock_quantity = true)
	{
		$stock_id = (int)$stock_id;

		if ($get_stock_quantity) {
			// avoid loading a new stock instance just to get stock quantity
			if (AttributeStock::has_instance($stock_id)) {
				$stock = AttributeStock::instance($stock_id);
				$stock_qty = $stock->quantity();
			} else {
				$stock_qty = (float)get_post_meta($stock_id, '_quantity', true);
			}
		} else {
			$stock_qty = 0;
		}

		$stock_item = [
			'stock_id' => $stock_id,
			'stock_qty' => $stock_qty,
			'multiplier' => 1,
			'comp' => [],
		];

		return $stock_item;
	}
}
