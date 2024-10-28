<?php
namespace Mewz\Framework\Core;

use Mewz\Framework\Plugin;

class Installer
{
	/** @var Plugin */
	protected $plugin;

	/** @var bool */
	protected $initialized_db = false;

	public function __construct(Plugin $plugin)
	{
		$this->plugin = $plugin;
	}

	public static function schema()
	{
		return '';
	}

	public function activate()
	{
		if (!$this->plugin->db_version) {
			$this->install();
		} else {
			$this->initialize_db('activate');
		}
	}

	public function deactivate() {}

	public function install()
	{
		$this->initialize_db('install');

		add_option($this->plugin->prefix . '_version', $this->plugin->version);
	}

	public function update()
	{
		$this->initialize_db('update');
		$this->migrations();

		update_option($this->plugin->prefix . '_version', $this->plugin->version);

		$this->plugin->db_version = $this->plugin->version;
	}

	public function initialize_db($operation)
	{
		if ($this->initialized_db) {
			return;
		}

		$this->update_schema();
		$this->init_data($operation);

		$this->initialized_db = true;
	}

	public function update_schema()
	{
		if ($schema = static::schema()) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta($schema);
		}
	}

	public function init_data($operation) {}

	public function migrations() {}

	public function uninstall() {}
}
