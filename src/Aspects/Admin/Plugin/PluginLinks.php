<?php
namespace Mewz\WCAS\Aspects\Admin\Plugin;

use Mewz\Framework\Base\Aspect;
use Mewz\WCAS\Models\AttributeStock;

class PluginLinks extends Aspect
{
	public function __hooks()
	{
		add_filter('plugin_action_links', [$this, 'plugin_action_links'], 10, 4);
		add_filter('plugin_row_meta', [$this, 'plugin_meta_links'], 10, 4);
	}

	public function plugin_action_links($action_links, $plugin_basename, $plugin_data, $context)
	{
		if ($plugin_basename === $this->plugin->basename) {
			if (current_user_can('edit_attribute_stock')) {
				$add_links['manage'] = '<a href="' . esc_url(AttributeStock::admin_url()) . '">' . esc_html__('Manage', 'woocommerce') . '</a>';
			}

			if (!MEWZ_WCAS_LITE && current_user_can('manage_woocommerce')) {
				$add_links['settings'] = '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=products&section=inventory#mewz-wcas-settings')) . '">' . esc_html__('Settings') . '</a>';
			}

			if (!empty($add_links)) {
				$action_links = $add_links + $action_links;
			}
		}

		return $action_links;
	}

	public function plugin_meta_links($meta_links, $plugin_basename, $plugin_data, $status)
	{
		if ($plugin_basename === $this->plugin->basename) {
			$meta_links[] = '<a href="' . esc_url($this->plugin->docs_url()) . '" target="_blank">' . esc_html__('Documentation') . '</a>';

			if (MEWZ_WCAS_LITE) {
				$meta_links[] = '<a href="' . esc_url($this->plugin->sale_url()) . '" target="_blank">' . esc_html__('Purchase', 'woocommerce') . '</a>';
			} else {
				$meta_links[] = '<a href="' . esc_url($this->plugin->support_url()) . '" target="_blank">' . esc_html__('Support') . '</a>';
			}
		}

		return $meta_links;
	}
}
