<?php
use Mewz\Framework\Util\Number;

defined('ABSPATH') or die;

/**
 * @var \WC_Product $product
 */

$lang = str_replace('_', '-', get_bloginfo('language'));

$multiplier_desc = __('Set a multiplier for stock quantities. Applies to product stock if managing stock, otherwise applies to attribute stock (inherit multipliers on attribute stock items). Multipliers set on variations take priority.', 'woocommerce-attribute-stock');

if (MEWZ_WCAS_LITE) {
	$multiplier_desc .= '<br><br> ' . __('LITE VERSION: Multipliers will only apply to attribute stock. The full version is required for product stock multipliers.', 'woocommerce-attribute-stock');
}
?>

<div class="mewz-wcas-options options_group show_if_simple show_if_variable">

<?php woocommerce_wp_text_input([
	'id' => '_mewz_wcas_multiplier',
	'value' => Number::safe_decimal($product->get_meta('_mewz_wcas_multiplier')),
	'label' => __('Stock multiplier', 'woocommerce-attribute-stock'),
	'desc_tip' => true,
	'description' => $multiplier_desc,
	'type' => 'number',
	'custom_attributes' => ['step' => 'any', 'lang' => $lang],
]); ?>

<?php woocommerce_wp_checkbox([
	'id' => '_mewz_wcas_exclude',
	'value' => (int)$product->get_meta('_mewz_wcas_exclude'),
	'cbvalue' => 1,
	'label' => __('Ignore attribute stock', 'woocommerce-attribute-stock'),
	'description' => __('Exclude this product from affecting or being affected by attribute stock', 'woocommerce-attribute-stock'),
]); ?>

</div>
