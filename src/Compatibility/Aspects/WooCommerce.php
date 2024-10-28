<?php
namespace Mewz\WCAS\Compatibility\Aspects;

use Mewz\Framework\Base\Aspect;
use Mewz\Framework\Util\Multilang;
use Mewz\WCAS\Aspects\Front\ProductLimits;
use Mewz\WCAS\Models\AttributeStock;
use Mewz\WCAS\Util;

class WooCommerce extends Aspect
{
	public function __hooks()
	{
		add_action('admin_init', [$this, 'admin_init']);

		// product search multilang compatibility
		add_action('wp_ajax_woocommerce_json_search_products', [$this, 'ajax_before_json_search_products'], 0);
		add_action('wp_ajax_woocommerce_json_search_products_and_variations', [$this, 'ajax_before_json_search_products'], 0);

		// ensure product limiting doesn't override product props in places where edit context should *always* be used
		add_filter('mewz_wcas_limit_product_stock', [$this, 'limit_product_stock'], 999999);
	}

	public function admin_init()
	{
		// ensure product limiting doesn't override product props in places where edit context should *always* be used
		if (!MEWZ_WCAS_LITE && Util\Limits::product_limits_active()) {
			add_action('manage_product_posts_custom_column', [$this, 'before_render_product_column'], 0, 2);
		}
	}

	public function ajax_before_json_search_products()
	{
		if (Multilang::active() && $this->check_referrer()) {
			Multilang::set_lang(Multilang::get_lang('default'));
		}
	}

	public function check_referrer()
	{
		// make sure we only modify the results for our own edit pages
		$referer = wp_get_raw_referer();

		if (strpos($referer, '/post.php') !== false) {
			return (
				preg_match('/[?&]post=(\d+)/', $referer, $matches)
				&& !empty($matches[1])
				&& get_post_type((int)$matches[1]) === AttributeStock::POST_TYPE
			);
		} else {
			return (
				strpos($referer, '/post-new.php') !== false
				&& strpos($referer, 'post_type=' . AttributeStock::POST_TYPE) !== false
			);
		}
	}

	public function limit_product_stock($limit)
	{
		global $pagenow;

		if ($limit) {
			// don't limit on the product edit page
			if ($pagenow == 'post.php' && !empty($_GET['post']) && get_post_type($_GET['post']) === 'product') {
				return false;
			}

			// don't limit during variation ajax actions
			if ($this->context->ajax && isset($_REQUEST['action']) && in_array($_REQUEST['action'], ['woocommerce_add_variation', 'woocommerce_save_variations', 'woocommerce_load_variations'])) {
				return false;
			}
		}

		return $limit;
	}

	public function before_render_product_column($column, $post_id)
	{
		// the name column contains all the "Quick Edit" data (which WooCommerce doesn't use 'edit' context for),
		// so we need to make sure product limits are always disabled on this column
		if ($column === 'name') {
			ProductLimits::$enabled = false;
	    } else {
			ProductLimits::$enabled = true;
		}
	}
}
