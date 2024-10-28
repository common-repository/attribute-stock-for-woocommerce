<?php
namespace Mewz\WCAS\Util;

use Mewz\Framework\Util\Number;
use Mewz\QueryBuilder\DB;
use Mewz\WCAS\Models\AttributeStock;

class Export
{
	const FIELDS = [
		'title' => '',
		'sku' => '',
		'quantity' => '',
		'low_stock' => '',
		'enabled' => '',
		'internal' => '',
		'multiplex' => '',
		'lock_multipliers' => '',
		'product_sku' => '',
		'product_image' => '',
		'image_id' => '',
		'components' => '',
		'products' => '',
		'exclude_products' => '',
		'categories' => '',
		'exclude_categories' => '',
		'product_types' => '',
		'match_rules' => '',
		'tags' => '',
		'notes' => '',
	];

	public static function to_csv_download(array $stock_ids)
	{
		$filename = 'attribute-stock-' . current_time('Y-m-d-His') . '.csv';
		$filename = apply_filters('mewz_wcas_export_filename', $filename, $stock_ids);

		$fields = apply_filters('mewz_wcas_export_fields', self::FIELDS, $stock_ids);

		// if headers have already been sent, there's not much else we can do here
		if (headers_sent()) {
			wp_die(__('Request headers have already been sent. This usually happens when PHP warnings or notices are being displayed. Please fix these and try again.', 'woocommerce-attribute-stock'));
		}

		$out = fopen('php://output', 'w');

		if (!$out) {
			wp_die(__('Writing to PHP\'s output stream (php://output) has been disabled. Unable to output CSV file download.', 'woocommerce-attribute-stock'));
		}

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Pragma: no-cache');

		fputcsv($out, array_keys($fields));

		foreach ($stock_ids as $stock_id) {
			if ($row = self::build_export_row($fields, $stock_id)) {
				fputcsv($out, $row);
			}
		}

		die;
	}

	/**
	 * @param array $row
	 * @param int $stock_id
	 *
	 * @return mixed
	 */
	public static function build_export_row(array $row, $stock_id)
	{
		$stock = new AttributeStock($stock_id, 'edit');

		$props = array_diff(array_keys($row), [
			'components',
			'products',
			'exclude_products',
			'categories',
			'exclude_categories',
			'match_rules',
			'tags',
		]);

		foreach ($props as $prop) {
			if (!method_exists($stock, $prop)) {
				continue;
			}

			$value = $stock->$prop();

			if (is_array($value)) {
				$row[$prop] = implode(', ', $value);
			} elseif (is_bool($value)) {
				$row[$prop] = $value ? 'yes' : 'no';
			} elseif (is_float($value)) {
				$row[$prop] = Number::safe_decimal($value);
			} else {
				$row[$prop] = (string)$value;
			}
		}

		if (isset($row['components'])) {
			$components = $stock->components();
			!empty($components['child']) && $row['components'] = self::build_component_list($components['child']);
		}

		isset($row['products']) && $row['products'] = self::build_product_list($stock->products());
		isset($row['exclude_products']) && $row['exclude_products'] = self::build_product_list($stock->exclude_products());

		isset($row['categories']) && $row['categories'] = self::build_category_list($stock->categories());
		isset($row['exclude_categories']) && $row['exclude_categories'] = self::build_category_list($stock->exclude_categories());

		isset($row['match_rules']) && $row['match_rules'] = self::build_match_rules_export_data($stock);

		isset($row['tags']) && $row['tags'] = implode(', ', get_terms([
			'taxonomy' => 'product_tag',
			'object_ids' => $stock_id,
			'fields' => 'names',
			'orderby' => 'name',
		]));

		return apply_filters('mewz_wcas_export_row', $row, $stock_id, $stock);
	}

	public static function build_component_list($components)
	{
		$list = [];

		foreach ($components as $stock_id => $quantity) {
			$stock = AttributeStock::instance($stock_id, 'edit');
			if (!$stock->valid()) continue;

			$ident = $stock->sku();

			if (!$ident) {
				$ident = $stock->id() . '-' . $stock->slug();
			}

			if ($quantity !== '' && $quantity != 1) {
				$ident .= " ($quantity)";
			}

			$list[] = $ident;
		}

		return implode(', ', $list);
	}

	public static function build_product_list($product_ids)
	{
		$list = [];

		foreach ($product_ids as $product_id) {
			$product = wc_get_product($product_id);
			if (!$product) continue;

			$ident = $product->get_sku('edit');

			if (!$ident) {
				$ident = $product->get_id() . '-' . $product->get_slug('edit');
			}

			$list[] = $ident;
		}

		return implode(', ', $list);
	}

	public static function build_category_list($category_ids)
	{
		$list = [];

		foreach ($category_ids as $category_id) {
			$category = get_term($category_id);

			if (!$category || is_wp_error($category)) {
				continue;
			}

			$list[] = $category->slug;
		}

		return implode(', ', $list);
	}

	/**
	 * @param AttributeStock $stock
	 *
	 * @return string
	 */
	public static function build_match_rules_export_data(AttributeStock $stock)
	{
		$match_rules = $stock->match_rules();
		$attributes = Attributes::get_attributes();

		$lines = [];

		foreach ($match_rules as $rule) {
			$line_attr = [];

			foreach ($rule['attributes'] as $attr_id => $term_ids) {
				if (!isset($attributes[$attr_id])) {
					continue;
				}

				if ($term_ids) {
					$term_slugs = [];

					foreach ($term_ids as $term_id) {
						$term = get_term($term_id);

						if (!$term || is_wp_error($term)) {
							continue;
						}

						$term_slugs[] = $term->slug;
					}

					$terms = implode('|', $term_slugs);
				} else {
					$terms = '*';
				}

				$line_attr[] = $attributes[$attr_id]->name . ': ' . $terms;
			}

			$line = implode(', ', $line_attr);

			if ($line) {
				if ($rule['multiplier'] !== '' && $rule['multiplier'] != 1) {
					$line .= ' (' . $rule['multiplier'] . ')';
				}

				$lines[] = $line;
			}
		}

		$lines = implode("\n", $lines);

		return $lines;
	}

	/**
	 * @param AttributeStock $stock
	 *
	 * @return array
	 */
	public static function build_filters_export_data($stock)
	{
		$filters = [];

		$product_filters = [
			'incl' => $stock->products(),
			'excl' => $stock->exclude_products(),
		];

		foreach ($product_filters as $type => $product_ids) {
		    foreach ($product_ids as $product_id) {
		    	$sku = get_post_meta($product_id, '_sku', true);
			    $filters['products'][$type][] = strlen($sku) ? $sku : (int)$product_id;
		    }
		}

		$category_filters = [
			'incl' => $stock->categories(),
			'excl' => $stock->exclude_categories(),
		];

		foreach ($category_filters as $type => $cat_ids) {
			foreach ($cat_ids as $cat_id) {
				$term = get_term($cat_id);

				if (!$term || is_wp_error($term)) {
					continue;
				}

				$filters['categories'][$type][] = $term->slug;
			}
		}

	    if ($product_types = $stock->product_types()) {
		    $filters['product_types'] = $product_types;
	    }

		return $filters;
	}

	public static function import_row($row, $exclude_match_ids = null)
	{
		$row = apply_filters('mewz_wcas_import_row', $row);
		if (!$row) return false;

		$data = [];
		$match_rules = null;

		foreach ($row as $key => $value) {
			if ($value === '' || in_array($key, ['components', 'match_rules', 'tags'], true)) {
				continue;
			}

			if (self::is_unset_value($value)) {
				$value = '';

				if (in_array($key, ['title', 'sku'])) {
					continue;
				}
			}

			$data[$key] = $value;
		}

		if (!empty($row['match_rules'])) {
			if (self::is_unset_value($row['match_rules'])) {
				$match_rules = [];
			} elseif ($row['match_rules'][0] === '[' && $match_rules = json_decode($row['match_rules'], true)) {
				$match_rules = self::find_match_rules($match_rules);
			} elseif ($rule_data = self::parse_match_rules($row['match_rules'])) {
				$match_rules = self::find_match_rules($rule_data);
			}
		}

		if (isset($data['sku']) || isset($data['title'])) {
			$stock = self::match_stock_item($data, $exclude_match_ids);
			$update = $stock->valid();

			if (!$update && !isset($data['title'])) {
				$data['title'] = $data['sku'];
			}
		} elseif ($match_rules) {
			$data['title'] = self::build_title_from_match_rules($match_rules);
			$stock = new AttributeStock(null, 'edit');
			$update = false;
		} else {
			// no identifier fields to import
			return false;
		}

		$bool_keys = [
			'enabled',
			'internal',
			'multiplex',
			'lock_multipliers',
			'product_sku',
			'product_image',
		];

		foreach ($bool_keys as $key) {
			if (isset($data[$key])) {
				$data[$key] = wc_string_to_bool($data[$key]);
			}
		}

		isset($data['products']) && $data['products'] = self::match_products($data['products']);
		isset($data['exclude_products']) && $data['exclude_products'] = self::match_products($data['exclude_products']);
		isset($data['categories']) && $data['categories'] = self::match_categories($data['categories']);
		isset($data['exclude_categories']) && $data['exclude_categories'] = self::match_categories($data['exclude_categories']);
		isset($data['product_types']) && $data['product_types'] = self::list_to_array($data['product_types']);

		$data = apply_filters('mewz_wcas_import_data', $data, $stock, $row);

		$stock->bind($data);

		if ($stock->save() === false) {
			return false;
		}

		if (is_array($match_rules)) {
			$stock->save_match_rules($match_rules);
		}

		if (!empty($row['components'])) {
			if (self::is_unset_value($row['components'])) {
				$stock->save_components(['child' => false]);
			} elseif ($components = self::match_components($row['components'])) {
				$stock->save_components(['child' => $components]);
			}
		}

		if (!empty($row['tags'])) {
			if (self::is_unset_value($row['tags'])) {
				wp_delete_object_term_relationships($stock->id(), 'product_tag');
			} else {
				$tag_ids = [];

				foreach (self::list_to_array($row['tags']) as $tag) {
					$tag = wp_create_term($tag, 'product_tag');
					$tag_ids[] = (int)$tag['term_id'];
				}

				if ($tag_ids) {
					wp_set_object_terms($stock->id(), $tag_ids, 'product_tag');
				}
			}
		}

		do_action('mewz_wcas_imported_row', $row, $stock, $update);

		return [
			'stock' => $stock,
			'action' => $update ? 'updated' : 'added',
		];
	}

	public static function match_stock_item($data, $exclude_ids = null)
	{
		$query = DB::table('posts', 'p')
			->where('p.post_type', AttributeStock::POST_TYPE)
			->where('p.post_status', ['publish', 'draft']);

		if ($exclude_ids) {
			$query->where_not('p.ID', $exclude_ids);
		}

		if (isset($data['sku'])) {
			$query->left_join('postmeta', 'sku')->on("sku.post_id = p.ID AND sku.meta_key = '_sku'");
		}

		if (isset($data['sku'], $data['title'])) {
			$query->where('sku.meta_value = ? OR p.post_title = ?', $data['sku'], $data['title']);
		}
		elseif (isset($data['sku'])) {
			$query->where('sku.meta_value', $data['sku']);
		}
		elseif (isset($data['title'])) {
			$query->where('p.post_title', $data['title']);
		}

		$stock_id = $query->var('p.ID') ?: null;
		$stock_id = apply_filters('mewz_wcas_import_match_stock_id', $stock_id, $data);

		return new AttributeStock($stock_id, 'edit');
	}

	public static function match_components($data)
	{
		preg_match_all('/\s*([^,]+?)\s*(?>\(([\d.]+)\))?\s*(?=,|$)/m', $data, $matches);

		if (empty($matches[1])) {
			return [];
		}

		$components = [];

		foreach ($matches[1] as $i => $ident) {
			$stock_id = AttributeStock::find(['meta_key' => '_sku', 'meta_value' => $ident], 'edit', 'id');

			if (!$stock_id) {
				$post = get_page_by_path($ident, OBJECT, AttributeStock::POST_TYPE);

				if ($post) {
					$stock_id = $post->ID;
				}
			}

			if (!$stock_id) {
				$parts = explode('-', $ident, 2);

				if ($parts[0] === (string)(int)$parts[0]) {
					if (isset($parts[1]) && strlen($parts[1])) {
						$post = get_page_by_path($parts[1], OBJECT, AttributeStock::POST_TYPE);

						if ($post) {
							$stock_id = $post->ID;
						}
					}

					if (!$stock_id && get_post_type($parts[0]) === AttributeStock::POST_TYPE) {
						$stock_id = (int)$parts[0];
					}
				}
			}

			if ($stock_id > 0) {
				$quantity = $matches[2][$i] ?? '';
				$components[$stock_id] = $quantity;
			}
		}

		return $components;
	}

	public static function match_products($list)
	{
		if (!$list) return [];

		$idents = self::list_to_array($list);
		$product_ids = [];

		foreach ($idents as $ident) {
			if (!$ident) continue;

			$product_id = wc_get_product_id_by_sku($ident);

			if (!$product_id) {
				$post = get_page_by_path($ident, OBJECT, 'product') ?: get_page_by_path($ident, OBJECT, 'product_variation');

				if ($post) {
					$product_id = $post->ID;
				}
			}

			if (!$product_id) {
				$parts = explode('-', $ident, 2);

				if ($parts[0] === (string)(int)$parts[0]) {
					if (isset($parts[1]) && strlen($parts[1])) {
						$post = get_page_by_path($parts[1], OBJECT, 'product') ?: get_page_by_path($parts[1], OBJECT, 'product_variation');

						if ($post) {
							$product_id = $post->ID;
						}
					}

					if (!$product_id && in_array(get_post_type($parts[0]), ['product', 'product_variation'])) {
						$product_id = (int)$parts[0];
					}
				}
			}

			if ($product_id > 0) {
				$product_ids[] = $product_id;
			}
		}

		return $product_ids;
	}

	public static function match_categories($list)
	{
		if (!$list) return [];

		$cat_slugs = self::list_to_array($list);
		$cat_ids = [];

		foreach ($cat_slugs as $cat_slug) {
			if (!$cat_slug) continue;

		    $cat_term = get_term_by('slug', $cat_slug, 'product_cat');

		    if ($cat_term && !is_wp_error($cat_term)) {
			    $cat_ids[] = $cat_term->term_id;
		    }
		}

		return $cat_ids;
	}

	public static function parse_match_rules($data)
	{
		$lines = preg_split('/[\n\r\/]/', strtolower(str_replace([' ',')'], '', $data)));
		$match_rules = [];

		foreach ($lines as $line) {
			if (!$line) continue;

			$rule = ['attr' => []];

			if ($p = strrpos($line, '(')) {
				$x = substr($line, $p + 1);
				$line = substr($line, 0, $p);

				if (is_numeric($x) && $x != 1) {
					$rule['x'] = (float)$x;
				}
			}

			if ($line) {
				foreach (explode(',', $line) as $attr) {
					$attr = explode(':', $attr, 2);

					if (count($attr) !== 2) {
						continue;
					}

					if ($attr[1] === '*') {
						$attr[1] = '';
					}

					$rule['attr'][] = $attr;
				}
			}

			$match_rules[] = $rule;
		}

		return $match_rules;
	}

	public static function find_match_rules($rule_data)
	{
		$match_rules = [];

		foreach ($rule_data as $line) {
			$match_rule = [];

			foreach ($line['attr'] as $attr) {
				$attr_id = Attributes::get_attribute_id($attr[0]);
				if (!$attr_id) continue;

				$taxonomy = Attributes::get_attribute_name($attr_id, true);
				$term_ids = [];

				if ($attr[1] !== '') {
					foreach (explode('|', $attr[1]) as $term_slug) {
						$term = get_term_by('slug', $term_slug, $taxonomy);

						if ($term && !is_wp_error($term)) {
							$term_ids[] = $term->term_id;
						}
					}
				}

				$match_rule['attributes'][$attr_id] = $term_ids;
			}

			if (isset($line['x'])) {
				$match_rule['multiplier'] = $line['x'];
			}

			if ($match_rule) {
				$match_rules[] = $match_rule;
			}
		}

		return $match_rules;
	}

	public static function build_title_from_match_rules($match_rules)
	{
		$attributes = [];

		foreach ($match_rules as $rule) {
		    foreach ($rule['attributes'] as $attr_id => $term_ids) {
			    if (!isset($attributes[$attr_id])) {
				    $attributes[$attr_id] = [];
				}

				foreach ($term_ids as $term_id) {
					$attributes[$attr_id][] = $term_id;
				}
		    }
		}

		$title = [];

		foreach ($attributes as $attr_id => $term_ids) {
			$title_attr = Attributes::get_attribute_label($attr_id);
			$taxonomy = Attributes::get_attribute_name($attr_id, true);

			if ($term_ids) {
				$term_ids = array_keys(array_flip($term_ids));
				$title_terms = [];

				foreach ($term_ids as $term_id) {
					$term = get_term($term_id, $taxonomy);
					$title_terms[] = $term->name;
				}

				$title[] = $title_attr . ': ' . implode('|', $title_terms);
			} else {
				$title[] = $title_attr;
			}
		}

		return implode(', ', $title);
	}

	public static function list_to_array($list)
	{
		$items = [];

		foreach (explode(',', $list) as $item) {
			$item = trim($item);

			if ($item !== '') {
				$items[] = $item;
			}
		}

		return $items;
	}

	public static function is_unset_value($value)
	{
	    return in_array($value, ['[]', '()']);
	}
}
