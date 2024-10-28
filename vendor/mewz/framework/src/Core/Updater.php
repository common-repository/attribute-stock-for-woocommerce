<?php
/**
 * If you're looking at this code to try and disable purchase verification -- well, I can't stop you.
 *
 * But, please consider that I am one developer working day and night to offer quality plugins
 * to the WordPress/WooCommerce community that solve real problems and save hard-working business
 * owners a lot of time and frustration. I am also not charging an arm and a leg like many other
 * plugins are.
 *
 * I currently get ~$25 from Envato for every $39 purchase (before taxes), which is barely enough
 * to cover my lunch and coffee in a day. But without this extra income I would not be able to
 * keep up with the necessary development and support.
 *
 * So please keep in mind, every version you use or share without buying is another nail in
 * the coffin that will eventually lead to this plugin being abandoned like so many before it.
 */

namespace Mewz\Framework\Core;

use Mewz\Framework\Plugin;
use Mewz\Framework\Services\Envato;

class Updater
{
	/** @var Plugin */
	protected $plugin;

	/** @var string */
	protected $wporg_slug;

	/** @var Envato|null */
	protected $envato;

	/** @var string */
	protected $lite;

	/** @var string */
	protected $full;

	/** @var array */
	protected $envato_data;

	public function __construct(Plugin $plugin, $wporg_slug, Envato $envato = null)
	{
		$this->plugin = $plugin;
		$this->envato = $envato;

		$this->wporg_slug = $wporg_slug;
		$this->lite = "{$wporg_slug}/{$wporg_slug}.php";
		$this->full = $plugin->basename;

		add_filter('plugins_api_args', self::cb([$this, 'details_request']), 0, 2);
		add_filter('plugins_api_result', self::cb([$this, 'details_result']), 0, 3);

		add_filter('http_request_args', self::cb([$this, 'update_request']), 0, 2);
		add_filter('http_response', self::cb([$this, 'update_response']), 0, 3);

		add_filter('upgrader_package_options', self::cb([$this, 'upgrader_package']), 0);

		add_action('set_site_transient_envato_market_plugins', self::cb([$this, 'check_envato_plugins']), 0);

		add_filter('pre_set_site_transient_update_plugins', self::cb([$this, 'envato_data_before']), 0);
		add_filter('pre_set_site_transient_update_plugins', self::cb([$this, 'envato_data_after']), 7);
	}

	protected static function cb($fn)
	{
	    return static function(...$args) use ($fn) {
			return $fn(...$args);
		};
	}

	protected function details_request($args, $action)
	{
		if ($action === 'plugin_information' && $args->slug === $this->plugin->slug) {
			$args->slug = $this->wporg_slug;
		}

		return $args;
	}

	protected function details_result($res, $action, $args)
	{
		if (
			$action !== 'plugin_information'
			|| $args->slug !== $this->wporg_slug
			|| $res instanceof \WP_Error
			|| empty($res->slug)
			|| empty($res->sections)
			|| $res->slug !== $this->wporg_slug
		) {
			return $res;
		}

		$plugin_info = $this->envato->get_plugin_info();

		if (!$plugin_info instanceof \WP_Error) {
			$plugin_info['name'] = htmlentities($plugin_info['name']);

			foreach ($plugin_info as $key => $value) {
				$res->$key = $value;
			}
		}

		$res->slug = $this->plugin->slug;
		$res->download_link = $this->plugin->sale_url();
		$res->external = true;
		$res->ratings = null;
		$res->versions = [];

		unset(
			$res->sections['installation'],
			$res->sections['faq'],
			$res->sections['reviews']
		);

		if (!empty($res->sections['description'])) {
			$envato_id = $this->envato->get_item_id();
			$remove = ['@<blockquote>.*?' . $envato_id . '.*?</blockquote>@is', '@\(.*?' . $envato_id . '.*?\)@is'];

			$res->sections['description'] = preg_replace($remove, '', $res->sections['description']);
		}

		return $res;
	}

	protected function update_request($args, $url)
	{
		if (strpos($url, '//api.wordpress.org/plugins/update-check/') === false || empty($args['body']['plugins'])) {
			return $args;
		}

		$plugins = json_decode($args['body']['plugins'], true);
		if (!$plugins) return $args;

		if (isset($plugins['plugins'][$this->full])) {
			$props = $plugins['plugins'][$this->full];

			$props['Name'] .= ' (Lite)';
			$props['Title'] = $props['Name'];

			$plugins['plugins'][$this->lite] = $props;
			unset($plugins['plugins'][$this->full]);

			ksort($plugins['plugins']);
		}

		if (!empty($plugins['active'])) {
			foreach ($plugins['active'] as $i => $active) {
				if ($active === $this->full) {
					$plugins['active'][$i] = $this->lite;
				} elseif ($active === $this->lite) {
					unset($plugins['active'][$i]);
				}
			}

			sort($plugins['active']);
		}

		$args['body']['plugins'] = json_encode($plugins);

		if (wp_doing_cron() && !$this->envato->using_envato_plugin()) {
			$this->envato->validate();
		}

		return $args;
	}

	protected function update_response($response, $args, $url)
	{
		if (strpos($url, '//api.wordpress.org/plugins/update-check/') === false || empty($response['body'])) {
			return $response;
		}

		$data = json_decode($response['body'], true);
		if (!$data) return $response;

		$sale_url = $this->plugin->sale_url();

		foreach (['plugins', 'translations', 'no_update'] as $type) {
			if (empty($data[$type][$this->lite])) continue;

			$props = $data[$type][$this->lite];

			if (isset($props['slug'])) $props['slug'] = $this->plugin->slug;
			if (isset($props['plugin'])) $props['plugin'] = $this->full;
			if (isset($props['url'])) $props['url'] = $sale_url;
			if (isset($props['package'])) $props['package'] = $sale_url;

			$data[$type][$this->full] = $props;

			unset($data[$type][$this->lite]);
		}

		$response['body'] = json_encode($data);

		return $response;
	}

	protected function upgrader_package($options)
	{
		if ($options['package'] === $this->plugin->sale_url()) {
			$options['package'] = $this->envato->get_download_url() ?: '';
		}

		return $options;
	}

	protected function check_envato_plugins($plugins)
	{
		$envato_id = $this->envato->get_item_id();
		$purchased = false;

		if (!empty($plugins['purchased'])) {
			foreach ($plugins['purchased'] as $plugin) {
				if ($plugin['id'] == $envato_id) {
					$purchased = true;
					break;
				}
			}
		}

		$unlisted = (bool)get_option($this->plugin->prefix . '_envato_unlisted');

		if ($purchased === $unlisted) {
			update_option($this->plugin->prefix . '_envato_unlisted', (int)!$purchased);
		}
	}

	protected function envato_data_before($data)
	{
		if (!$this->envato_data && $this->envato->using_envato_plugin() && isset($data->response[$this->full]->id)) {
			$this->envato_data = (array)$data->response[$this->full];
		}

		return $data;
	}

	protected function envato_data_after($data)
	{
		if ($this->envato_data && isset($data->response[$this->full])) {
			$data->response[$this->full] = (object)(array_merge($this->envato_data, (array)$data->response[$this->full]));
		}

		return $data;
	}
}
