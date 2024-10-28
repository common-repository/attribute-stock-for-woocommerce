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
use Mewz\Framework\Util\Admin;

class Authorizer
{
	/** @var Plugin */
	protected $plugin;

	/** @var Envato */
	protected $envato;

	/** @var string */
	protected $auth_name;

	public function __construct(Plugin $plugin, Envato $envato)
	{
		global $plugin_page;

		if ($plugin_page === 'envato-market' || $plugin->context->ajax || $envato->using_envato_plugin()) {
			return;
		}

		$this->plugin = $plugin;
		$this->envato = $envato;
		$this->auth_name = $this->plugin->prefix . '_envato_auth';

		$check_action = $this->auth_name . '_check';

		if (isset($_GET[$check_action])) {
			$this->check_envato_auth();

			add_filter('removable_query_args', static function($query_args) use ($check_action) {
				$query_args[] = $check_action;
				return $query_args;
			});
		}

		if (strlen($envato->get_refresh_token()) === 32) {
			return;
		}

		$this->show_envato_auth_notice();
		$this->handle_envato_auth_return();
	}

	protected function check_envato_auth()
	{
		$type = 'error';

		if (!$this->envato->authorized()) {
			$message = esc_html__('Envato authorization failed. Please try again.', 'mewz-framework');
		}
		elseif (!$this->envato->verify_purchase()) {
			$this->envato->delete_tokens();
			$message = sprintf(__('Your purchase could not be verified. Please make sure you\'re signed into the Envato account that purchased <a href="%s" target="_blank">%s</a>. If you need a license, you can <a href="%s" target="_blank">buy one</a> now.', 'mewz-framework'), $this->plugin->sale_url(), $this->plugin->name, $this->plugin->sale_url());
		}
		else {
			$type = 'success';
			$message = esc_html__('Your purchase has been verified successfully. Thank you for using our plugin! ❤️', 'mewz-framework');
		}

		$message = '<strong>' . $this->plugin->name . '</strong> &mdash; ' . $message;

		$output_notice = static function() use ($type, $message) {
			Admin::display_notice($type, '<p>' . $message . ' <span style="display: none;">' . mt_rand() . '</span></p>', true, true);
		};

		add_action('admin_notices', $output_notice, $this->get_notice_priority());
		add_action('network_admin_notices', $output_notice, $this->get_notice_priority());
	}

	protected function show_envato_auth_notice()
	{
		$self = $this;

		$return_url = admin_url('admin-post.php?action=' . $this->auth_name . '_return&_wpnonce=' . wp_create_nonce($this->auth_name . '_return'));
		$url = $this->envato->get_auth_url($return_url);

		$link = '<a href="#" onclick="return ' . $self->auth_name . '(this)" style="display: inline-block; color: #80B341;" data-url="' . esc_url($url) . '"><svg viewBox="0 0 62 70" fill="none" xmlns="http://www.w3.org/2000/svg" style="height: 14px; margin: 0 .25em -2px 0;"><path d="M54.4726 3.04156C44.8171 -8.2888 13.5633 13.6622 13.749 41.9247C13.7492 42.2134 13.6548 42.4941 13.4802 42.7239C13.3056 42.9536 13.0605 43.1196 12.7825 43.1964C12.5045 43.2731 12.209 43.2564 11.9415 43.1488C11.6739 43.0412 11.449 42.8486 11.3014 42.6006C8.0799 35.6059 7.66003 27.6408 10.1282 20.3455C10.2456 20.0693 10.2576 19.7597 10.162 19.4752C10.0665 19.1908 9.86994 18.9513 9.60972 18.8023C9.3495 18.6533 9.0437 18.6051 8.75032 18.6668C8.45695 18.7286 8.19642 18.896 8.01815 19.1373C2.85332 24.7197 -0.0110427 32.0513 3.19946e-05 39.6603C3.19946e-05 59.5497 16.4752 70.3055 30.3001 69.9929C72.9396 69.0212 63.0815 13.1468 54.4726 3.04156Z" fill="currentColor" /></svg><span class="text">' . esc_html__('Authorize with Envato', 'mewz-framework') . '</span></a>';

		$output_js = static function() use ($self) { ?>
			<script type="text/javascript">
				function <?= $self->auth_name ?>(link) {
					if (link.dataset.clicked) return false;
					else link.dataset.clicked = 'true';
					const popup = window.open(link.dataset.url, '<?= $self->auth_name ?>', 'width=620,height=660');
					const interval = setInterval(function() {
						if (!popup.closed) return;
						clearInterval(interval);
						let url = window.location.href;
						url += (url.indexOf('?') === -1 ? '?' : '&') + '<?= $self->auth_name ?>_check';
						window.location = url;
					}, 100)
					link.removeAttribute('href');
					link.querySelector('.text').textContent = <?= json_encode(__('Authorizing...', 'mewz-framework')) ?>;
					return false;
				}
			</script>
		<?php };

		if (!WP_DEBUG) {
			$output_notice = static function() use ($self, $link) { ?>
				<div class="notice notice-info">
					<p style="overflow: hidden;">
						<strong><?= $self->plugin->name ?></strong> &mdash; <?= esc_html__('Please connect your CodeCanyon account to enable plugin updates.', 'mewz-framework') ?>
						<?= $link ?>
						<span style="display: none;"><?= mt_rand() ?></span>
						<span style="float: right; margin-left: 10px;"><?= sprintf(__('Need a license? <a href="%s" target="_blank">Buy one</a>', 'mewz-framework'), $self->plugin->sale_url()) ?></span>
					</p>
				</div>
			<?php };

			add_action('admin_print_scripts', $output_js);
			add_action('admin_notices', $output_notice, $this->get_notice_priority());
			add_action('network_admin_notices', $output_notice, $this->get_notice_priority());
		} else {
			add_action('load-plugins.php', static function() use ($output_js) {
				add_action('admin_print_scripts', $output_js);
			});
		}

		add_filter('plugin_row_meta', static function($meta_links, $plugin_basename, $plugin_data, $status) use ($self, $link) {
			if ($plugin_basename === $self->plugin->basename) {
				$meta_links[] = $link;
			}

			return $meta_links;
		}, 20, 4);
	}

	protected function handle_envato_auth_return()
	{
		$action = $this->auth_name . '_return';
		$envato = $this->envato;

		add_action("admin_post_{$action}", static function() use ($action, $envato) {
			check_admin_referer($action);

			$envato->save_tokens($_GET);

			echo '<script type="text/javascript">window.close();</script>';
			die;
		});
	}

	protected function get_notice_priority()
	{
	    static $priority;
	    return $priority ??= mt_rand(-100, -1);
	}
}
