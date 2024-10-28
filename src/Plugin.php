<?php
namespace Mewz\WCAS;

/**
 * @property Core\Installer $installer
 * @property Core\Loader $loader
 */
class Plugin extends \Mewz\Framework\Plugin
{
	public $version = MEWZ_WCAS_VERSION;
	public $name = MEWZ_WCAS_NAME;
	public $slug = MEWZ_WCAS_SLUG;
	public $prefix = MEWZ_WCAS_PREFIX;
	public $base_file = MEWZ_WCAS_FILE;
	public $base_dir = MEWZ_WCAS_DIR;

	const __LITE = MEWZ_WCAS_LITE;
	const __WPORG_SLUG = MEWZ_WCAS_WPORG_SLUG;
	const __ENVATO_ID = MEWZ_WCAS_ENVATO_ID;

	protected function get_installer()
	{
		return new Core\Installer($this);
	}

	protected function get_loader()
	{
		return new Core\Loader($this);
	}

	public function sale_url()
	{
		return 'https://codecanyon.net/item/woocommerce-attribute-stock/25796765';
	}

	public function support_url()
	{
		return MEWZ_WCAS_LITE
			? 'https://wordpress.org/support/plugin/attribute-stock-for-woocommerce/'
			: 'https://codecanyon.net/item/woocommerce-attribute-stock/25796765/support';
	}

	public function docs_url($uri = '')
	{
		return 'https://wcas-docs.mewz.dev/' . $uri;
	}
}
