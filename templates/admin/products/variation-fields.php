<?php
use Mewz\Framework\Util\Number;

defined('ABSPATH') or die;

/**
 * @var \WP_Post $post
 * @var int $index
 */

$lang = str_replace('_', '-', get_bloginfo('language'));

$multiplier_desc = __('Set a multiplier for stock quantities. Applies to product stock if managing stock, otherwise applies to attribute stock (inherit multipliers on attribute stock items). Multipliers set on variations take priority.', 'woocommerce-attribute-stock');

if (MEWZ_WCAS_LITE) {
	$multiplier_desc .= '<br><br> ' . __('LITE VERSION: Multipliers will only apply to attribute stock. The full version is required for product stock multipliers.', 'woocommerce-attribute-stock');
}
?>

<?php woocommerce_wp_text_input([
	'id' => "variable_mewz_wcas_multiplier{$index}",
	'name' => "variable_mewz_wcas_multiplier[{$index}]",
	'value' => Number::safe_decimal(get_post_meta($post->ID, '_mewz_wcas_multiplier', true)),
	'label' => __('Stock multiplier', 'woocommerce-attribute-stock'),
	'desc_tip' => true,
	'description' => $multiplier_desc,
	'wrapper_class' => 'form-row form-row-full',
	'type' => 'number',
	'custom_attributes' => ['step' => 'any', 'lang' => $lang],
]); ?>
