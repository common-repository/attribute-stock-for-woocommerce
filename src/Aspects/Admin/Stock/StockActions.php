<?php
namespace Mewz\WCAS\Aspects\Admin\Stock;

use Mewz\Framework\Base\Aspect;
use Mewz\Framework\Util\Admin;
use Mewz\WCAS\Models\AttributeStock;

class StockActions extends Aspect
{
	protected $notices = [];
	protected $row_actions;

	public function __hooks()
	{
		// gather all notices to show before the $_GET vars are unset by WP
		$this->prepare_notices();

		// show action notices on list screen
		add_action('admin_notices', [$this, 'admin_notices']);
		add_filter('removable_query_args', [$this, 'removable_query_args']);

		// replace default row actions with our own
		add_filter('post_row_actions', [$this, 'post_row_actions'], 10, 2);
		add_action('mewz_wcas_stock_row_actions_output', [$this, 'stock_row_actions_output']);

		// custom actions
		add_action('post_action_mewz_wcas_duplicate', [$this, 'action_duplicate']);
	}

	public function __scripts()
	{
		if ($this->context->screen_id === 'edit-' . AttributeStock::POST_TYPE) {
			if (current_user_can('import')) {
				$params['import_url'] = admin_url('admin-post.php?action=mewz_wcas_import&_wpnonce=' . wp_create_nonce('mewz_wcas_import'));
			}

			if (current_user_can('export')) {
				$params['export_url'] = admin_url('admin-post.php?action=mewz_wcas_export&_wpnonce=' . wp_create_nonce('mewz_wcas_export'));
			}

			if (!MEWZ_WCAS_LITE && current_user_can('manage_woocommerce')) {
				$params['settings_url'] = admin_url('admin.php?page=wc-settings&tab=products&section=inventory#mewz-wcas-settings');
			}

			if (!empty($params)) {
				$this->scripts->export_data('headerActions', [
					'html' => trim($this->view->render('admin/stock/header-actions', $params, true)),
				]);
			}
		}
		elseif ($this->context->screen_id === AttributeStock::POST_TYPE) {
			global $post;

			$params['duplicate_url'] = $this->get_action_url($post->ID, 'duplicate');

			$this->scripts->export_data('headerActions', [
				'html' => trim($this->view->render('admin/stock/header-actions', $params, true)),
			]);
		}
	}

	public function prepare_notices()
	{
		foreach (['success', 'error', 'warning', 'info'] as $type) {
			if (!empty($_GET[$type])) {
				if (is_array($_GET[$type])) {
					$this->notices[$type] = array_map('stripslashes', $_GET[$type]);
				} else {
					$this->notices[$type] = stripslashes($_GET[$type]);
				}
			}
		}
	}

	public function admin_notices()
	{
		foreach ($this->notices as $type => $message) {
			if (is_array($message)) {
				foreach ($message as $msg) {
					Admin::display_notice($type, $msg);
				}
			} else {
				Admin::display_notice($type, $message);
			}
		}
	}

	public function removable_query_args($query_args)
	{
		$query_args[] = 'success';
		$query_args[] = 'warning';
		$query_args[] = 'info';
		$query_args[] = 'back';

		return $query_args;
	}

	public function post_row_actions($actions, $post)
	{
		// save default row actions and remove them from display
		if ($post->post_type === AttributeStock::POST_TYPE) {
			$this->row_actions = $actions;
			$actions = ['' => ''];
		}

		return $actions;
	}

	public function stock_row_actions_output(AttributeStock $stock)
	{
		$actions = [];

		if (!$stock->trashed()) {
			$actions['duplicate'] = [
				'url' => $this->get_action_url($stock->id(), 'duplicate'),
				'title' => __('Duplicate', 'woocommerce'),
			];
		}

		if (isset($this->row_actions['trash'])) {
			$actions['trash'] = [
				'url' => get_delete_post_link($stock->id()),
				'title' => _x('Trash', 'verb'),
			];
		}

		if (isset($this->row_actions['untrash'])) {
			$actions['untrash'] = [
				'url' => wp_nonce_url(admin_url('post.php?post=' . $stock->id() . '&amp;action=untrash'), 'untrash-post_' . $stock->id()),
				'title' => __('Restore'),
			];
		}

		$actions = apply_filters('mewz_wcas_stock_list_actions', $actions, $stock);
		if (!$actions) return;

		echo '<span class="stock-row-actions">';

		foreach ($actions as $key => $action) {
			if ($action['url']) {
				echo '<a href="' . esc_url($action['url']) . '" class="action-button action-' . esc_attr($key) . '" title="' . esc_attr($action['title']) . '"></a>';
			}
		}

		echo '</span>';
	}

	public function action_duplicate($post_id)
	{
		$this->validate_action('duplicate', $post_id);

		$stock = AttributeStock::instance($post_id, 'edit');

		if ($stock->enabled() && !current_user_can('publish_post', $post_id)) {
			$stock->set_enabled(false);
		}

		$copy = $stock->duplicate();

		$message = sprintf(__('Attribute stock "%s" has been duplicated.', 'woocommerce-attribute-stock'), $stock->title());

		Admin::redirect($copy->edit_url(), ['success' => $message]);
	}

	public function get_action_url($stock_id, $action, $capability = 'edit_post')
	{
		$post_type_object = get_post_type_object(AttributeStock::POST_TYPE);

		if (!$post_type_object || !$post_type_object->_edit_link || ($capability && !current_user_can($capability, $stock_id))) {
			return false;
		}

		$action = MEWZ_WCAS_PREFIX . '_' . $action;
		$nonce = wp_create_nonce($action . '_' . $stock_id);
		$params = ['action' => $action, '_wpnonce' => $nonce];

		return add_query_arg($params, sprintf($post_type_object->_edit_link, $stock_id));
	}

	public function validate_action($action, $post_id, $capability = 'edit_post')
	{
		check_admin_referer(MEWZ_WCAS_PREFIX . '_' . $action . '_' . $post_id);

		if (!current_user_can($capability, $post_id)) {
			wp_die(__('Sorry, you are not allowed to do that.'));
		}
	}
}
