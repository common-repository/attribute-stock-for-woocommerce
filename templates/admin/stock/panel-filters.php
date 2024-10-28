<?php
defined('ABSPATH') or die;

/**
 * @var Mewz\WCAS\Models\AttributeStock $stock
 * @var array $products
 * @var array $exclude_products
 * @var array $categories
 * @var array $product_types
 */

echo '<div class="options_group">';

woocommerce_wp_select([
	'label' => __('Products', 'woocommerce'),
	'id' => 'mewz_wcas_products',
	'name' => 'mewz_wcas[products][]',
	'class' => 'wc-product-search',
	'description' => __('Filter matching to the selected products only. Leave blank to allow all products.', 'woocommerce-attribute-stock'),
	'desc_tip' => true,
	'value' => $stock->products(),
	'options' => $products,
	'custom_attributes' => [
		'multiple' => 'multiple',
		'data-placeholder' => __('All products', 'woocommerce-attribute-stock'),
		'data-action' => 'woocommerce_json_search_products',
		'data-exclude_type' => 'grouped,external',
	],
]);

woocommerce_wp_select([
	'label' => __('Exclude products', 'woocommerce'),
	'id' => 'mewz_wcas_exclude_products',
	'name' => 'mewz_wcas[exclude_products][]',
	'class' => 'wc-product-search',
	'description' => __('Filter matching to exclude the selected products.', 'woocommerce-attribute-stock'),
	'desc_tip' => true,
	'value' => $stock->exclude_products(),
	'options' => $exclude_products,
	'custom_attributes' => [
		'multiple' => 'multiple',
		'data-placeholder' => __('No products excluded', 'woocommerce-attribute-stock'),
		'data-action' => 'woocommerce_json_search_products',
		'data-exclude_type' => 'grouped,external',
	],
]);

echo '</div><div class="options_group">';

woocommerce_wp_select([
	'label' => __('Categories', 'woocommerce'),
	'id' => 'mewz_wcas_categories',
	'name' => 'mewz_wcas[categories][]',
	'class' => 'wc-enhanced-select',
	'description' => __('Filter matching to products in the selected categories only. Leave blank to allow products in any category.', 'woocommerce-attribute-stock'),
	'desc_tip' => true,
	'value' => $stock->categories(),
	'options' => $categories,
	'custom_attributes' => [
		'multiple' => 'multiple',
		'data-placeholder' => __('All categories', 'woocommerce-attribute-stock'),
	],
]);

woocommerce_wp_select([
	'label' => __('Exclude categories', 'woocommerce'),
	'id' => 'mewz_wcas_exclude_categories',
	'name' => 'mewz_wcas[exclude_categories][]',
	'class' => 'wc-enhanced-select',
	'description' => __('Filter matching to exclude all products in the selected categories.', 'woocommerce-attribute-stock'),
	'desc_tip' => true,
	'value' => $stock->exclude_categories(),
	'options' => $categories,
	'custom_attributes' => [
		'multiple' => 'multiple',
		'data-placeholder' => __('No categories excluded', 'woocommerce-attribute-stock'),
	],
]);

echo '</div><div class="options_group">';

woocommerce_wp_select([
	'label' => __('Product types', 'woocommerce-attribute-stock'),
	'id' => 'mewz_wcas_product_types',
	'name' => 'mewz_wcas[product_types][]',
	'class' => 'wc-enhanced-select',
	'description' => __('Filter matching to products of the selected types only. Leave blank to allow products of any valid type.', 'woocommerce-attribute-stock'),
	'desc_tip' => true,
	'value' => $stock->product_types(),
	'options' => $product_types,
	'custom_attributes' => [
		'multiple' => 'multiple',
		'data-placeholder' => __('All product types', 'woocommerce-attribute-stock'),
	],
]);

echo '</div>';
