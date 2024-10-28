<?php
namespace Mewz\WCAS\Aspects\Admin\Plugin;

use Mewz\Framework\Base\Aspect;

class PluginHelp extends Aspect
{
	public function __hooks()
	{
		add_action('current_screen', [$this, 'add_help_tab'], 60);
		add_action('load-edit.php', [$this, 'remove_yoast_help_tab'], 20);
	}

	public function add_help_tab(\WP_Screen $screen)
	{
		$screen->remove_help_tabs();
		$screen->set_help_sidebar('');

		$screen->add_help_tab([
			'id' => 'mewz_wcas_support',
			'title' => __('Help & Support', 'woocommerce-attribute-stock'),
			'content' => '
		        <h2>' . esc_html__('Help & Support', 'woocommerce-attribute-stock') . '</h2>
		        <p>' . esc_html__('Need assistance setting up your attribute stock? Found a bug and want to report it? Just feel like chatting? Get in touch!', 'woocommerce-attribute-stock') . '</p>
		        <p>
		            <a href="' . esc_url($this->plugin->support_url()) . '" class="button button-primary" target="_blank">' . esc_html__('Get support', 'woocommerce-attribute-stock') . '</a>
		        </p>
		    ',
		]);

		$screen->add_help_tab([
			'id' => 'mewz_wcas_documentation',
			'title' => __('Documentation'),
			'content' => '
		        <h2>' . esc_html__('Documentation') . '</h2>
		        <p>' . sprintf(esc_html__('Want to learn more about %s? Check out the official online documentation.', 'woocommerce-attribute-stock'), MEWZ_WCAS_NAME) . '</p>
		        <p>
			        <a href="' . esc_url($this->plugin->docs_url()) . '" class="button button-primary" target="_blank">' . esc_html__('Online documentation', 'woocommerce-attribute-stock') . '</a>
		        </p>
		    ',
		]);
	}

	public function remove_yoast_help_tab()
	{
	    $screen = get_current_screen();

	    if ($screen) {
		    $screen->remove_help_tab('yst-columns');
	    }
	}
}
