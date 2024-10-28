<?php
namespace Mewz\Framework\Base;

use Mewz\Framework\Plugin;

#[\AllowDynamicProperties]
abstract class ServiceProxy implements ServiceAccess
{
	/** @var Plugin */
	protected $plugin;

	public function __construct(Plugin $plugin)
	{
		$this->plugin = $plugin;
	}

	public function __get($name)
	{
		return $this->$name = $this->plugin->$name;
	}
}
