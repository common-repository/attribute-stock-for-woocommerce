<?php
namespace Mewz\WCAS\Util;

use Mewz\Framework\Util\Multilang;
use Mewz\QueryBuilder\DB;
use Mewz\WCAS\Models\Attribute;

class Attributes
{
	public static $list;
	public static $multipliers;

	/**
	 * @param bool $refresh
	 *
	 * @return Attribute[]
	 */
	public static function get_attributes($refresh = false)
	{
		if (self::$list === null || $refresh) {
			$attribute_list = wc_get_attribute_taxonomies();
			self::$list = [];

			foreach ($attribute_list as $attribute) {
				self::$list[$attribute->attribute_id] = Attribute::from_result($attribute);
			}
		}

		return self::$list;
	}

	/**
	 * @param int|string $attribute
	 *
	 * @return Attribute|false
	 */
	public static function get_attribute($attribute)
	{
		$attribute_id = self::get_attribute_id($attribute);
		if (!$attribute_id) return false;

		return self::get_attributes()[$attribute_id] ?? false;
	}

	public static function get_attribute_id($attribute)
	{
		if (is_string($attribute) && $attribute !== (string)(int)$attribute) {
			$attributes = self::get_attributes();

			foreach ($attributes as $attr) {
			    if (in_array($attribute, [$attr->name, $attr->taxonomy])) {
			    	return $attr->id;
			    }
			}

			// handle CJK characters
			$sanitized = wc_sanitize_taxonomy_name($attribute);

			if ($sanitized !== $attribute) {
				foreach ($attributes as $attr) {
					if (in_array($sanitized, [$attr->name, $attr->taxonomy])) {
						return $attr->id;
					}
				}
			}

			return false;
		}

		return (int)$attribute;
	}

	public static function get_attribute_name($attribute, $get_taxonomy = false)
	{
		if (is_string($attribute) && $attribute !== (string)(int)$attribute) {
			$attribute = wc_sanitize_taxonomy_name($attribute);
			$is_taxonomy = strpos($attribute, 'pa_') === 0;

			if ($get_taxonomy == $is_taxonomy) {
				return $attribute;
			} else {
				return $get_taxonomy ? 'pa_' . $attribute : substr($attribute, 3);
			}
		} else {
			$attributes = self::get_attributes();

			if (!isset($attributes[$attribute])) {
				return false;
			}

			if ($get_taxonomy) {
				return $attributes[$attribute]->taxonomy;
			} else {
				return $attributes[$attribute]->name;
			}
		}
	}

	public static function get_attribute_label($attribute)
	{
		$taxonomy = self::get_attribute_name($attribute, true);
		if (!$taxonomy) return false;

		return wc_attribute_label($taxonomy);
	}

	public static function get_display_label($label, $slug = null)
	{
		if ($slug === null) {
			return html_entity_decode($label);
		} elseif ($label === '') {
			return "($slug)";
		} else {
			$label = html_entity_decode($label);

			if ($label === $slug) {
				return $label;
			} else {
				return "$label ($slug)";
			}
		}
	}

	/**
	 * @param array $attributes
	 * @param bool $bypass_multilang
	 *
	 * @return array<int, int[]>
	 */
	public static function get_attribute_id_sets($attributes, $bypass_multilang = true)
	{
		if (!$attributes) return [];

		$bypass_multilang = $bypass_multilang && Multilang::active();

		if ($bypass_multilang) {
			Multilang::toggle_term_filters(false);
		}

		$sets = [];

		foreach ($attributes as $attribute => $terms) {
			$attribute = self::get_attribute($attribute);
			if (!$attribute) continue;

			if ($terms) {
				foreach ((array)$terms as $term) {
					if (!$term) {
						$term_id = 0;
					} elseif (is_string($term)) {
						$term = get_term_by('slug', $term, $attribute->taxonomy);
						$term_id = $term ? $term->term_id : 0;
					} else {
						$term_id = (int)$term;
					}

					if ($bypass_multilang && $term_id > 0) {
						$term_id = Multilang::get_translated_object_id($term_id, 'term', $attribute->taxonomy, 'default');
					}

					$sets[$attribute->id][$term_id] = $term_id;
				}

				if (!empty($sets[$attribute->id])) {
					sort($sets[$attribute->id]);
				}
			} else {
				$sets[$attribute->id][] = 0;
			}
		}

		if ($bypass_multilang) {
			Multilang::toggle_term_filters(true);
		}

		ksort($sets);

		return $sets;
	}

	public static function get_used_attribute_options($post_status = null)
	{
		static $cache = [];

		$cache_key = is_array($post_status) ? implode(',', $post_status) : ($post_status ?: 'all');

		if (isset($cache[$cache_key])) {
			return $cache[$cache_key];
		}

		Multilang::toggle_term_filters(false);

		$attributes = self::get_attributes();
		$stock_attributes = Matches::get_all_stock_attributes($post_status);
		$term_options = self::get_term_options($attributes, $stock_attributes);
		$show_names = self::find_irregular_names($attributes, 'label');
		$any_term = [0, '"' . __('Any', 'woocommerce-attribute-stock') . '"'];
		$attribute_options = [];

		foreach ($stock_attributes as $attr_id => $term_ids) {
			if (!isset($attributes[$attr_id])) {
				continue;
			}

			$attr = $attributes[$attr_id];
			$display_name = isset($show_names[$attr->label]) ? $attr->name : null;
			$label = self::get_display_label($attr->label, $display_name);
			$terms = $term_options[$attr->taxonomy] ?? [];

			if (isset($term_ids[0])) {
				$terms[] = $any_term;
			}

			$attribute_options[$attr_id] = [
				'label' => $label,
				'terms' => $terms,
			];
		}

		Multilang::toggle_term_filters(true);

		uasort($attribute_options, fn($a, $b) => strnatcasecmp($a['label'], $b['label']));

		$cache[$cache_key] = $attribute_options;

		return $attribute_options;
	}

	/**
	 * @param Attribute|Attribute[] $attributes
	 * @param array<int, int[]> $filter_ids
	 *
	 * @return array|void
	 */
	public static function get_term_options($attributes, $filter_ids = null)
	{
		if (!is_array($attributes)) {
			$attributes = [$attributes];
		}

		$attribute_groups = [];

		foreach ($attributes as $attr) {
			if ($filter_ids && !isset($filter_ids[$attr->id])) {
				continue;
			}

			$attribute_groups[$attr->orderby]['taxonomies'][] = $attr->taxonomy;

			if ($filter_ids) {
				$attribute_groups[$attr->orderby]['term_ids'][] = $filter_ids[$attr->id];
			}
		}

		$term_options = [];

		foreach ($attribute_groups as $orderby => $group) {
			$args = [
				'taxonomy' => $group['taxonomies'],
				'orderby' => $orderby,
				'hide_empty' => false,
				'update_term_meta_cache' => false,
			];

			if (isset($group['term_ids'])) {
				$args['include'] = array_merge(...$group['term_ids']);
			}

			$term_results = get_terms($args);
			if (!$term_results) continue;

			$show_slugs = self::find_irregular_names($term_results, 'name', 'taxonomy');

			/** @var \WP_Term $term */
			foreach ($term_results as $term) {
				$display_slug = isset($show_slugs[$term->taxonomy][$term->name]) ? $term->slug : null;
				$label = self::get_display_label($term->name, $display_slug);

				$term_options[$term->taxonomy][] = [$term->term_id, $label];
			}
		}

		return $term_options;
	}

	public static function get_term_multipliers()
	{
		global $wpdb;

		if (is_array(self::$multipliers)) {
			return self::$multipliers;
	    }

		$cache_key = 'term_multipliers';
		$cache = Mewz_WCAS()->cache->get($cache_key);

		if (is_array($cache)) {
			return self::$multipliers = $cache;
		}

		$query = "
			SELECT tt.taxonomy, tm.term_id, 0+tm.meta_value multiplier
			FROM {$wpdb->termmeta} tm
			LEFT JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = tm.term_id
			WHERE tm.meta_key = 'mewz_wcas_multiplier'
		      AND tm.meta_value != ''
			ORDER BY tt.taxonomy, multiplier
		";

		$results = $wpdb->get_results($query);
		$multipliers = [];

		foreach ($results as $row) {
			$multipliers[$row->taxonomy][$row->term_id] = (float)$row->multiplier;
		}

		$multipliers = apply_filters('mewz_wcas_term_multipliers', $multipliers);

		if (!is_array($multipliers)) {
			$multipliers = [];
		}

		Mewz_WCAS()->cache->set($cache_key, $multipliers);

		return self::$multipliers = $multipliers;
	}

	/**
	 * @param int[] $rule_ids
	 * @param array<int, int[]> $attribute_id_sets
	 *
	 * @return array<int, int>
	 */
	public static function match_rule_multipliers($rule_ids, $attribute_id_sets)
	{
		global $wpdb;

		$term_multipliers = self::get_term_multipliers();
		if (!$term_multipliers) return [];

		$rule_attr_table = DB::prefix(Matches::ATTR_TABLE);
		$attributes_table = DB::prefix('woocommerce_attribute_taxonomies');

		$rule_ids = implode(',', $rule_ids);

		$query = "
			SELECT r.rule_id, r.attribute_id, r.term_id
			FROM {$rule_attr_table} r
			JOIN {$attributes_table} a ON a.attribute_id = r.attribute_id
			WHERE r.rule_id IN ($rule_ids)
			ORDER BY a.attribute_name
		";

		$results = $wpdb->get_results($query);
		if (!$results) return [];

		$multipliers = [];

		foreach ($results as $row) {
			if (isset($multipliers[$row->rule_id])) {
				continue;
			}

			$attr_id = (int)$row->attribute_id;

			if (empty($attribute_id_sets[$attr_id])) {
				continue;
			}

			$taxonomy = self::get_attribute_name($attr_id, true);
			$row->term_id = (int)$row->term_id;

			foreach ($attribute_id_sets[$attr_id] as $term_id) {
				if ($row->term_id > 0 && (int)$term_id !== $row->term_id) {
					continue;
				}

				if (isset($term_multipliers[$taxonomy][$term_id])) {
					$multipliers[$row->rule_id] = $term_multipliers[$taxonomy][$term_id];
				}
			}
		}

		return $multipliers;
	}

	/**
	 * @param array<string, mixed> $attributes [taxonomy => [terms]]
	 * @param bool $match_range Return a min-max range if a matching attribute has multiple terms
	 * @param T $default
	 *
	 * @template T
	 * @return float|float[]|T
	 */
	public static function match_term_multiplier($attributes, $match_range = false, $default = 1.00)
	{
		if (!$attributes) return $default;

		$term_multipliers = self::get_term_multipliers();
		if (!$term_multipliers) return $default;

		foreach ($attributes as $taxonomy => $terms) {
			if (!$terms) continue;

			$multipliers = [];
			$has_unmatched = false;

			foreach ((array)$terms as $term) {
				if (is_string($term)) {
					$term = get_term_by('slug', $term, $taxonomy);
					if (!$term) continue;
					$term = $term->term_id;
				}

				if (isset($term_multipliers[$taxonomy][$term])) {
					$multiplier = $term_multipliers[$taxonomy][$term];

					if ($match_range) {
						$multipliers[] = $multiplier;
					} else {
						return $multiplier;
					}
				} else {
					$has_unmatched = true;
				}
			}

			if ($multipliers) {
				if ($has_unmatched) {
					$multipliers[] = 1;
				}

				$range = [
					min($multipliers),
					max($multipliers),
				];

				return $range[0] === $range[1] ? $range[0] : $range;
			}
		}

		return $default;
	}

	public static function get_term_prop($term_id, $taxonomy, $prop)
	{
		static $cache = [];

		if (isset($cache[$taxonomy][$term_id][$prop])) {
			return $cache[$taxonomy][$term_id][$prop];
		}

		$term = get_term($term_id, $taxonomy);

		if (!$term || $term instanceof \WP_Error) {
			return $cache[$taxonomy][$term_id][$prop] = false;
		}

		return $cache[$taxonomy][$term_id][$prop] = $term->$prop;
	}

	public static function has_catchall(array $attributes)
	{
		foreach ($attributes as $term) {
			if ($term === '' || $term === 0) {
				return true;
			}
		}

		return false;
	}

	public static function strip_attribute_prefix($attributes)
	{
		if (empty($attributes) || strpos(key($attributes), 'attribute_') !== 0) {
			return $attributes;
		}

		$stripped = [];

		foreach ($attributes as $attr => $term) {
			$stripped[substr($attr, 10)] = $term;
		}

	    return $stripped;
	}

	public static function get_request_attributes()
	{
		if (empty($_REQUEST)) {
			return false;
		}

		$attributes = [];

		foreach ($_REQUEST as $key => $value) {
			if (strpos($key, 'attribute_') === 0) {
				$attributes[substr($key, 10)] = stripslashes($value);
			}
		}

		return $attributes;
	}

	public static function decode_keys(array $attributes)
	{
	    $decoded = [];

		foreach ($attributes as $key => $value) {
			$decoded[wc_sanitize_taxonomy_name($key)] = $value;
		}

		return $decoded;
	}

	public static function encode_keys(array $attributes)
	{
		$encoded = [];

		foreach ($attributes as $key => $value) {
			$encoded[sanitize_title($key)] = $value;
		}

		return $encoded;
	}

	public static function sluggify_attributes($attributes)
	{
		$slugged = [];

		foreach ($attributes as $taxonomy => $terms) {
			if (!$terms) continue;

			$terms = (array)$terms;

			if (is_int($taxonomy)) {
				$taxonomy = self::get_attribute_name($taxonomy, true);
			}

			$taxonomy = wc_sanitize_taxonomy_name($taxonomy);

			foreach ($terms as $term) {
				if (is_string($term)) {
					$slugged[$taxonomy][] = $term;
				} else {
					$slug = self::get_term_prop($term, $taxonomy, 'slug');

					if ($slug !== false) {
						$slugged[$taxonomy][] = $slug;
					}
				}
			}
		}

		return $slugged;
	}

	public static function find_irregular_names(array $items, $name_key = 'name', $group_key = null)
	{
		if (!$items) return [];

		$is_object = is_object(current($items));
		$names = [];
		$irregular = [];

		foreach ($items as $item) {
			$name = $is_object ? $item->$name_key : $item[$name_key];
			$group = $group_key ? ($is_object ? $item->$group_key : $item[$group_key]) : 0;

			if ($name === '' || isset($names[$group][$name])) {
				$irregular[$group][$name] = true;
			}

			$names[$group][$name] = true;
		}

		if (!$irregular) {
			return $irregular;
		}

		return $group_key ? $irregular : $irregular[0];
	}
}
