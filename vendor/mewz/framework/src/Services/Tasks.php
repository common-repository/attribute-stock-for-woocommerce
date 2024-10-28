<?php
namespace Mewz\Framework\Services;

use Mewz\Framework\Plugin;

class Tasks
{
	/** @var Plugin */
	protected $plugin;

	/** @var string */
	protected $action;

	/** @var array */
	protected $tasks = [];

	/** @var int */
	protected $start_time;

	/** @var bool */
	protected $current_request_ended = false;

	public function __construct(Plugin $plugin)
	{
		$this->plugin = $plugin;
		$this->action = $this->plugin->prefix . '_task';
	}

	public function get_action()
	{
		return $this->action;
	}

	public function hook_name($task)
	{
		return $this->action . '_' . $task;
	}

	public function add($task, $data = [])
	{
		$this->tasks[] = [$task, $data];
	}

	public function dispatch_tasks($end_current_request = false)
	{
		if (!$this->tasks) return;

		if ($end_current_request) {
			$this->end_current_request();
		}

		foreach ($this->tasks as $task) {
			$this->dispatch(...$task);
		}

		$this->tasks = [];
	}

	public function dispatch($task, $data = [])
	{
		$url = admin_url(sprintf('admin-ajax.php?action=%s&task=%s&_nonce=%s', urlencode($this->action), urlencode($task), $this->create_nonce($task)));

		$args = [
			'body' => $data,
			'timeout' => 0.01, // actually limited to 1s when doing the request, but this is how WP-Cron does it
			'blocking' => false,
			'sslverify' => apply_filters('https_local_ssl_verify', false),
		];

		$result = wp_remote_post($url, $args);

		if ($result instanceof \WP_Error) {
			wc_get_logger()->error('Dispatch task error: ' . $result->get_error_message(), ['source' => $this->plugin->slug]);
		}
	}

	public function handle($params, $data)
	{
		if (!defined('MEWZ_DOING_TASK')) {
			define('MEWZ_DOING_TASK', true);
		}

		if (empty($params['task']) || empty($params['_nonce'])) {
			return $this->kill(400);
		}

		$task = $params['task'];

		if (!$this->verify_nonce($task, $params['_nonce'])) {
			return $this->kill(403);
		}

		$this->start_time = time();

		do_action($this->plugin->prefix . '_before_prepare_task', $task, $data);

		$this->end_current_request();
		$this->increase_request_limits();

		do_action($this->plugin->prefix . '_before_task', $task, $data);

		do_action($this->hook_name($task), $data, $this->start_time);

		return $this->kill();
	}

	public function near_limits($throttle = 0.1)
	{
		$time_limit = self::get_current_limit('time');

		if ($time_limit > 0 && time() - $this->start_time >= $time_limit - min(10, ceil($time_limit / 2))) {
			return true;
		}

		$memory_limit = self::get_current_limit('memory');

		if ($memory_limit && memory_get_usage(true) >= $memory_limit * .8) {
			return true;
		}

		// wait a bit before moving on to prevent hogging the cpu
		if ($throttle) {
			usleep($throttle * 1000000);
		}

		return false;
	}

	public function create_nonce($task, $tick = null)
	{
		$tick ??= wp_nonce_tick();

		return substr(wp_hash($tick . '|' . $this->hook_name($task), 'nonce'), -12, 10);
	}

	public function verify_nonce($task, $nonce)
	{
		$tick = wp_nonce_tick();

		// nonce generated 0-12 hours ago
		$expected = $this->create_nonce($task, $tick);
		if (hash_equals($expected, $nonce)) return 1;

		// nonce generated 12-24 hours ago
		$expected = $this->create_nonce($task, $tick - 1);
		if (hash_equals($expected, $nonce)) return 2;

		return false;
	}

	public function kill($response_code = 200)
	{
		$ok = $response_code < 400;

		if ($this->plugin->context->ajax) {
			wp_die($ok ? 1 : -1, $response_code);
		}

		if (!$ok) {
			return new \WP_Error('mewz_task_error', '', $response_code);
		}

		return $response_code;
	}

	public function end_current_request()
	{
		if ($this->current_request_ended) return;

		$this->current_request_ended = true;

		// attempt to set the process to low cpu priority
		if (function_exists('proc_nice')) {
			@proc_nice(20);
		}

		// attempt to not lock up other request sessions while processing
		if (function_exists('session_write_close')) {
			session_write_close();
		}

		// attempt to keep running if user aborts the request
		ignore_user_abort(true);

		// attempt to close the client connection and continue in the background
		if (function_exists('fastcgi_finish_request')) {
			fastcgi_finish_request();
		}
		elseif (!headers_sent()) {
			$length = ob_get_level() ? ob_get_length() : 0;
			header("Content-Length: $length");
			ob_end_flush();
			flush();
		}
	}

	public function increase_request_limits($time_limit = 600)
	{
		$this->end_current_request();

		// attempt to allow the request to run longer than usual
		wc_set_time_limit($time_limit);

		// attempt to allow the request to use more memory
		wp_raise_memory_limit();
	}

	public static function get_current_limit($type)
	{
		static $limits = [];

		if (!isset($limits[$type])) {
			if ($type === 'time') {
				$limits[$type] = (int)ini_get('max_execution_time');
			} elseif ($type === 'memory') {
				$limits[$type] = wp_convert_hr_to_bytes(ini_get('memory_limit'));
			}
		}

		return $limits[$type] ?? false;
	}
}
