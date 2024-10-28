<?php
namespace Mewz\Framework\Util;

class Admin
{
	/**
	 * Simple helper for admin redirects with additional url params (e.g. for notice messages).
	 *
	 * @param string $url
	 * @param string|array $params
	 */
	public static function redirect($url, $params = null)
	{
		if (strpos($url, 'http') !== 0) {
			$url = admin_url($url);
		}

		if ($params) {
			if (is_array($params)) {
				$params = http_build_query($params);
			}

			$sep = strpos($url, '?') === false ? '?' : '&';
			$url .= $sep . $params;
		}

		wp_redirect($url); die;
	}

	/**
	 * Render an optionally dismissable admin notice.
	 *
	 * @param string $type
	 * @param string $message
	 * @param bool $dismissible
	 * @param bool $is_html
	 * @param string $class
	 *
	 * @return string
	 */
	public static function render_notice($type, $message, $dismissible = true, $is_html = false, $class = '')
	{
		$class = 'notice notice-' . $type . ($class ? ' ' . $class : '');

		if ($dismissible) {
			$class .= ' is-dismissible';
		}

		$output = '<div class="' . $class . '">';
		$output .= $is_html ? $message : '<p>' . esc_html($message) . '</p>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Output an optionally dismissable admin notice.
	 *
	 * @param string $type
	 * @param string $message
	 * @param bool $dismissible
	 * @param bool $html
	 */
	public static function display_notice($type, $message, $dismissible = true, $html = false)
	{
		echo self::render_notice($type, $message, $dismissible, $html);
	}

	/**
	 * Gets a list of all product categories for use in a dropdown list.
	 *
	 * @return array
	 */
	public static function get_category_options()
	{
		$terms = get_terms([
			'taxonomy' => 'product_cat',
			'hide_empty' => false,
			'orderby' => 'name',
		]);

		$categories = [];

		foreach ($terms as $term) {
			$term->children = [];
			$categories[$term->term_id] = $term;
		}

		$tree = [];

		foreach ($categories as $cat) {
			if ($cat->parent === 0 || !isset($categories[$cat->parent])) {
				$tree[] = $cat;
			} else {
				$parent = $categories[$cat->parent];
				$parent->children[] = $cat;
				$cat->name = $parent->name . ' > ' . $cat->name;
			}
		}

		return self::sort_category_options($tree);
	}

	protected static function sort_category_options($tree, $options = [], $level = 0)
	{
		foreach ($tree as $cat) {
			$options[$cat->term_id] = $cat->name;

			if (!empty($cat->children)) {
				$options = self::sort_category_options($cat->children, $options, $level + 1);
			}
		}

		return $options;
	}
}
