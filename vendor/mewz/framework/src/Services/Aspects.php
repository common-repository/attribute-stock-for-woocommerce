<?php
namespace Mewz\Framework\Services;

use Mewz\Framework\Plugin;
use Mewz\Framework\Base\Aspect;

/**
 * @template T
 */
class Aspects
{
	/** @var Plugin */
	protected $plugin;

	/** @var T[]|Aspect[] */
	protected $aspects = [];

	/**
	 * @param Plugin $plugin
	 */
	public function __construct($plugin)
	{
		$this->plugin = $plugin;
	}

	/**
	 * Loads and runs an aspect.
	 *
	 * @param class-string<T>|class-string<T>[] $aspect The aspect's fully qualified class name
	 *
	 * @return T|Aspect|bool
	 */
	public function load($aspect)
	{
		if (is_array($aspect)) {
			foreach ($aspect as $key => $value) {
				if ($value) {
					$this->load(is_string($key) ? $key : $value);
				}
			}

			return true;
		}

		// allow the aspect class to be swapped out for a different class or not be loaded at all
		$class = apply_filters($this->plugin->prefix . '_aspect_class', $aspect);
		if (!$class) return false;

		if (isset($this->aspects[$class])) {
			return $this->aspects[$class];
		}

		/** @var Aspect $aspect */
		$aspect = new $class($this->plugin);

		if ($aspect->__load() === false) {
			return false;
		}

		$aspect->__hooks();

		return $this->aspects[$class] = $aspect;
	}

	/**
	 * Retrieves the loaded instance of an aspect class.
	 *
	 * @param class-string<T> $class The aspect's fully qualified class name
	 *
	 * @return T|Aspect|false
	 */
	public function get($class)
	{
		$class = apply_filters($this->plugin->prefix . '_aspect_class', $class);

		if (!$class || !isset($this->aspects[$class])) {
			return false;
		}

		return $this->aspects[$class];
	}

	/**
	 * Enqueues declared scripts and calls the `__scripts()` method for each aspect.
	 */
	public function enqueue_scripts()
	{
		$scripts = $this->plugin->scripts;

		foreach ($this->aspects as $aspect) {
			if ($aspect->enqueue) {
				foreach ((array)$aspect->enqueue as $handle) {
					if ($scripts->is_registered('js', $handle)) {
						$scripts->enqueue_js($handle);
					}

					if ($scripts->is_registered('css', $handle)) {
						$scripts->enqueue_css($handle);
					}
				}
			}

			$aspect->__scripts();
		}
	}
}
