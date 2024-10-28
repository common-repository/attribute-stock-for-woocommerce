<?php
use Mewz\Framework\Util\Number;

defined('ABSPATH') or die;

/**
 * @var Mewz\Framework\Services\View $this
 * @var Mewz\WCAS\Models\AttributeStock $stock
 */

$lang = str_replace('_', '-', get_bloginfo('language'));

$pro_cta = MEWZ_WCAS_LITE ? '<a href="' . esc_url($this->plugin->sale_url()) . '" class="mewz-wcas-pro-cta" title="' . esc_attr__('Full version required — Click to upgrade', 'woocommerce-attribute-stock') . '" target="_blank">PRO</a>' : '';
?>

<div class="options_group">
	<?php
	woocommerce_wp_text_input([
	'label' => __('Low stock threshold', 'woocommerce'),
	'id' => 'mewz_wcas_low_stock',
	'name' => 'mewz_wcas[low_stock]',
	'type' => 'number',
	'placeholder' => get_option('woocommerce_notify_low_stock_amount', '0'),
	'description' => __('When stock reaches this amount or less, you will be notified by email (if enabled).', 'woocommerce-attribute-stock') ,
	'desc_tip' => true,
	'value' => Number::safe_decimal($stock->low_stock()),
	'custom_attributes' => ['step' => 'any', 'lang' => $lang],
	]);
	?>
</div>

<div class="options_group">
	<?php
	woocommerce_wp_checkbox([
		'label' => __('Internal stock', 'woocommerce-attribute-stock') . $pro_cta,
		'id' => 'mewz_wcas_internal',
		'name' => 'mewz_wcas[internal]',
		'description' => __('Internal stock is ignored when determining the stock availability of matched products', 'woocommerce-attribute-stock'),
		'cbvalue' => 1,
		'value' => MEWZ_WCAS_LITE ? 1 : (int)$stock->internal(),
		'custom_attributes' => MEWZ_WCAS_LITE ? ['disabled' => 'disabled'] : [],
		'wrapper_class' => MEWZ_WCAS_LITE ? 'mewz-wcas-pro-required' : '',
	]);

	woocommerce_wp_checkbox([
		'label' => __('Multiplex matching', 'woocommerce-attribute-stock'),
		'id' => 'mewz_wcas_multiplex',
		'name' => 'mewz_wcas[multiplex]',
		'description' => __('Deduct stock for multiple matching rules simultaneously (instead of only the first matched rule)', 'woocommerce-attribute-stock'),
		'cbvalue' => 1,
		'value' => (int)$stock->multiplex(),
	]);

	woocommerce_wp_checkbox([
		'label' => __('Lock multipliers', 'woocommerce-attribute-stock'),
		'id' => 'mewz_wcas_lock_multipliers',
		'name' => 'mewz_wcas[lock_multipliers]',
		'description' => __('Prevent this item\'s stock multipliers from being overridden by products', 'woocommerce-attribute-stock'),
		'cbvalue' => 1,
		'value' => (int)$stock->lock_multipliers(),
	]);
	?>
</div>

<div class="options_group">
	<?php
	woocommerce_wp_checkbox([
		'label' => __('Product SKU', 'woocommerce-attribute-stock') . $pro_cta,
		'id' => 'mewz_wcas_product_sku',
		'name' => 'mewz_wcas[product_sku]',
		'description' => __('Allow matched products to inherit this stock item\'s SKU', 'woocommerce-attribute-stock'),
		'cbvalue' => 1,
		'value' => (int)$stock->product_sku(),
		'custom_attributes' => MEWZ_WCAS_LITE ? ['disabled' => 'disabled'] : [],
		'wrapper_class' => MEWZ_WCAS_LITE ? 'mewz-wcas-pro-required' : '',
	]);

	woocommerce_wp_checkbox([
		'label' => __('Product image', 'woocommerce-attribute-stock') . $pro_cta,
		'id' => 'mewz_wcas_product_image',
		'name' => 'mewz_wcas[product_image]',
		'description' => __('Allow matched products to inherit this stock item\'s image', 'woocommerce-attribute-stock'),
		'cbvalue' => 1,
		'value' => (int)$stock->product_image(),
		'custom_attributes' => MEWZ_WCAS_LITE ? ['disabled' => 'disabled'] : [],
		'wrapper_class' => MEWZ_WCAS_LITE ? 'mewz-wcas-pro-required' : '',
	]);
	?>
</div>
