<?php
namespace Mewz\Framework\Services;

use Mewz\Framework\Plugin;

class Scripts
{
	/** @var Plugin */
	protected $plugin;

	/** @var string Prefix to add to handles starting with "@" */
	public $prefix;

	/** @var string */
	public $js_global;

	/** @var array */
	public $export_data = [];

	/**
	 * @param Plugin $plugin
	 * @param string $prefix
	 * @param string $js_global
	 */
	public function __construct(Plugin $plugin, $prefix = null, $js_global = null)
	{
		$this->plugin = $plugin;
		$this->prefix = ($prefix ?? str_replace('_', '-', $plugin->prefix)) . '-';
		$this->js_global = $js_global ?? lcfirst(str_replace('_', '', ucwords($plugin->prefix, '_')));
	}

	/**
	 * @param string $type 'js' or 'css'
	 * @param string $handle Unique script handle
	 * @param mixed ...$options Array of key/value pairs, optionally following a $path string
	 *                          (Keys: path, deps, ver, media, in_footer, min, translate)
	 *
	 * @return bool
	 */
	public function register($type, $handle, ...$options)
	{
		$args = $this->expand_args($type, $handle, ...$options);

		if ($type === 'js') {
			$result = wp_register_script($args['handle'], $args['path'], $args['deps'], $args['ver'], $args['in_footer']);

			if ($result && !empty($args['translate'])) {
				wp_set_script_translations($args['handle'], $args['translate']);
			}

			return $result;
		}
		elseif ($type === 'css') {
			return wp_register_style($args['handle'], $args['path'], $args['deps'], $args['ver'], $args['media']);
		}

		return false;
	}

	/**
	 * @param string $type 'js' or 'css'
	 * @param string $handle Unique script handle
	 * @param mixed ...$options Array of key/value pairs, optionally following a $path string
	 *                          (Keys: path, deps, ver, media, in_footer, min, translate)
	 */
	public function enqueue($type, $handle, ...$options)
	{
		if ($options) {
			$args = $this->expand_args($type, $handle, ...$options);

			if ($type === 'js') {
				wp_enqueue_script($args['handle'], $args['path'], $args['deps'], $args['ver'], $args['in_footer']);
			} elseif ($type === 'css') {
				wp_enqueue_style($args['handle'], $args['path'], $args['deps'], $args['ver'], $args['media']);
			}
		} elseif ($type === 'js') {
			wp_enqueue_script($this->expand_handle($handle));
		} elseif ($type === 'css') {
			wp_enqueue_style($this->expand_handle($handle));
		}
	}

	/**
	 * @param string $handle Unique script handle
	 * @param mixed ...$options Array of key/value pairs, optionally following a $path string
	 *                          (Keys: path, deps, ver, in_footer, min, translate)
	 *
	 * @return bool
	 */
	public function register_js($handle, ...$options)
	{
		return $this->register('js', $handle, ...$options);
	}

	/**
	 * @param string $handle Unique script handle
	 * @param mixed ...$options Array of key/value pairs, optionally following a $path string
	 *                          (Keys: path, deps, ver, media, min)
	 *
	 * @return bool
	 */
	public function register_css($handle, ...$options)
	{
		return $this->register('css', $handle, ...$options);
	}

	/**
	 * @param string $handle Unique script handle
	 * @param mixed ...$options Array of key/value pairs, optionally following a $path string
	 *                          (Keys: path, deps, ver)
	 *
	 * @return bool
	 */
	public function register_bundle($handle, ...$options)
	{
		return $this->register('js', $handle, ...$options)
			&& $this->register('css', $handle, ...$options);
	}

	/**
	 * @param string $handle Unique script handle
	 * @param mixed ...$options Array of key/value pairs, optionally following a $path string
	 *                          (Keys: path, deps, ver, in_footer, min, translate)
	 */
	public function enqueue_js($handle, ...$options)
	{
		$this->enqueue('js', $handle, ...$options);
	}

	/**
	 * @param string $handle Unique script handle
	 * @param mixed ...$options Array of key/value pairs, optionally following a $path string
	 *                          (Keys: path, deps, ver, media, min)
	 */
	public function enqueue_css($handle, ...$options)
	{
		$this->enqueue('css', $handle, ...$options);
	}

	/**
	 * @param string $handle Unique script handle
	 * @param mixed ...$options Array of key/value pairs, optionally following a $path string
	 *                          (Keys: path, deps, ver)
	 */
	public function enqueue_bundle($handle, ...$options)
	{
		$this->enqueue('js', $handle, ...$options);
		$this->enqueue('css', $handle, ...$options);
	}

	/**
	 * @param string $type 'js' or 'css'
	 * @param string $handle Unique script handle
	 *
	 * @return bool
	 */
	public function is_registered($type, $handle)
	{
		if ($type === 'css') {
			return isset(wp_styles()->registered[$this->expand_handle($handle)]);
		} elseif ($type === 'js') {
			return isset(wp_scripts()->registered[$this->expand_handle($handle)]);
		} else {
			return false;
		}
	}

	/**
	 * @param string $handle
	 *
	 * @return string
	 */
	public function expand_handle($handle)
	{
		if ($handle[0] === '@') {
			$handle = $this->prefix . substr($handle, 1);
		}

		return $handle;
	}

	/**
	 * @param string $type
	 * @param string $handle
	 * @param string|array ...$options
	 *
	 * @return array
	 */
	public function expand_args($type, $handle, ...$options)
	{
		$args = [
			'handle' => $this->expand_handle($handle),
			'path' => null,
			'deps' => [],
			'ver' => null,
			'min' => '.min',
		];

		if ($type === 'css') {
			$args['media'] = 'all';
		} elseif ($type === 'js') {
			$args['deps'] = ['jquery'];
			$args['in_footer'] = true;
		}

		if ($options) {
			if (is_string($options[0])) {
				$args['path'] = $options[0];

				if (isset($options[1])) {
					$args = array_merge($args, $options[1]);
				}
			} else {
				$args = array_merge($args, $options[0]);
			}

			if ($args['path'] !== null) {
				if (SCRIPT_DEBUG && $args['min']) {
					$args['path'] = str_replace($args['min'], '', $args['path']);
				}

				if (strpos($args['path'], '//') === false) {
					$args['path'] = $this->plugin->assets->dist($args['path']);
				}
			}

			if (!empty($args['deps'])) {
				$args['deps'] = (array)$args['deps'];
			}

			if (isset($args['translate']) && $args['translate'] === true && $type === 'js') {
				$args['translate'] = $this->plugin->slug;
			}
		}

		if ($args['path'] === null && $handle !== $args['handle']) { // handle was expanded, i.e. this is a plugin script
			$path = substr($handle, 1);

			if (!SCRIPT_DEBUG && $args['min']) {
				$path .= $args['min'];
			}

			$args['path'] = $this->plugin->assets->dist($path . '.' . $type);
		}

		$args['ver'] ??= $this->plugin->version;

		return $args;
	}

	public function export_data($key, $data = null)
	{
		if ($data !== null) {
			if (isset($this->export_data[$key]) && is_array($this->export_data[$key]) && is_array($data)) {
				$this->export_data[$key] = array_merge($this->export_data[$key], $data);
			} else {
				$this->export_data[$key] = $data;
			}
		}

		return $this->export_data[$key] ?? null;
	}

	public function render_export_data()
	{
		if (!$this->js_global) {
			return false;
		}

		$data = $this->export_data;

		if ($data && $data = json_encode($data)) {
			$data = addcslashes($data, "\\'");
		}

		if (!$data) {
			return false;
		}

		$script = "window.$this->js_global = JSON.parse('$data');";

		return "<script id=\"{$this->plugin->slug}-data-js\">\n{$script}\n</script>\n";
	}
}
