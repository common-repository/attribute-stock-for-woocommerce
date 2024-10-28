<?php
namespace Mewz\WCAS\Core;

use Mewz\Framework\Core;
use Mewz\Framework\Util\PostType;
use Mewz\WCAS\Aspects;
use Mewz\WCAS\Classes\RestApiController;
use Mewz\WCAS\Compatibility;
use Mewz\WCAS\Models\AttributeStock;
use Mewz\WCAS\Util;

class Loader extends Core\Loader
{
	public function register_tables()
	{
		Installer::register_tables();
	}

	public function load()
	{
		parent::load();

		add_action('before_woocommerce_init', [$this, 'before_woocommerce_init']);

		// load compatibility integration overrides
		add_filter('woocommerce_integrations', [$this, 'integrations_compatibility'], 20);

		// register WooCommerce webhooks
		$this->aspects->load(Aspects\Common\Webhooks::class);
	}

	public function init()
	{
		// make sure WooCommerce is active before running
		if (!defined('WC_PLUGIN_FILE')) return;

	    parent::init();

		add_action('init', [$this, 'register_post_types'], 5);
		add_action('rest_api_init', [$this, 'rest_api_init'], 5);

		// handle background tasks
		add_action('wp_ajax_mewz_wcas_task', [$this, 'ajax_handle_task']);
		add_action('wp_ajax_nopriv_mewz_wcas_task', [$this, 'ajax_handle_task']);
		add_action('shutdown', [$this, 'shutdown_dispatch_tasks'], 999999);

		// load common actions
		$this->aspects->load([
			Aspects\Common\CleanUp::class,
			Aspects\Common\Shortcode::class,
			Aspects\Common\StockNotifications::class,
			Aspects\Common\UpdateOrderStock::class,
		]);

		// load compatibility for certain plugins to play nice with attribute stock
		$this->load_compatibility_actions();

		// load admin actions
		if ($this->context->admin) {
			add_action('current_screen', [$this, 'current_screen'], 0);
			add_action('load-product_page_product_attributes', [$this, 'load_product_attributes'], 0);
			add_action('load-woocommerce_page_wc-reports', [$this, 'load_wc_reports'], 0);
			add_action('load-plugins.php', [$this, 'load_plugins_page'], 0);
		}

		// everything below this line is full version only
		if (MEWZ_WCAS_LITE) return;

		// load settings
		if ($this->context->admin) {
			$this->aspects->load(Aspects\Admin\Settings\InventorySettings::class);
		}

		$this->aspects->load(Aspects\Common\ProductSettings::class);

		// load product limits
		$modify_product_stock = Util\Settings::modify_product_stock();

		if ($modify_product_stock === 'auto') {
			$limit_product_stock = apply_filters('mewz_wcas_limit_product_stock_auto', $this->context->front || $this->context->cron || $this->context->task);
		} else {
			$limit_product_stock = $modify_product_stock === 'yes';
		}

		$limit_product_stock = apply_filters('mewz_wcas_limit_product_stock', $limit_product_stock, $modify_product_stock);

		if ($limit_product_stock) {
			$this->aspects->load(Aspects\Front\ProductLimits::class);

			// load frontend actions
			if ($this->context->front) {
				add_action('wp_loaded', [$this, 'wp_loaded_front'], 0);
				add_action('wc_ajax_get_variation', [$this, 'wc_ajax_get_variation'], 0);

				$this->aspects->load(Aspects\Front\VariableLimits::class);
			}
		}

		// load workers
		if ($limit_product_stock || $modify_product_stock !== 'no') {
			if ($modify_product_stock === 'auto' && !class_exists(Aspects\Front\ProductLimits::class, false)) {
				$this->aspects->load(Aspects\Workers\AutoProductLimits::class);
			}

			$this->aspects->load([
				Aspects\Workers\StockChange::class,
				Aspects\Workers\OutOfStockProducts::class,
				Aspects\Workers\ProductAttributesLookup::class,
			]);
		}

		if (!class_exists(Core\Updater::class, false)) {
			$this->plugin->__load_updater();

			if (!class_exists(Core\Updater::class, false)) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
				deactivate_plugins($this->plugin->basename);
			}
		}
	}

	public function register_post_types()
	{
		PostType::add(AttributeStock::POST_TYPE, [
			'description' => __('WooCommerce attribute stock items.', 'woocommerce-attribute-stock'),
			'capability_type' => ['attribute_stock_item', 'attribute_stock'],
			'map_meta_cap' => true,
			'show_ui' => true,
			'show_in_menu' => apply_filters('mewz_wcas_show_in_product_submenu', current_user_can('edit_products')) ? 'edit.php?post_type=product' : true,
			'menu_icon' => 'dashicons-clipboard',
			'menu_position' => 56,
			'supports' => ['title', 'thumbnail'],
			'taxonomies' => ['product_tag'],
			'public' => false,
			'rewrite' => false,
			'labels' => [
				'name'                  => _x('Attribute Stock', 'post type general name', 'woocommerce-attribute-stock'),
				'singular_name'         => _x('Attribute Stock', 'post type singular name', 'woocommerce-attribute-stock'),
				'featured_image'        => __('Stock image', 'woocommerce-attribute-stock'),
				'set_featured_image'    => __('Set stock image', 'woocommerce-attribute-stock'),
				'remove_featured_image' => __('Remove stock image', 'woocommerce-attribute-stock'),
				'use_featured_image'    => __('Use as stock image', 'woocommerce-attribute-stock'),
			],
		]);

		add_image_size('32x32', 32, 32, true);

		AttributeStock::apply_defaults();
	}

	public function register_scripts()
	{
		if ($this->context->admin) {
			// require wp-i18n for wp delete dialog text
			$this->scripts->register_js('@admin/stock-list', ['deps' => ['wp-i18n', 'jquery-tiptip']]);
			$this->scripts->register_css('@admin/stock-list');

			$this->scripts->register_js('@admin/stock-edit', ['deps' => 'wc-admin-meta-boxes']);
			$this->scripts->register_css('@admin/stock-edit');

			$this->scripts->register_js('@admin/attributes');
			$this->scripts->register_css('@admin/attributes');

			$this->scripts->register_css('@admin/product-edit');
			$this->scripts->register_css('@admin/reports');
		}
		elseif (!MEWZ_WCAS_LITE) {
			$this->scripts->register_js('@front/variable-stock');
		}
	}

	public function rest_api_init()
	{
		$this->aspects->load(Aspects\Admin\Stock\StockAjax::class);

		add_filter('woocommerce_rest_api_get_rest_namespaces', [$this, 'register_wc_rest_api_namespace']);
	}

	public function register_wc_rest_api_namespace($namespaces)
	{
		$namespaces['wc/v3']['attribute-stock'] = RestApiController::class;

	    return $namespaces;
	}

	public function admin_init()
	{
		parent::admin_init();

		if ($this->context->taxonomy && strpos($this->context->taxonomy, 'pa_') === 0) {
			$this->aspects->load([
				Aspects\Admin\Attributes\AttributeTermEdit::class,
				Aspects\Admin\Attributes\AttributeTermSave::class,
				Aspects\Admin\Attributes\AttributeTermList::class,
			]);
		}

		$this->aspects->load([
			Aspects\Admin\Products\ProductVariationEdit::class,
			Aspects\Admin\Stock\StockExport::class,
		]);

		if (!WP_DEBUG && !MEWZ_WCAS_LITE && !class_exists(Core\Authorizer::class, false)) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			deactivate_plugins($this->plugin->basename);
		}
	}

	public function ajax_handle_task()
	{
		$this->tasks->handle($_GET, $_POST);
	}

	public function shutdown_dispatch_tasks()
	{
		$this->tasks->dispatch_tasks(!$this->context->task);
	}

	public function current_screen()
	{
		switch ($this->context->screen_id) {
			case 'edit-' . AttributeStock::POST_TYPE:
				$this->aspects->load([
					Aspects\Admin\Stock\StockList::class,
					Aspects\Admin\Stock\StockListFilters::class,
					Aspects\Admin\Stock\StockListQuery::class,
					Aspects\Admin\Stock\StockActions::class,
					Aspects\Admin\Stock\StockBulkActions::class,
					Aspects\Admin\Plugin\PluginHelp::class,
				]);
				break;

			case AttributeStock::POST_TYPE:
				$this->aspects->load([
					Aspects\Admin\Stock\StockEdit::class,
					Aspects\Admin\Stock\StockSave::class,
					Aspects\Admin\Stock\StockActions::class,
					Aspects\Admin\Plugin\PluginHelp::class,
				]);
				break;

			case 'product':
				$this->aspects->load(Aspects\Admin\Products\ProductEdit::class);
				break;
		}
	}

	public function load_product_attributes()
	{
		$this->aspects->load([
			Aspects\Admin\Attributes\AttributeEdit::class,
			Aspects\Admin\Attributes\AttributeSave::class,
		]);

		if (!isset($_GET['edit'])) {
			$this->aspects->load(Aspects\Admin\Attributes\AttributeList::class);
		}
	}

	public function load_wc_reports()
	{
		$this->aspects->load(Aspects\Admin\Reports\StockReport::class);
	}

	public function load_plugins_page()
	{
		$this->aspects->load(Aspects\Admin\Plugin\PluginLinks::class);
	}

	public function wp_loaded_front()
	{
		if (WC()->cart) {
			$this->aspects->load(Aspects\Front\CartItems::class);
		}
	}

	public function wc_ajax_get_variation()
	{
		$this->aspects->load(Aspects\Front\VariableLimitsAjax::class);
	}

	public function load_compatibility_actions()
	{
		$this->aspects->load(Compatibility\Aspects\WooCommerce::class);

		// Lumise Product Designer
		if (defined('LUMISE_WOO')) {
			$this->aspects->load(Compatibility\Aspects\LumiseProductDesigner::class);
		}

		// OpenPOS
		if (class_exists(\Openpos_Core::class, false)) {
			$this->aspects->load(Compatibility\Aspects\OpenPOS::class);
		}

		// WooCommerce Order Status & Actions Manager
		if (function_exists('WC_SA')) {
			$this->aspects->load(Compatibility\Aspects\OrderStatusActions::class);
		}

		if (!MEWZ_WCAS_LITE) {
			// Advanced Order Export for WooCommerce
			if (defined('WOE_VERSION')) {
				$this->aspects->load(Compatibility\Aspects\AdvancedOrderExport::class);
			}

			// Polylang for WooCommerce
			if (defined('PLLWC_VERSION')) {
				$this->aspects->load(Compatibility\Aspects\Polylang::class);
			}

			// WooCommerce WPML
			if (defined('WCML_VERSION')) {
				$this->aspects->load(Compatibility\Aspects\WPML::class);
			}

			// WP-Lister (for eBay and Amazon)
			if (defined('WPLE_PLUGIN_VERSION') || defined('WPLA_VERSION')) {
				$this->aspects->load(Compatibility\Aspects\WPLister::class);
			}

			// WP Rocket
			if (defined('WP_ROCKET_VERSION')) {
				$this->aspects->load(Compatibility\Aspects\WPRocket::class);
			}

			// Xootix Waitlist WooCommerce
			if (defined('XOO_WL_PLUGIN_FILE')) {
				$this->aspects->load(Compatibility\Aspects\XootixWaitlist::class);
			}
		}
	}

	public function before_woocommerce_init()
	{
		// declare WooCommerce High Performance Order Storage (HPOS) compatibility
		if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', $this->plugin->base_file, true);
		}
	}

	public function integrations_compatibility($integrations)
	{
		// WooCommerce Cart Stock Reducer
		if (!MEWZ_WCAS_LITE && $this->context->front && class_exists(\WC_Cart_Stock_Reducer::class, false)) {
			$csr_index = array_search(\WC_Cart_Stock_Reducer::class, $integrations, true);

			if ($csr_index !== false) {
				$integrations[$csr_index] = Compatibility\Classes\WCCartStockReducer::class;
			}
		}

		return $integrations;
	}
}
