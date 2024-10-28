<?php

use Mewz\WCAS\Plugin;
use Mewz\WCAS\Models\AttributeStock;
use Mewz\WCAS\Util;

/**
 * @return Plugin
 */
function Mewz_WCAS()
{
	return Plugin::instance();
}

/**
 * Loads a new or existing attribute stock object.
 *
 * @param int|\WP_Post $id The attribute stock ID or post object
 * @param string $context 'view', 'edit' or 'object'
 *
 * @return AttributeStock
 */
function mewz_wcas_get_stock($id = null, $context = 'view')
{
	return AttributeStock::instance($id, $context);
}

/**
 * Query one or more attribute stock items with WP_Query.
 *
 * @param array $args WP_Query args
 * @param string $context 'view' or 'edit'
 * @param string $return 'object', 'id' or 'query'
 *
 * @return AttributeStock[]|int[]|\WP_Query
 */
function mewz_wcas_query_stock(array $args = [], $context = 'view', $return = 'object')
{
	return AttributeStock::query($args, $context, $return);
}

/**
 * Retrieves the lowest matching attribute stock quantity data for a product or variation.
 *
 * This is the main method used to determine a product's available stock on the frontend
 * based on matching attribute stock.
 *
 * @param \WC_Product|int $product Product object or ID
 * @param array $variation Selected variation key/value pairs
 *
 * @return array|false
 */
function mewz_wcas_get_product_stock_limit($product, $variation = null)
{
	return Util\Limits::get_stock_limit($product, $variation);
}

/**
 * Retrieves a list of matching attribute stock quantity data for a product or variation,
 * sorted from lowest to highest stock.
 *
 * @param \WC_Product|int $product Product object or ID
 * @param array $variation Selected variation key/value pairs
 *
 * @return array|false
 */
function mewz_wcas_get_product_stock_limits($product, $variation = null)
{
	return Util\Limits::get_stock_limits($product, $variation);
}

/**
 * Finds all attribute stock items matching a product + attributes (including internal stock).
 *
 * @see mewz_wcas_get_product_attributes()
 *
 * @param \WC_Product|int $product Product object or ID
 * @param array $attributes Key/value pairs where key is an attribute id/name/taxonomy
 *                          and value is a term id/slug or an array of term ids and/or slugs
 * @param string $context 'view' or 'edit'
 *
 * @return array
 */
function mewz_wcas_match_product_stock($product, array $attributes, $context = 'view')
{
	return Util\Matches::match_product_stock($product, $attributes, $context);
}

/**
 * Finds attribute stock items based solely on attributes. Does not check if the stock items
 * are enabled, internal, or even exist.
 *
 * @param array $attributes Key/value pairs where key is an attribute id/name/taxonomy
 *                          and value is a term id/slug or an array of term ids and/or slugs
 *
 * @return array Raw stock match results, before any validation or filtering
 */
function mewz_wcas_match_raw_stock(array $attributes)
{
	return Util\Matches::match_raw_stock($attributes);
}

/**
 * Set variation attributes on a product object. This is required to match the correct attribute
 * stock when working with "any" product variations for example, where the attributes aren't
 * explicitly specified on the variation.
 *
 * @param \WC_Product $product
 * @param array<string, string>|null $variation Variation attributes
 */
function mewz_wcas_set_product_variation(\WC_Product $product, $variation)
{
	Util\Products::set_variation($product, $variation);
}

/**
 * Aggregates a complete list of attribute data for a product or variation.
 *
 * @param \WC_Product $product
 * @param array $variation Variation attribute data to merge
 * @param bool $assoc Key by attribute name instead of ID for non-variations
 * @param bool|string $inherit Include attributes from parent
 *
 * @return array
 */
function mewz_wcas_get_product_attributes(\WC_Product $product, $variation = null, $assoc = false, $inherit = true)
{
	return Util\Products::get_product_attributes($product, $variation, $assoc, $inherit);
}

/**
 * Gets the product multiplier to use for product stock or attribute stock.
 *
 * @param \WC_Product $product Product object
 * @param string $type Get only for 'product' stock or 'attribute' stock
 * @param T $default
 *
 * @template T
 * @return float|T
 */
function mewz_wcas_get_product_multiplier($product, $type = null, $default = 1.00)
{
	return Util\Products::get_multiplier($product, $type, $default);
}

/**
 * Checks if a product or variation has been excluded or ignored from attribute stock.
 *
 * Note: None of the above functions check for this automatically.
 *
 * @param \WC_Product|int $product Product object or ID
 * @param \WC_Product|false $parent Provide the product's parent object if applicable
 *                                  for improved performance, or false to skip checking parent
 *
 * @return bool
 */
function mewz_wcas_is_product_excluded($product, $parent = null)
{
	return Util\Products::is_product_excluded($product, $parent);
}

/**
 * Queries a list of all products matching an attribute stock item.
 *
 * Important: This can be a fairly intensive operation. It should be used sparingly only
 * when necessary, with appropriate use of the `$exclude` parameter.
 *
 * @param AttributeStock|int|array<AttributeStock|int> $stock Attribute stock item objects / IDs
 * @param bool $query_variations Expand found variable products to matching variations
 * @param int[] $exclude Product IDs to exclude
 *
 * @return array List of matching product IDs
 */
function mewz_wcas_get_matching_products($stock, $query_variations = false, $exclude = [])
{
	return Util\Matches::query_matching_products($stock, $query_variations, $exclude);
}

/**
 * Reduces attribute stock for an order accordingly. Does nothing if stock has already been reduced.
 *
 * @param int|\WC_Order $order
 *
 * @return bool
 */
function mewz_wcas_reduce_order_stock($order)
{
	return Util\Orders::update_order_attribute_stock($order, 'reduce');
}

/**
 * Restores attribute stock for an order that has previously been reduced.
 *
 * @param int|\WC_Order $order
 *
 * @return bool
 */
function mewz_wcas_restore_order_stock($order)
{
	return Util\Orders::update_order_attribute_stock($order, 'restore');
}

/**
 * Automatically reduce/restore attribute stock for an order if applicable.
 *
 * @param int|\WC_Order $order
 *
 * @return bool
 */
function mewz_wcas_sync_order_stock($order)
{
	return Util\Orders::update_order_attribute_stock($order, 'sync');
}

/**
 * Add log entry to WooCommerce logs.
 *
 * @param mixed $message
 * @param string $level
 * @param array $context
 */
function mewz_wcas_log($message, $level = \WC_Log_Levels::DEBUG, $context = null)
{
	static $debug_mode, $logger;

	if ($debug_mode === null && $level === \WC_Log_Levels::DEBUG) {
		$debug_mode = defined('MEWZ_DEBUG') ? MEWZ_DEBUG : (defined('WP_DEBUG') && WP_DEBUG);
	}

	if ($debug_mode || $level !== \WC_Log_Levels::DEBUG) {
		$logger ??= wc_get_logger();

		if ($logger) {
			$ctx = ['source' => Plugin::instance()->slug];

			if ($context) {
				$ctx = $context + $ctx;
			}

			if ($message instanceof \WP_Error) {
				$message = $message->get_error_message();
			} elseif (!is_string($message)) {
				$message = print_r($message, true);
			}

			$logger->log($level, $message, $ctx);
		}
	}
}
