<?php
namespace Mewz\Framework;

use Mewz\Framework\Util\Multilang;
use Mewz\Framework\Services\Envato;

/**
 * @property string $base_url
 * @property string $basename
 * @property string $dirname
 * @property string $db_version
 *
 * @property Core\Installer $installer
 * @property Core\Loader $loader
 */
#[\AllowDynamicProperties]
class Plugin implements Base\ServiceAccess
{
	public $fw_version = '2.0.0';

	public $version;
	public $name;
	public $slug;
	public $prefix;
	public $base_file;
	public $base_dir;

	/** @var static[] */
	protected static $instance = [];

	/**
	 * Initializes the plugin object and all necessary lifecycle hooks.
	 *
	 * @return static
	 */
	public static function run()
	{
		$plugin = self::$instance[static::class] = new static();

		register_activation_hook($plugin->base_file, [$plugin, '__activate']);
		register_deactivation_hook($plugin->base_file, [$plugin, '__deactivate']);
		register_uninstall_hook($plugin->base_file, [static::class, '__uninstall']);

		$plugin->loader->register_tables();

		add_action('plugins_loaded', [$plugin, '__load']);

		if (!static::__LITE) {
			$plugin->__load_updater();
		}

		return $plugin;
	}

	/**
	 * @return static
	 */
	public static function instance()
	{
		return self::$instance[static::class];
	}

	public function __activate()
	{
		$this->installer->activate();

		if (!static::__LITE) {
			delete_site_transient('update_plugins');
			delete_site_transient('envato_market_plugins');
		}
	}

	public function __deactivate()
	{
		$this->installer->deactivate();

		if (!static::__LITE) {
			delete_site_transient('update_plugins');
			delete_site_transient('envato_market_plugins');
		}
	}

	public static function __uninstall()
	{
		static::instance()->installer->uninstall();
	}

	public function __load()
	{
		$this->loader->load();
	}

	public function __load_updater()
	{
		require_once __DIR__ . '/Services/Envato.php';
		$envato = new Envato($this, static::__ENVATO_ID);

		require_once __DIR__ . '/Core/Updater.php';
		new Core\Updater($this, static::__WPORG_SLUG, $envato);

		$plugin = $this;

		add_action('admin_init', static function() use ($plugin, $envato) {
			require_once __DIR__ . '/Core/Authorizer.php';
			new Core\Authorizer($plugin, $envato);
		}, mt_rand(-100, 0));
	}

	public function __is_lite()
	{
		return static::__LITE;
	}

	/**
	 * Lazy-loads a value, object or service from a `get` method below and dynamically stores it as
	 * a property for quick re-access (bypassing {@see __get()} for subsequent retrievals).
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function __get($name)
	{
		return $this->$name = $this->{'get_' . $name}();
	}

	protected function get_base_url()
	{
		return plugin_dir_url($this->base_file);
	}

	protected function get_basename()
	{
		return plugin_basename($this->base_file);
	}

	protected function get_dirname()
	{
		return dirname($this->basename);
	}

	protected function get_db_version()
	{
		return get_option($this->prefix . '_version');
	}

	protected function get_installer()
	{
		return new Core\Installer($this);
	}

	protected function get_loader()
	{
		return new Core\Loader($this);
	}

	protected function get_aspects()
	{
		return new Services\Aspects($this);
	}

	protected function get_assets()
	{
		return new Services\Assets($this);
	}

	protected function get_cache()
	{
		$version = $this->version . Multilang::get_lang_suffix(null, '_');

		return new Services\Cache($this->prefix, $version, !defined('MEWZ_CACHE') || MEWZ_CACHE);
	}

	protected function get_context()
	{
		return new Services\Context($this);
	}

	protected function get_scripts()
	{
		return new Services\Scripts($this);
	}

	protected function get_tasks()
	{
		return new Services\Tasks($this);
	}

	protected function get_view()
	{
		return new Services\View($this);
	}
}
