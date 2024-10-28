<?php
defined('ABSPATH') or die;

/**
 * @var Mewz\WCAS\Models\AttributeStock $stock
 * @var string $name
 * @var string $class
 * @var string $placeholder
 */

$class = !empty($class) ? ' ' . $class : '';
$placeholder ??= '';

$product_id = !empty($_REQUEST[$name]) ? (int)$_REQUEST[$name] : '';
$product_name = $placeholder;

if ($product_id && $product = wc_get_product($product_id)) {
	$product_name = $product->get_formatted_name();
}
?>

<select name="<?= esc_attr($name) ?>" id="filter_<?= esc_attr($name) ?>" class="list-filter list-filter-<?= esc_attr(str_replace('_', '-', $name)) ?> wc-product-search<?= $class ?>" data-placeholder="<?= esc_attr($placeholder) ?>" data-allow_clear="true" data-action="woocommerce_json_search_products">
	<option value="<?= $product_id ?>" selected="selected"><?= htmlspecialchars(wp_kses_post($product_name)) ?></option>
</select>
