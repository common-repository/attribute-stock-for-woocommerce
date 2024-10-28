<?php
namespace Mewz\Framework\Services;

use Mewz\Framework\Plugin;

class Assets
{
	/** @var string */
	protected $base_url;

	/** @var string */
	protected $base_dir;

	/**
	 * @param Plugin $plugin
	 * @param string $base_path Assets directory base path
	 */
	public function __construct($plugin, $base_path = 'assets')
	{
		$base_path = trim($base_path, '/\\') . '/';

		$this->base_url = $plugin->base_url . $base_path;
		$this->base_dir = $plugin->base_dir . '/' . $base_path;
	}

	public function url($path = '')
	{
		return $this->base_url . $path;
	}

	public function dir($path = '')
	{
		return $this->base_dir . $path;
	}

	public function dist($path)
	{
		return $this->url('dist/' . $path);
	}

	public function img($path)
	{
		return $this->url('img/' . $path);
	}

	public function vendor($path)
	{
		return $this->url('vendor/' . $path);
	}
}
