<?php
/**
 * This is an internal file that handles plugin activation, to ensure that
 * only the lite plugin or the full plugin can be active, but not both.
 *
 * It does this by deactivating both plugins if it detects that both plugins
 * have been activated. It then redirects to the current request, which in
 * most cases will be a request to activate one of the plugins, resulting in
 * the subsequent activation of the intended plugin on the second request.
 *
 * If this isn't a normal activation request, such as wp-cli, then we just
 * throw an exception that can be handled externally. At the very least this
 * will just result in the second plugin activation failing, as it should.
 *
 * @since 2.0.0
 */

if (defined('MEWZ_WCAS_VERSION')) {
	if (defined('WP_SANDBOX_SCRAPING') && WP_SANDBOX_SCRAPING && !empty($_SERVER['REQUEST_URI'])) {
		deactivate_plugins([
			'attribute-stock-for-woocommerce/attribute-stock-for-woocommerce.php',
			'woocommerce-attribute-stock/woocommerce-attribute-stock.php',
		], true);

		wp_redirect($_SERVER['REQUEST_URI']);
		die;
	}

	$plugin = basename(dirname(__DIR__));
	$plugin = "$plugin/$plugin.php";

	if (!is_plugin_active($plugin)) {
		/**
		 * If this plugin isn't active but it's being loaded anyway, it's most likely
		 * being deleted. In this case we don't care about its uninstall hook since
		 * it should only be called when the last plugin instance is deleted.
		 *
		 * So we return true here to short-circuit the plugin from loading so that it
		 * can be deleted successfully without throwing a conflict error.
		 */
		return true;
	} else {
		throw new Exception(sprintf(__('A different version of "%s" is already installed. Please deactivate and uninstall it first. Your attribute stock data will not be deleted.', 'woocommerce-attribute-stock'), MEWZ_WCAS_NAME));
	}
}

return false;
