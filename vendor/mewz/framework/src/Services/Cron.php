<?php
namespace Mewz\Framework\Services;

use Mewz\Framework\Plugin;
use Mewz\Framework\Util\Date;

class Cron
{
	/** @var Plugin */
	protected $plugin;

	/** @var string */
	protected $prefix;

	/** @var array */
	protected $tasks = [];

	/**
	 * @param Plugin $plugin
	 */
	public function __construct(Plugin $plugin)
	{
		$this->plugin = $plugin;
		$this->prefix = $plugin->prefix . '_task_';
	}

	/**
	 * @see wp_schedule_event()
	 *
	 * @param string $name Task name
	 * @param string $interval 'daily', 'weekly', etc.
	 * @param null|string|int $start Start time
	 */
	public function register($name, $interval, $start = null)
	{
		$this->tasks[] = compact('name', 'interval', 'start');
	}

	public function schedule($unschedule = true)
	{
		if ($unschedule) {
			$this->unschedule();
		}

		$time = time();

		foreach ($this->tasks as $task) {
			if ($task['start'] === null) {
				$start = $time;
			} elseif (is_string($task['start'])) {
				$start = Date::local_strtotime($task['start']);
			} else {
				$start = (int)$task['start'];
			}

		    wp_schedule_event($start, $task['interval'], $this->prefix . $task['name']);
		}
	}

	public function unschedule()
	{
		foreach ($this->tasks as $task) {
			wp_clear_scheduled_hook($this->prefix . $task['name']);
		}
	}
}
