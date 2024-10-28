<?php
namespace Mewz\Framework\Services;

use Mewz\Framework\Plugin;

/**
 * @property bool $admin
 * @property bool $ajax
 * @property bool $rest
 * @property bool $ajax_or_rest
 * @property bool $cron
 * @property bool $cli
 * @property bool $task
 * @property bool $front
 * @property bool $front_referer
 * @property bool $referer
 *
 * @property string $page
 * @property string $plugin_page
 * @property string $page_hook
 * @property string $post_type
 * @property string $taxonomy
 * @property string $logged_in
 * @property string $screen_id
 */
#[\AllowDynamicProperties]
class Context
{
	/** @var Plugin */
	protected $plugin;

	public function __construct(Plugin $plugin)
	{
		$this->plugin = $plugin;
	}

	public function __get($name)
	{
		$value = apply_filters('mewz_context_' . $name, $this->$name());

		if ($value !== null) {
			$this->$name = $value;
		}

		return $value;
	}

	public function __call($name, $args)
	{
		$value = $this->$name();

		if ($value === null) {
			unset($this->$name);
		} else {
			$this->$name = $value;
		}

		return $value;
	}

	public function screen($prop)
	{
		static $screen;

		$screen ??= get_current_screen();

		return $screen ? $screen->$prop : null;
	}

	protected function admin()
	{
		return is_admin();
	}

	protected function ajax()
	{
		return wp_doing_ajax();
	}

	protected function rest()
	{
		global $wp;

		if (defined('REST_REQUEST')) {
			return REST_REQUEST;
		}

		if (!empty($wp->query_vars['rest_route']) || !empty($_GET['rest_route'])) {
			return true;
		}

		if (!empty($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/' . rest_get_url_prefix() . '/') === 0) {
			return true;
		}

		return null;
	}

	protected function ajax_or_rest()
	{
		return $this->ajax || $this->rest;
	}

	protected function cron()
	{
		return apply_filters('wp_doing_cron', defined('DOING_CRON') && DOING_CRON);
	}

	protected function cli()
	{
		return defined('WP_CLI') && WP_CLI;
	}

	protected function task()
	{
		return (
			(defined('MEWZ_DOING_TASK') && MEWZ_DOING_TASK)
			|| (
				$this->ajax && isset($_REQUEST['action']) && is_string($_REQUEST['action'])
				&& (
					$_REQUEST['action'] === $this->plugin->tasks->get_action()
					|| preg_match('/(sync|task|job|send|mail)/i', $_REQUEST['action'])
				)
			)
			|| (
				$this->admin
				&& isset($_REQUEST['tab'], $_REQUEST['row_action'])
				&& $_REQUEST['tab'] === 'action-scheduler'
				&& $_REQUEST['row_action'] === 'run'
			)
		);
	}

	protected function front()
	{
		if ($this->cron || $this->cli) {
			return false;
		}

		if ($this->admin) {
			return $this->ajax ? $this->front_referer : false;
		}

		if ($this->rest) {
			return $this->front_referer;
		}

		return true;
	}

	protected function front_referer()
	{
		$referer = $this->referer;
		if (!$referer) return false;

		return (
			// referer is this site
			strpos($referer, site_url('', 'http')) === 0
			// but not wp-admin
			&& strpos($referer, admin_url('', 'http')) !== 0
			// and not wp-login
			&& strpos($referer, site_url('wp-login.php', 'http')) !== 0
			// and not a rest request
			&& strpos($referer, '?rest_route=') === false
			&& strpos($referer, '/' . rest_get_url_prefix() . '/') === false
		);
	}

	protected function referer()
	{
		$referer = wp_get_raw_referer();
		if (!$referer) return false;

		if ($referer[0] === '/') {
			$referer = site_url($referer, 'http');
		} elseif (strpos($referer, 'https') === 0) {
			$referer = 'http' . substr($referer, 5);
		}

		return $referer;
	}

	protected function page()
	{
		global $pagenow;
		return $pagenow;
	}

	protected function plugin_page()
	{
		global $plugin_page;

		$value = $plugin_page ?: ($_REQUEST['page'] ?? null);

		return $value && is_string($value) ? $value : null;
	}

	protected function page_hook()
	{
		global $hook_suffix;

		return $hook_suffix;
	}

	protected function post_type()
	{
		global $typenow;

		$value = $typenow ?: ($_REQUEST['post_type'] ?? null);

		return $value && is_string($value) ? $value : null;
	}

	protected function taxonomy()
	{
		global $taxnow;

		$value = $taxnow ?: ($_REQUEST['taxonomy'] ?? null);

		return $value && is_string($value) ? $value : null;
	}

	protected function logged_in()
	{
		return is_user_logged_in();
	}

	protected function screen_id()
	{
		return $this->screen('id');
	}
}
