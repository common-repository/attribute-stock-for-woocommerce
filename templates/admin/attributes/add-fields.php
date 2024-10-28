<?php
defined('ABSPATH') or die;

/** @var bool $attribute_level */
?>

<div class="form-field form-field-mewz-wcas form-field-mewz-wcas-manage-stock">
	<label for="mewz_wcas_manage_stock">
		<input name="mewz_wcas[manage_stock]" id="mewz_wcas_manage_stock" type="checkbox" value="1">
		<?= esc_html__('Manage stock?', 'woocommerce') ?>
	</label>
	<p class="description">
		<?php if (!empty($attribute_level)): ?>
			<?= esc_html__('Enable stock management at the attribute level (all terms share the same stock).', 'woocommerce-attribute-stock') ?>
		<?php else: ?>
			<?= esc_html__('Enable stock management for this attribute term.', 'woocommerce-attribute-stock') ?>
		<?php endif; ?>
	</p>
</div>

<div class="form-field form-field-mewz-wcas form-field-mewz-wcas-sku form-field-mewz-wcas-hidden">
	<label for="mewz_wcas_sku">
		<?= esc_html__('SKU', 'woocommerce') ?>
	</label>
	<input name="mewz_wcas[sku]" id="mewz_wcas_sku" type="text" value="">
	<p class="description">
		<?= esc_html__('Unique identifier for stock keeping. Optional for your own reference.', 'woocommerce-attribute-stock') ?>
	</p>
</div>

<div class="form-field form-field-mewz-wcas form-field-mewz-wcas-quantity form-field-mewz-wcas-hidden">
	<label for="mewz_wcas_quantity">
		<?= esc_html__('Stock quantity', 'woocommerce') ?>
	</label>
	<input name="mewz_wcas[quantity]" id="mewz_wcas_quantity" type="number" value="" step="any" placeholder="<?= esc_attr(number_format_i18n(0, 2)) ?>">
	<p class="description">
		<?php if (!empty($attribute_level)): ?>
			<?= esc_html__('Current stock quantity of this attribute.', 'woocommerce-attribute-stock') ?>
		<?php else: ?>
			<?= esc_html__('Current stock quantity of this attribute term.', 'woocommerce-attribute-stock') ?>
		<?php endif; ?>
	</p>
</div>

<?php if (empty($attribute_level)): ?>
	<div class="form-field form-field-mewz-wcas form-field-mewz-wcas-multiplier">
		<label for="mewz_wcas_multiplier">
			<?= esc_html__('Stock multiplier', 'woocommerce-attribute-stock') ?>
		</label>
		<input name="mewz_wcas[multiplier]" id="mewz_wcas_multiplier" type="number" value="" step="any" placeholder="<?= esc_attr(number_format_i18n(1, 2)) ?>">
		<p class="description">
			<?= esc_html__('The default stock multiplier to use for product stock or attribute stock.', 'woocommerce-attribute-stock') ?>
		</p>
	</div>
<?php endif; ?>
