<?php
/**
 * Plugin Name: WooCommerce Attribute Stock (Lite)
 * Plugin URI:  https://codecanyon.net/item/woocommerce-attribute-stock/25796765
 * Description: Take your stock to the next level. Set up complex stock configurations with ease. Shared stock, variable quantities, the possibilities are endless.
 * Version:     2.0.3
 * Author:      Mewz
 * Author URI:  https://mewz.dev/
 * Text Domain: woocommerce-attribute-stock
 * Domain Path: /languages
 *
 * Requires at least: 5.4
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 6.0
 * WC tested up to: 9.1
 */

defined('ABSPATH') or die;

if (require __DIR__ . '/includes/activation.php') {
	return;
}

const MEWZ_WCAS_VERSION = '2.0.3';
const MEWZ_WCAS_NAME = 'WooCommerce Attribute Stock';
const MEWZ_WCAS_SLUG = 'woocommerce-attribute-stock';
const MEWZ_WCAS_PREFIX = 'mewz_wcas';

const MEWZ_WCAS_LITE = true;
const MEWZ_WCAS_WPORG_SLUG = 'attribute-stock-for-woocommerce';
const MEWZ_WCAS_ENVATO_ID = 25796765;

const MEWZ_WCAS_FILE = __FILE__;
const MEWZ_WCAS_DIR = __DIR__;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/functions.php';

Mewz\WCAS\Plugin::run();
