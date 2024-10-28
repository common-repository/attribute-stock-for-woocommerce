<?php
defined('ABSPATH') or die;

/**
 * @var Mewz\WCAS\Models\AttributeStock $stock
 * @var array $stock_options
 * @var array $components
 */

woocommerce_wp_select([
	'label' => __('Parent components', 'woocommerce-attribute-stock'),
	'id' => 'mewz_wcas_component_parent_ids',
	'name' => 'mewz_wcas_components[parent_ids][]',
	'class' => 'wc-enhanced-select',
	'description' => $description = __('Select items that use this stock item as a component.', 'woocommerce-attribute-stock'),
	'desc_tip' => true,
	'value' => $components['parent_ids'],
	'options' => $stock_options,
	'custom_attributes' => [
		'multiple' => 'multiple',
		'data-placeholder' => $description . '..',
	],
]);

woocommerce_wp_select([
	'label' => __('Child components', 'woocommerce-attribute-stock'),
	'id' => 'mewz_wcas_component_child_ids',
	'name' => 'mewz_wcas_components[child_ids][]',
	'class' => 'wc-enhanced-select',
	'description' => $description = __('Select components that this stock item uses.', 'woocommerce-attribute-stock'),
	'desc_tip' => true,
	'value' => $components['child_ids'],
	'options' => $stock_options,
	'custom_attributes' => [
		'multiple' => 'multiple',
		'data-placeholder' => $description . '..',
	],
]);
?>

<input type="hidden" name="mewz_wcas_noupdate_components" id="mewz_wcas_noupdate[components]" value="1">
