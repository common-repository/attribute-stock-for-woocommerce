<?php
namespace Mewz\Framework\Core;

use Mewz\Framework\Base\ServiceProxy;

class Loader extends ServiceProxy
{
	public function register_tables() {}

	public function load()
	{
		load_plugin_textdomain('mewz-framework', false, $this->plugin->dirname . '/vendor/mewz/framework/languages');
		load_plugin_textdomain($this->plugin->slug, false, $this->plugin->dirname . '/languages');

		if ($this->plugin->db_version && version_compare($this->plugin->version, $this->plugin->db_version, '>')) {
			$this->plugin->installer->update();
		}

		if (!$this->plugin->__is_lite() && !class_exists(Updater::class, false)) {
			$this->plugin->__load_updater();

			if (!class_exists(Updater::class, false)) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
				deactivate_plugins($this->plugin->basename);
			}
		}

		add_action('init', [$this, 'init'], 0);
		add_action('admin_init', [$this, 'admin_init']);
	}

	public function init()
	{
		if ($this->context->admin) {
			add_action('admin_enqueue_scripts', [$this, 'register_scripts'], 0);
			add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts'], 12);
			add_action('admin_print_footer_scripts', [$this, 'print_footer_scripts'], 5);
		} else {
			add_action('wp_enqueue_scripts', [$this, 'register_scripts'], 0);
			add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts'], 12);
			add_action('wp_print_footer_scripts', [$this, 'print_footer_scripts'], 5);
		}
	}

	public function admin_init()
	{
		if (!WP_DEBUG && !$this->plugin->__is_lite() && !class_exists(Authorizer::class, false)) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			deactivate_plugins($this->plugin->basename, true);
		}
	}

	public function register_scripts() {}

	public function enqueue_scripts()
	{
		$this->aspects->enqueue_scripts();
	}

	public function print_footer_scripts()
	{
		echo $this->scripts->render_export_data();
	}
}
