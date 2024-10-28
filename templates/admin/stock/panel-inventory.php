<?php
use Mewz\Framework\Util\Number;

defined('ABSPATH') or die;

/** @var Mewz\WCAS\Models\AttributeStock $stock */

$lang = str_replace('_', '-', get_bloginfo('language'));

woocommerce_wp_text_input([
	'label' => __('SKU', 'woocommerce'),
	'id' => 'mewz_wcas_sku',
	'name' => 'mewz_wcas[sku]',
	'description' => __('Unique identifier for stock keeping. This is optional for your own reference and doesn\'t affect stock functionality in any way.', 'woocommerce-attribute-stock'),
	'desc_tip' => true,
	'value' => $stock->sku(),
]);

woocommerce_wp_text_input([
	'label' => __('Stock quantity', 'woocommerce'),
	'id' => 'mewz_wcas_quantity',
	'name' => 'mewz_wcas[quantity]',
	'type' => 'number',
	'placeholder' => number_format_i18n(0, 2),
	'description' => __('Current stock quantity of this attribute stock item.', 'woocommerce-attribute-stock'),
	'desc_tip' => true,
	'value' => Number::safe_decimal($stock->quantity()),
	'custom_attributes' => ['step' => 'any', 'lang' => $lang],
]);

woocommerce_wp_textarea_input([
	'label' => __('Notes'),
	'id' => 'mewz_wcas_notes',
	'name' => 'mewz_wcas[notes]',
	'rows' => 3,
	'description' => __('Internal notes about this attribute stock item.', 'woocommerce-attribute-stock'),
	'desc_tip' => true,
	'value' => $stock->notes(),
]);
