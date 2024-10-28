<?php
defined('ABSPATH') or die;

/** @var Mewz\WCAS\Models\AttributeStock $stock */

$can_publish = current_user_can('publish_post', $stock->id());
$can_delete = current_user_can('delete_post', $stock->id());
?>

<div class="mewz-wcas-status-metabox submitbox">

	<section class="status-metabox-body">
		<div class="status-row row-enabled">
			<label class="row-label" for="mewz_wcas_enabled"><?= esc_html__('Enabled', 'woocommerce') ?></label>
			<div class="row-content">
				<label class="toggle-switch">
					<input type="checkbox" name="mewz_wcas[enabled]" id="mewz_wcas_enabled" class="checkbox" value="1" <?php checked($stock->status() !== 'draft') ?> <?php disabled(!$can_publish) ?>>
					<span class="switch"></span>
				</label>
			</div>
		</div>

		<?php if ($stock->exists()): ?>
			<div class="status-row row-date row-created">
				<div class="row-label"><?= esc_html__('Created', 'woocommerce') ?></div>
				<div class="row-content"><?= esc_html($stock->created(false, 'admin-full')) ?></div>
			</div>

			<div class="status-row row-date row-modified">
				<div class="row-label"><?= esc_html__('Modified', 'woocommerce-attribute-stock') ?></div>
				<div class="row-content"><?= esc_html($stock->modified(false, 'admin-full')) ?></div>
			</div>
		<?php endif; ?>

		<?php do_action('mewz_wcas_stock_status_metabox_body'); ?>
	</section>

	<section class="status-metabox-foot">
		<?php if ($can_delete): ?>
			<div class="foot-left">
				<a class="submitdelete deletion" href="<?= esc_url(get_delete_post_link($stock->id())) ?>"><?= esc_html__('Move to Trash') ?></a>
			</div>
		<?php endif; ?>

		<div class="foot-right">
			<span class="spinner"></span>
			<?php submit_button($stock->exists() ? __('Update Stock', 'woocommerce-attribute-stock') : __('Create Stock', 'woocommerce-attribute-stock'), 'primary', 'submit', false) ?>
		</div>
	</section>

</div>
