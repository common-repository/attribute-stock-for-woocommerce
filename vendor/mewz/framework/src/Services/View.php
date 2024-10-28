<?php
namespace Mewz\Framework\Services;

use Mewz\Framework\Base\ServiceProxy;
use Mewz\Framework\Plugin;

class View extends ServiceProxy
{
	/** @var string */
	protected $theme_dir;

	/** @var string */
	protected $templates_path;

	/** @var string[] */
	public $path_cache = [];

	/**
	 * @param Plugin $plugin
	 * @param string $theme_dir Frontend theme template override directory name. Defaults to `Plugin::DOMAIN`.
	 * @param string $templates_path Default root path for template files. Defaults to `<plugin>/templates`.
	 */
	public function __construct(Plugin $plugin, $theme_dir = null, $templates_path = null)
	{
		$this->plugin = $plugin;
		$this->theme_dir = $theme_dir ?: $plugin->slug;
		$this->templates_path = $templates_path ?: $plugin->base_dir . '/templates';
	}

	/**
	 * @return string
	 */
	public function theme_dir()
	{
	    return $this->theme_dir;
	}

	/**
	 * @return string
	 */
	public function templates_path()
	{
		return $this->templates_path;
	}

	/**
	 * @param string $template
	 *
	 * @return string
	 */
	public function locate_template($template)
	{
		if (isset($this->path_cache[$template])) {
			return $this->path_cache[$template];
		}

		if (strpos($template, 'admin/') !== 0) {
			$template_path = locate_template($this->theme_dir . '/' . $template . '.php');
		}

		if (empty($template_path)) {
			$template_path = $this->templates_path . '/' . $template . '.php';
		}

		$template_path = apply_filters($this->plugin->prefix . '_template_path', $template_path, $template);

		$this->path_cache[$template] = $template_path;

		return $template_path;
	}

	/**
	 * @param string $template
	 * @param array $data
	 * @param bool $return
	 *
	 * @return string|null|false
	 */
	public function render($template, $data = [], $return = false)
	{
		$template_path = $this->locate_template($template);

		if ($template_path) {
			$data = apply_filters($this->plugin->prefix . '_template_data', $data, $template, $template_path);

			return $this->__output($template_path, $data, $return);
		} else {
			return false;
		}
	}

	/**
	 * @param string $_template_path
	 * @param array $_template_data
	 * @param bool $_template_return
	 *
	 * @return string|null
	 */
	protected function __output($_template_path, $_template_data = [], $_template_return = false)
	{
		extract($_template_data, EXTR_SKIP);
		unset($_template_data);

		if ($_template_return) ob_start();

		include $_template_path;

		return $_template_return ? ob_get_clean() : null;
	}
}
