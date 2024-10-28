<?php
namespace Mewz\WCAS\Util;

use Mewz\Framework\Util\Multilang;

class Products
{
	/** @var array */
	public static $options = [];

	/** @var array */
	public static $categories = [];

	/** @var array */
	public static $attributes = [];

	/** @var \WeakMap */
	public static $propmap = null;

	/**
	 * Checks if a product has a valid object type.
	 *
	 * @param \WC_Product $product
	 * @param bool $allow_variable
	 *
	 * @return bool
	 */
	public static function is_valid_product($product, $allow_variable = false)
	{
		if (!$product instanceof \WC_Product || $product instanceof \WC_Product_Grouped || $product instanceof \WC_Product_External) {
			return false;
		}

		if (!$allow_variable && $product instanceof \WC_Product_Variable) {
			return false;
		}

	    return true;
	}

	/**
	 * This is a modified version of `WC_Product::validate_props()` that uses 'edit' context so as not to
	 * save data that's been modified by filters in 'view' context.
	 *
	 * It also fixes the original method not respecting the 'woocommerce_stock_amount' filter to allow
	 * decimal stock quantities.
	 *
	 * @see \WC_Product::validate_props()
	 *
	 * @param \WC_Product $product
	 */
	public static function validate_stock_props($product)
	{
		if ($manage_stock = $product->get_manage_stock('edit')) {
			if ($manage_stock === 'parent' && method_exists($product, 'get_parent_data')) {
				$parent_data = $product->get_parent_data();
				$stock_quantity = $parent_data['stock_quantity'];
			} else {
				$stock_quantity = $product->get_stock_quantity('edit');
			}

			$multiplier = self::get_multiplier($product, 'product');
			$stock_quantity = Matches::calc_limit_qty($stock_quantity, $multiplier);

			$no_stock_amount = max(0, (int)get_option('woocommerce_notify_no_stock_amount', 0));

			if ($stock_quantity > $no_stock_amount) {
				$new_stock_status = 'instock';
			} elseif ($product->get_backorders('edit') !== 'no') {
				$new_stock_status = 'onbackorder';
			} else {
				$new_stock_status = 'outofstock';
			}

			$product->set_stock_status($new_stock_status);
		} else {
			// set 'stock_status' back to what it was before `$product->validate_props()` changed it with 'view' context
			if ($original_stock_status = self::get_prop($product, 'original_stock_status')) {
				$product->set_stock_status($original_stock_status);
			}

			$product->set_stock_quantity('');
			$product->set_backorders('no');
			$product->set_low_stock_amount('');
		}
	}

	/**
	 * Checks if a product or variation has been excluded/ignored from attribute stock.
	 *
	 * @param \WC_Product|int $product Product object or ID
	 * @param \WC_Product|false $parent Provide the product's parent object if applicable
	 *                                  for improved performance, or false to skip checking parent
	 *
	 * @return bool
	 */
	public static function is_product_excluded($product, $parent = null)
	{
		return (bool)self::get_option('exclude', $product, $parent);
	}

	/**
	 * Gets the value of a product or variation option.
	 *
	 * @param string $name Option name
	 * @param \WC_Product|int $product Product object or ID
	 * @param \WC_Product|bool $parent Provide the product's parent object for improved performance,
	 *                                 or true if product is parent, or false to skip checking parent
	 *
	 * @return string
	 */
	public static function get_option($name, $product, $parent = null)
	{
		if ($product instanceof \WC_Product) {
			$product_id = $product->get_id();
		} elseif (is_numeric($product) && $product > 0) {
			$product_id = (int)$product;
			$product = null;
		} else {
			return '';
		}

		$check_parent = $parent !== false;

		if (isset(self::$options[$name][$check_parent][$product_id])) {
			return self::$options[$name][$check_parent][$product_id];
		}

		$key = '_mewz_wcas_' . $name;
		$value = (string)($product ? $product->get_meta($key) : get_post_meta($product_id, $key, true));

		if ($value === '' && $check_parent && $parent !== true) {
			if ($parent) {
				$parent_id = $parent instanceof \WC_Product ? $parent->get_id() : (int)$parent;
			} else {
				$parent_id = $product ? $product->get_parent_id() : wp_get_post_parent_id($product_id);
			}

			if ($parent_id && $parent_id !== $product_id) {
				$parent = $parent instanceof \WC_Product ? $parent : $parent_id;
				$value = self::get_option($name, $parent, true);
			}
		}

		$value = apply_filters('mewz_wcas_product_option_' . $name, $value, $product_id);

		if ($key === 'exclude') {
			$value = apply_filters_deprecated('mewz_wcas_is_product_excluded', [$value, $product_id], '2.0.0', 'mewz_wcas_product_option_' . $name);
		}

		return self::$options[$name][$check_parent][$product_id] = $value;
	}

	/**
	 * Check if product should use multipliers for product stock or attribute stock.
	 * By default, if the product is managing stock then use multipliers for product stock,
	 * otherwise use multipliers for matching attribute stock items.
	 *
	 * @param \WC_Product $product
	 * @param string $type The type of stock to use multiplier for ('product' or 'attribute')
	 *
	 * @return bool
	 */
	public static function use_multiplier($product, $type)
	{
		if (MEWZ_WCAS_LITE && $type === 'product') {
			return false;
		}

	    $manage_stock = self::without_limits($product, 'get_manage_stock');

		$use_multiplier = $manage_stock ? $type === 'product' : $type === 'attribute';

		return apply_filters('mewz_wcas_use_product_multiplier', $use_multiplier, $product, $type);
	}

	/**
	 * Gets the product multiplier to use for product stock or attribute stock.
	 *
	 * @param \WC_Product|\WC_Product[] $product Product object or [product, parent]
	 * @param string $type Get only for 'product' stock or 'attribute' stock
	 * @param T $default
	 *
	 * @template T
	 * @return float|T
	 */
	public static function get_multiplier($product, $type = null, $default = 1.00)
	{
		if (is_array($product)) {
			[$product, $parent] = $product;
		}

		if (!$product instanceof \WC_Product) {
			return $default;
		}

		if (!$type) {
			$manage_stock = self::without_limits($product, 'get_manage_stock');
			$type = $manage_stock ? 'product' : 'attribute';
		}

		$prop_key = "{$type}_multiplier";
		$value = self::get_prop($product, $prop_key);

		if (!isset($value)) {
			if (!self::use_multiplier($product, $type)) {
				$value = '';
			} elseif (!empty($parent)) {
				$value = self::get_option('multiplier', $product, $parent);
			} else {
				$value = self::get_option('multiplier', $product);
			}

			if ($value !== '') {
				$value = (float)$value;
			} elseif (
				$type === 'product'
				&& self::is_valid_product($product)
				&& ($manage_stock ?? self::without_limits($product, 'get_manage_stock'))
			) {
				$value = self::match_term_multiplier($product, null, false, '');
			}

			$value = apply_filters('mewz_wcas_product_multiplier', $value, $product, $type);

			self::set_prop($product, $prop_key, $value);
		}

		return $value === '' ? $default : $value;
	}

	/**
	 * @param \WC_Product $product
	 * @param array $variation
	 * @param bool $match_any_range Whether to return a min-max range of multipliers for "any" attributes
	 * @param T $default
	 *
	 * @template T
	 * @return float|float[]|T
	 */
	public static function match_term_multiplier($product, $variation = null, $match_any_range = false, $default = 1.00)
	{
		if (self::without_limits($product, 'get_manage_stock') === 'parent') {
			$inherit = $match_any_range ? 'all' : true;
		} else {
			$inherit = false;
		}

		$attributes = self::get_product_attributes($product, $variation, true, $inherit);
		if (!$attributes) return $default;

		$multiplier = Attributes::match_term_multiplier($attributes, $match_any_range, $default);

		return apply_filters('mewz_wcas_match_product_multiplier', $multiplier, $product, $attributes, $match_any_range, $default);
	}

	/**
	 * Gets a complete list of product attribute data.
	 *
	 * @param \WC_Product $product
	 * @param array $variation Variation attribute data to merge
	 * @param bool $assoc Key by attribute name instead of ID for non-variations
	 * @param bool|string $inherit Include attributes from parent
	 *
	 * @return array
	 */
	public static function get_product_attributes(\WC_Product $product, $variation = null, $assoc = false, $inherit = true)
	{
		$attributes = [];

		foreach ($product->get_attributes() as $key => $attr) {
		    if ($attr instanceof \WC_Product_Attribute) {
			    $attr_id = $attr->get_id();

			    if ($attr_id && $terms = $attr->get_options()) {
				    $key = $assoc ? $attr->get_name() : $attr_id;
				    $attributes[$key] = $terms;
			    }
		    } else {
			    if ($attr === '' && $inherit === 'all') {
				    continue;
				}

			    $attributes[wc_sanitize_taxonomy_name($key)] = $attr;
		    }
		}

		if ($inherit && $parent_id = $product->get_parent_id()) {
			// temporarily cache parent attributes to avoid excessive `wc_get_product()` calls
			if (isset(self::$attributes[$parent_id])) {
				$attributes += self::$attributes[$parent_id];
			}
			elseif ($parent = wc_get_product($parent_id)) {
				$parent_attr = [];

				// get non-variation attributes from parent product
				/** @var \WC_Product_Attribute $attr */
				foreach ($parent->get_attributes() as $attr) {
					if ($attr->get_id() && $terms = $attr->get_options()) {
						$parent_attr[$attr->get_name()] = $terms;
					}
				}

				$attributes += $parent_attr;
				self::$attributes[$parent_id] = $parent_attr;
			}
		}

		// get variation attribute data attached to product object
		if ($variation === null && $product_variation = self::get_prop($product, 'variation')) {
			$variation = $product_variation;
		}

		// merge additional variation attribute data
		if (is_array($variation) && $variation) {
			$variation_data = Attributes::strip_attribute_prefix($variation);
			$variation_data = Attributes::decode_keys($variation_data);

			$attributes = $variation_data + $attributes;
		}

		$attributes = apply_filters('mewz_wcas_product_attributes', $attributes, $product, $variation, $assoc);

		return is_array($attributes) ? $attributes : [];
	}

	/**
	 * Gets a complete list of product category IDs (including ancestors).
	 *
	 * @param \WC_Product|int $product Checks for product parent only if object passed
	 * @param bool $bypass_multilang
	 *
	 * @return array
	 */
	public static function get_all_product_category_ids($product, $bypass_multilang = true)
	{
		if ($product instanceof \WC_Product) {
			$product_id = $product->get_parent_id() ?: $product->get_id();
		} else {
			$product_id = (int)$product;
		}

		if (!isset(self::$categories[$product_id])) {
			if ($bypass_multilang) {
				Multilang::toggle_term_filters(false);
			}

			$categories = wc_get_product_term_ids($product_id, 'product_cat');

			if ($categories) {
				$ancestors = [];

				foreach ($categories as $cat_id) {
					$ancestors[] = get_ancestors($cat_id, 'product_cat', 'taxonomy');
				}

				self::$categories[$product_id] = array_merge($categories, ...$ancestors);
			} else {
				self::$categories[$product_id] = [];
			}

			if ($bypass_multilang) {
				Multilang::toggle_term_filters(true);
			}
		}

		return self::$categories[$product_id];
	}

	/**
	 * @param \WP_Term|int $category
	 *
	 * @return \WP_Term[]|\WP_Error|null
	 */
	public static function get_category_ancestry($category)
	{
		if (!$category instanceof \WP_Term) {
			$category = get_term($category, 'product_cat');
		}

		if (!$category || is_wp_error($category)) {
			return $category;
		}

		$tree[] = $category;

		while ($category->parent > 0 && ($category = get_term($category->parent, 'product_cat')) && !is_wp_error($category)) {
			$tree[] = $category;
		}

		return $tree;
	}

	/**
	 * @param \WP_Term|int $category
	 * @param string $sep
	 * @param bool|string $compress
	 *
	 * @return string|\WP_Error|null
	 */
	public static function get_category_tree_label($category, $sep = ' > ', $compress = false)
	{
		$ancestry = self::get_category_ancestry($category);

		if (!$ancestry || is_wp_error($ancestry)) {
			return $ancestry;
		}

		$count = count($ancestry);

		if ($count === 1) {
			$label = $ancestry[0]->name;
		} elseif ($count === 2) {
			$label = $ancestry[1]->name . $sep . $ancestry[0]->name;
		} elseif ($compress) {
			if (is_string($compress)) {
				$sep = $compress;
			}

			$label = end($ancestry)->name . $sep . $ancestry[0]->name;
		} else {
			$label = [];

			foreach (array_reverse($ancestry) as $category) {
				$label[] = $category->name;
			}

			$label = implode($sep, $label);
		}

		return $label;
	}

	/**
	 * @return string[]
	 */
	public static function get_product_types()
	{
		static $product_types;

		if ($product_types === null) {
			$product_types = wc_get_product_types();

			unset($product_types['grouped'], $product_types['external']);

			// remove "product" from product type label (at least for English)
			foreach ($product_types as &$product_type) {
				$product_type = trim(str_replace('product', '', $product_type));
			}
		}

		return $product_types;
	}

	/**
	 * @param \WC_Product $product
	 *
	 * @return string
	 */
	public static function get_formatted_product_name($product)
	{
		$name = $product->get_name();

		if (!$product instanceof \WC_Product_Variation) {
			return $name;
		}

		$sku = $product->get_sku('edit');

		if ($sku !== '') {
			$name .= " ($sku)";
		} else {
			$variation = wc_get_formatted_variation($product, true, true, true);
			$name .= $variation !== '' ? " ($variation)" : ' (#' . $product->get_id() . ')';
		}

		return $name;
	}

	/**
	 * @param \WC_Product|int $product
	 *
	 * @return \WC_Product|int Returns the same type that was provided
	 */
	public static function bypass_multilang_product($product)
	{
		$is_object = $product instanceof \WC_Product;
		$product_id = $is_object ? $product->get_id() : (int)$product;
		$translated_id = Multilang::get_translated_object_id($product_id, 'post', 'product', 'default');

		if ($translated_id === $product_id) {
			return $product;
		} elseif ($is_object) {
			return wc_get_product($translated_id);
		} else {
			return $translated_id;
		}
	}

	/**
	 * @param \WC_Product $product
	 * @param string $method
	 *
	 * @return mixed
	 */
	public static function without_limits($product, $method)
	{
		self::incr_prop($product, 'bypass_limits');
		$value = $product->$method();
		self::decr_prop($product, 'bypass_limits');
		return $value;
	}

	/**
	 * @param \WC_Product $product
	 * @param array<string, string>|null $variation
	 */
	public static function set_variation($product, $variation)
	{
		$current = self::get_prop($product, 'variation');

		if ($current !== $variation) {
			self::set_prop($product, 'variation', $variation);

			// clear stock limit prop cache whenever variation prop changes
			if (self::has_prop($product, 'stock_limit')) {
				self::set_prop($product, 'stock_limit', null);
			}
		}
	}

	/**
	 * @param object $product
	 * @param string $key
	 * @param mixed $value
	 */
	public static function set_prop($product, $key, $value)
	{
		if (self::$propmap === null) {
			$product->{"mewz_wcas_$key"} = $value;
		} else {
			if (isset(self::$propmap[$product])) {
				self::$propmap[$product][$key] = $value;
			} else {
				self::$propmap[$product] = [$key => $value];
			}
		}
	}

	/**
	 * @param object $product
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public static function get_prop($product, $key, $default = null)
	{
		if (self::$propmap === null) {
			$prop = "mewz_wcas_$key";
			return property_exists($product, $prop) && $product->$prop !== null ? $product->$prop : $default;
		} else {
			return isset(self::$propmap[$product][$key]) ? self::$propmap[$product][$key] : $default;
		}
	}

	/**
	 * @param object $product
	 * @param string $key
	 *
	 * @return bool
	 */
	public static function has_prop($product, $key)
	{
		if (self::$propmap === null) {
			$prop = "mewz_wcas_$key";
			return property_exists($product, $prop) && $product->$prop !== null;
		} else {
			return isset(self::$propmap[$product][$key]);
		}
	}

	/**
	 * @param object $product
	 * @param string $key
	 * @param int $amount
	 *
	 * @return int
	 */
	public static function incr_prop($product, $key, $amount = 1)
	{
		if (self::$propmap === null) {
			$prop = "mewz_wcas_$key";

			if (property_exists($product, $prop)) {
				$amount += (int)$product->$prop;
			}

			$product->$prop = $amount;
		} else {
			if (!isset(self::$propmap[$product])) {
				self::$propmap[$product] = [$key => $amount];
			} else {
				if (isset(self::$propmap[$product][$key])) {
					$amount += self::$propmap[$product][$key];
				}

				self::$propmap[$product][$key] = $amount;
			}
		}

		return $amount;
	}

	/**
	 * @param object $product
	 * @param string $key
	 * @param int $amount
	 *
	 * @return int
	 */
	public static function decr_prop($product, $key, $amount = 1)
	{
		return self::incr_prop($product, $key, -$amount);
	}
}

if (PHP_VERSION_ID >= 80200) {
	Products::$propmap = new \WeakMap();
}
