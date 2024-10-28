<?php
namespace Mewz\Framework\Util;

class Multilang
{
	public static function plugin()
	{
		static $plugin;

		if ($plugin === null) {
			global $sitepress;

			if ($sitepress) {
				$plugin = 'wpml';
			} elseif (defined('POLYLANG')) {
				$plugin = 'polylang';
			} else {
				$plugin = false;
			}
		}

		return $plugin;
	}

	public static function active()
	{
		static $active;
		return $active ??= (bool)self::plugin();
	}

	public static function get_lang($type = 'current')
	{
		static $lang = [];

		global $sitepress;

		if (!isset($lang[$type])) {
			if ($sitepress) {
				$lang[$type] = $type === 'current' ? $sitepress->get_current_language() : $sitepress->get_default_language();
			} elseif (defined('POLYLANG')) {
				$lang[$type] = $type === 'current' ? pll_current_language() : pll_default_language();
			} else {
				$lang[$type] = false;
			}
		}

		return $lang[$type];
	}

	public static function set_lang($lang)
	{
		global $sitepress;

		if ($sitepress) {
			static $lang_prop;

			if ($lang === self::get_lang()) {
				return false;
			}

			if ($lang_prop === null) {
				try {
					$lang_prop = new \ReflectionProperty($sitepress, 'this_lang');
					$lang_prop->setAccessible(true);
				} catch (\ReflectionException $e) {
					return false;
				}
			}

			$lang_prop->setValue($sitepress, $lang);
		}
		elseif (defined('POLYLANG')) {
			if ($lang === self::get_lang()) {
				return false;
			}

			PLL()->curlang = PLL()->model->get_language($lang);
		}

		return true;
	}

	public static function reset_lang()
	{
	    self::set_lang(self::get_lang());
	}

	public static function get_translated_object_id($id, $content_type, $object_type, $lang = 'current')
	{
		static $cache = [];

		if ($lang === 'current' || $lang === 'default') {
			$lang = self::get_lang($lang);
		}

		if (!isset($cache[$content_type][$lang][$id])) {
			global $sitepress;

			if ($sitepress) {
				$translated_id = wpml_object_id_filter($id, $object_type, true, $lang);
			}
			elseif (defined('POLYLANG')) {
				if ($content_type === 'post') {
					$translated_id = pll_get_post($id, $lang);
				} elseif ($content_type === 'term') {
					$translated_id = pll_get_term($id, $lang);
				}
			}

			$cache[$content_type][$lang][$id] = !empty($translated_id) ? $translated_id : $id;
		}

		return $cache[$content_type][$lang][$id];
	}

	public static function get_lang_suffix($lang = null, $sep = '-')
	{
		static $suffix = [];

		if ($lang === null) {
			$lang = self::get_lang();
			if (!$lang) return '';
		}

		if (!isset($suffix[$lang])) {
			$suffix[$lang] = $lang && $lang !== self::get_lang('default') ? $sep . $lang : '';
		}

		return $suffix[$lang];
	}

	public static function strip_lang_suffix($slug, $lang = null, $sep = '-')
	{
		$suffix = self::get_lang_suffix($lang, $sep);
		if (!$suffix) return $slug;

		$len = strlen($suffix);

		if (substr($slug, -$len) === $suffix) {
			$slug = substr($slug, 0, -$len);
		}

		return $slug;
	}

	public static function toggle_term_filters($value)
	{
		global $sitepress;

		if ($sitepress) {
			if ($value) {
				add_filter('get_terms_args', [$sitepress, 'get_terms_args_filter'], 10, 2);
				add_filter('get_term', [$sitepress, 'get_term_adjust_id'], 1);
				add_filter('terms_clauses', [$sitepress, 'terms_clauses'], 10, 3);
			} else {
				remove_filter('get_terms_args', [$sitepress, 'get_terms_args_filter']);
				remove_filter('get_term', [$sitepress, 'get_term_adjust_id'], 1);
				remove_filter('terms_clauses', [$sitepress, 'terms_clauses']);
			}
		}
		elseif (defined('POLYLANG')) {
			$object = PLL()->terms;

			if ($value) {
				add_filter('terms_clauses', [$object, 'terms_clauses'], 10, 3);
			} else {
				remove_filter('terms_clauses', [$object, 'terms_clauses']);
			}
		}
	}

	public static function toggle_term_id_filter($value)
	{
		global $sitepress, $icl_adjust_id_url_filter_off;

		if ($value === null) {
			return null;
		}

		if ($sitepress) {
			$original = !$icl_adjust_id_url_filter_off;

			if ($value !== $original) {
				$icl_adjust_id_url_filter_off = !$value;
				return $original;
			}
		}

		return null;
	}
}
