<?php
namespace Mewz\WCAS\Aspects\Admin\Stock;

use Mewz\Framework\Base\Aspect;
use Mewz\WCAS\Models\AttributeStock;
use Mewz\WCAS\Util\Export;

class StockBulkActions extends Aspect
{
	public function __hooks()
	{
		add_filter('bulk_actions-edit-' . AttributeStock::POST_TYPE, [$this, 'bulk_actions']);
		add_filter('handle_bulk_actions-edit-' . AttributeStock::POST_TYPE, [$this, 'handle_bulk_actions'], 10, 3);
		add_filter('bulk_post_updated_messages', [$this, 'bulk_post_updated_messages'], 10, 2);
	}

	public function bulk_actions($actions)
	{
		unset($actions['edit']);

		$post_status = $_REQUEST['post_status'] ?? null;

		if ($post_status !== 'trash') {
			$custom_actions = [];

			if (current_user_can('publish_attribute_stock')) {
				if ($post_status !== 'publish') {
					$custom_actions['enable'] = __('Enable', 'woocommerce');
				}

				if ($post_status !== 'draft') {
					$custom_actions['disable'] = __('Disable', 'woocommerce');
				}
			}

			$custom_actions['duplicate'] = __('Duplicate', 'woocommerce');

			if (current_user_can('export')) {
				$custom_actions['export'] = __('Export');
			}

			$actions = $custom_actions + $actions;
		}

		return $actions;
	}

	public function handle_bulk_actions($redirect, $action, $post_ids)
	{
		$redirect = remove_query_arg(['success', 'error', 'warning', 'info'], $redirect);

		if (in_array($action, ['enable', 'disable'])) {
			$params = $this->action_change_status($action === 'enable', $post_ids);
		}
		elseif ($action === 'duplicate') {
			$params = $this->action_duplicate($post_ids);
		}
		elseif ($action === 'export') {
			$params = $this->action_export($post_ids);
		}

		if (!empty($params)) {
			$redirect = add_query_arg($params, $redirect);
		}

		return $redirect;
	}

	public function action_change_status($enable, $post_ids)
	{
		$count = 0;

		foreach ($post_ids as $post_id) {
			$stock = new AttributeStock($post_id, 'edit');
			$stock->set_enabled($enable);

			if ($stock->save()) {
				$count++;
			}
		}

		if ($count) {
			if ($enable) {
				$message = sprintf(_n('%d attribute stock enabled.', '%d attribute stocks enabled.', $count, 'woocommerce-attribute-stock'), $count);
			} else {
				$message = sprintf(_n('%d attribute stock disabled.', '%d attribute stocks disabled.', $count, 'woocommerce-attribute-stock'), $count);
			}

			return ['success' => $message];
		} else {
			if ($enable) {
				$message = __('No disabled attribute stocks to enable.', 'woocommerce-attribute-stock');
			} else {
				$message = __('No enabled attribute stocks to disable.', 'woocommerce-attribute-stock');
			}

			return ['warning' => $message];
		}
	}

	public function action_duplicate($post_ids)
	{
		$count = 0;

		foreach ($post_ids as $post_id) {
			$stock = new AttributeStock($post_id, 'edit');

			if ($stock->valid() && $stock->duplicate()) {
				$count++;
			}
		}

		if ($count > 0) {
			return ['success' => sprintf(_n('%d attribute stock duplicated.', '%d attribute stocks duplicated.', $count, 'woocommerce-attribute-stock'), $count)];
		} else {
			return ['warning' => __('No valid attribute stocks to duplicate.', 'woocommerce-attribute-stock')];
		}
	}

	public function action_export($post_ids)
	{
		$query_args = apply_filters('mewz_wcas_export_query_args', [
			'post__in' => $post_ids,
			'orderby' => [
				'post_title' => 'asc',
				'post_date' => 'desc',
			],
		]);

		$stock_ids = AttributeStock::query($query_args, 'edit', 'id');
		$stock_ids = apply_filters('mewz_wcas_export_stock_ids', $stock_ids, $post_ids);

		if (!$stock_ids) {
			return ['warning' => __('No attribute stock items to export.', 'woocommerce-attribute-stock')];
		}

		Export::to_csv_download($stock_ids);
	}

	public function bulk_post_updated_messages($bulk_messages, $bulk_counts)
	{
		global $bulk_counts;

		$bulk_messages[AttributeStock::POST_TYPE] = [
			'updated' => _n('%d attribute stock updated.', '%d attribute stocks updated.', $bulk_counts['updated'], 'woocommerce-attribute-stock'),
			'locked' => _n('%d attribute stock not updated, somebody is editing it.', '%d attribute stocks not updated, somebody is editing them.', $bulk_counts['locked'], 'woocommerce-attribute-stock'),
			'deleted' => _n('%d attribute stock permanently deleted.', '%d attribute stocks permanently deleted.', $bulk_counts['deleted'], 'woocommerce-attribute-stock'),
			'trashed' => _n('%d attribute stock moved to the Trash.', '%d attribute stocks moved to the Trash.', $bulk_counts['trashed'], 'woocommerce-attribute-stock'),
			'untrashed' => _n('%d attribute stock restored from the Trash.', '%d attribute stocks restored from the Trash.', $bulk_counts['untrashed'], 'woocommerce-attribute-stock'),
		];

	    return $bulk_messages;
	}
}
