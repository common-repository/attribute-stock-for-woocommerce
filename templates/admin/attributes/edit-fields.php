<?php
use Mewz\Framework\Util\Number;
use Mewz\WCAS\Util\Matches;

defined('ABSPATH') or die;

/**
 * @var Mewz\WCAS\Models\AttributeStock[] $stocks
 * @var bool $attribute_level
 * @var WP_Term $term
 */

$sku_column = false;

if (count($stocks) > 1) {
	foreach ($stocks as $stock) {
		if ($stock->sku() !== '') {
			$sku_column = true;
			break;
		}
	}
}
?>

<?php if (count($stocks) > 1): ?>

	<tr class="form-field form-field-mewz-wcas-associated">
		<th scope="row">
			<label for="mewz_wcas_assigned">
				<?= esc_html__('Attribute stock', 'woocommerce-attribute-stock') ?>
			</label>
		</th>
		<td>
			<table class="mewz-wcas-associated-table widefat striped">
				<thead>
				<tr>
					<th scope="col" class="column-title"><?= esc_html__('Title') ?></th>
					<?php if ($sku_column): ?>
						<th scope="col" class="column-sku"><?= esc_html__('SKU', 'woocommerce') ?></th>
					<?php endif; ?>
					<th scope="col" class="column-quantity"><?= esc_html__('Stock', 'woocommerce') ?></th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ($stocks as $stock): ?>
					<tr<?= !$stock->enabled() ? ' class="disabled"' : '' ?>>
						<td class="column-title">
							<?= ($url = $stock->edit_url()) ? '<a href="' . esc_url($url) . '"' : '<span' ?> class="stock-title">
								<?php if ($image_id = $stock->image_id()): ?>
									<?= wp_get_attachment_image($image_id, [32, 32]) ?>
								<?php endif; ?>

								<span class="title"><?= esc_html($stock->title()) ?></span>
							<?= $url ? '</a>' : '</span>' ?>

							<?php if (!$stock->enabled()): ?>
								<span class="disabled-status">— <?= esc_html__('Disabled', 'woocommerce') ?></span>
							<?php endif; ?>
						</td>

						<?php if ($sku_column): ?>
							<td class="column-sku">
								<?= esc_html($stock->sku()) ?>
							</td>
						<?php endif; ?>

						<td class="column-quantity">
							<?php if ($term): ?>
								<?= Matches::get_term_display_quantity($stock, $term->taxonomy, $term->term_id) ?>
							<?php else: ?>
								<?= Number::local_format($stock->quantity()) ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</td>
	</tr>

<?php else: ?>
	<?php $stock = current($stocks); ?>

	<tr class="form-field form-field-mewz-wcas form-field-mewz-wcas-manage-stock">
		<th scope="row">
			<label for="mewz_wcas_manage_stock">
				<?= esc_html__('Manage stock?', 'woocommerce') ?>
			</label>
		</th>
		<td>
			<div class="input-wrap">
				<input type="checkbox" name="mewz_wcas[manage_stock]" id="mewz_wcas_manage_stock" value="1" <?php checked($stock->enabled()) ?>>
				<input type="hidden" name="mewz_wcas[stock_id]" id="mewz_wcas_stock_id" value="<?= $stock->id() ?>">

				<?php if ($stock->valid('edit')): ?>
					<a href="<?= esc_url($stock->edit_url()) ?>" class="button mewz-wcas-edit-button"><?= esc_html__('Configure', 'woocommerce-attribute-stock') ?></span></a>
				<?php endif; ?>
			</div>

			<p class="description">
				<?php if (!empty($attribute_level)): ?>
					<?= esc_html__('Enable stock management at the attribute level (all terms share the same stock).', 'woocommerce-attribute-stock') ?>
				<?php else: ?>
					<?= esc_html__('Enable stock management for this attribute term.', 'woocommerce-attribute-stock') ?>
				<?php endif; ?>
			</p>
		</td>
	</tr>

	<tr class="form-field form-field-mewz-wcas form-field-mewz-wcas-sku form-field-mewz-wcas-hidden<?php if ($stock->enabled()) echo ' show'; ?>">
		<th scope="row">
			<label for="mewz_wcas_sku">
				<?= esc_html__('SKU', 'woocommerce') ?>
			</label>
		</th>
		<td>
			<input type="text" name="mewz_wcas[sku]" id="mewz_wcas_sku" value="<?= esc_attr($stock->sku()) ?>">

			<p class="description">
				<?= esc_html__('Unique identifier for stock keeping. Optional for your own reference.', 'woocommerce-attribute-stock') ?>
			</p>
		</td>
	</tr>

	<tr class="form-field form-field-mewz-wcas form-field-mewz-wcas-quantity form-field-mewz-wcas-hidden<?php if ($stock->enabled()) echo ' show'; ?>">
		<th scope="row">
			<label for="mewz_wcas_quantity">
				<?= esc_html__('Stock quantity', 'woocommerce') ?>
			</label>
		</th>
		<td>
			<input type="number" name="mewz_wcas[quantity]" id="mewz_wcas_quantity" value="<?= $stock->quantity() ?>" step="any" placeholder="<?= esc_attr(number_format_i18n(0, 2)) ?>">

			<p class="description">
				<?php if (!empty($attribute_level)): ?>
					<?= esc_html__('Current stock quantity of this attribute.', 'woocommerce-attribute-stock') ?>
				<?php else: ?>
					<?= esc_html__('Current stock quantity of this attribute term.', 'woocommerce-attribute-stock') ?>
				<?php endif; ?>
			</p>
		</td>
	</tr>

<?php endif; ?>

<?php if (empty($attribute_level)): ?>
	<tr class="form-field form-field-mewz-wcas form-field-mewz-wcas-multiplier">
		<th scope="row">
			<label for="mewz_wcas_multiplier">
				<?= esc_html__('Stock multiplier', 'woocommerce-attribute-stock') ?>
			</label>
		</th>
		<td>
			<input type="number" name="mewz_wcas[multiplier]" id="mewz_wcas_multiplier" value="<?= Number::safe_decimal(get_term_meta($term->term_id, 'mewz_wcas_multiplier', true)) ?>" step="any" min="0" placeholder="<?= esc_attr(number_format_i18n(1, 2)) ?>">

			<p class="description">
				<?= esc_html__('The default stock multiplier to use for product stock or attribute stock.', 'woocommerce-attribute-stock') ?>
			</p>
		</td>
	</tr>
<?php endif; ?>
