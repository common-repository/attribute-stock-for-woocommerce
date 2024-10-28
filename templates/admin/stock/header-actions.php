<?php
defined('ABSPATH') or die;

/**
 * @var string $import_url
 * @var string $export_url
 * @var string $settings_url
 * @var string $duplicate_url
 */

if (!empty($import_url)) {
	?><button type="button" class="page-title-action mewz-wcas-import-button" id="mewz-wcas-import-button" data-action="<?= esc_url($import_url) ?>"><?= esc_html__('Import') ?></button><?php
}

if (!empty($export_url)) {
	?><a href="<?= esc_url($export_url) ?>" class="page-title-action mewz-wcas-export-button"><?= esc_html__('Export') ?></a><?php
}

if (!empty($settings_url)) {
	?><a href="<?= esc_url($settings_url) ?>" class="page-title-action mewz-wcas-settings-button"><?= esc_html__('Settings') ?></a><?php
}

if (!empty($duplicate_url)) {
	?><a href="<?= esc_url($duplicate_url) ?>" class="page-title-action mewz-wcas-duplicate-button"><?= esc_html__('Duplicate', 'woocommerce') ?></a><?php
}
