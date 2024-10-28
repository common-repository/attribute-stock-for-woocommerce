<?php
defined('ABSPATH') or die;

/**
 * @var \WP_Post $post
 * @var int $index
 */
?>

<label class="tips" data-tip="<?= esc_attr__('Exclude this variation from affecting or being affected by attribute stock', 'woocommerce-attribute-stock') ?>">
	<?= esc_html__('Ignore attribute stock', 'woocommerce-attribute-stock') ?>
	<input type="checkbox" class="checkbox variable_mewz_wcas_exclude" name="variable_mewz_wcas_exclude[<?= esc_attr($index) ?>]" <?php checked(get_post_meta($post->ID, '_mewz_wcas_exclude', true)) ?> />
</label>
