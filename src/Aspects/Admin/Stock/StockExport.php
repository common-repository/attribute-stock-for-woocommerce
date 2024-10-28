<?php
namespace Mewz\WCAS\Aspects\Admin\Stock;

use Mewz\Framework\Base\Aspect;
use Mewz\Framework\Util\Admin;
use Mewz\WCAS\Models\AttributeStock;
use Mewz\WCAS\Util\Export;

class StockExport extends Aspect
{
	public function __hooks()
	{
		add_action('admin_post_mewz_wcas_export', [$this, 'export_csv']);
		add_action('admin_post_mewz_wcas_import', [$this, 'import_csv']);
	}

	public function export_csv()
	{
		check_admin_referer('mewz_wcas_export');

		$query_args = apply_filters('mewz_wcas_export_query_args', [
			'orderby' => [
				'post_title' => 'asc',
				'post_date' => 'desc',
			],
		]);

		$stock_ids = AttributeStock::query($query_args, 'edit', 'id');
		$stock_ids = apply_filters('mewz_wcas_export_stock_ids', $stock_ids);

		if (!$stock_ids) {
			$this->redirect(['error' => __('There are no valid attribute stock items to export.', 'woocommerce-attribute-stock')]);
		}

		Export::to_csv_download($stock_ids);
	}

	public function import_csv()
	{
		check_admin_referer('mewz_wcas_import');

		if (empty($_FILES['import_file']) || $_FILES['import_file']['error'] > 0) {
			$this->redirect(['error' => __('The uploaded CSV file is invalid and cannot be imported.', 'woocommerce-attribute-stock')]);
		}

		$csv = fopen($_FILES['import_file']['tmp_name'], 'r');
		$cols = fgetcsv($csv);

		if (!$cols) {
			$this->redirect(['error' => __('The uploaded CSV file is invalid and cannot be imported.', 'woocommerce-attribute-stock')]);
		}

		foreach ($cols as &$col) {
			$col = strtolower(preg_replace('/\W+/', '', $col));
		}
		unset($col);

		$imported_ids = [];
		$added = 0;
		$updated = 0;
		$skipped = 0;

		while ($line = fgetcsv($csv)) {
			if ($line[0] === null) continue;

			$row = [];

			foreach ($cols as $i => $col) {
				$row[$col] = isset($line[$i]) ? trim($line[$i]) : '';
			}

			$result = Export::import_row($row, $imported_ids);

			if (!$result) {
				$skipped++;
			} else {
				$imported_ids[] = $result['stock']->id();

				if ($result['action'] === 'added') {
					$added++;
				} elseif ($result['action'] === 'updated') {
					$updated++;
				}
			}
		}

		if ($added) {
			$messages['success'] = sprintf(_n('%d attribute stock item was added successfully.', '%d attribute stock items were added successfully.', $added, 'woocommerce-attribute-stock'), $added);
		}

		if ($updated) {
			$messages['info'] = sprintf(_n('%d attribute stock item was updated successfully.', '%d attribute stock items were updated successfully.', $updated, 'woocommerce-attribute-stock'), $updated);
		}

		if ($skipped) {
			$messages['warning'] = sprintf(_n('%d row was skipped.', '%d rows were skipped.', $skipped, 'woocommerce-attribute-stock'), $skipped);
		}

		if (!$added && !$updated) {
			$messages['error'] = __('No attribute stock items were imported. Please check the file and try again.', 'woocommerce-attribute-stock');
		}

		$this->redirect($messages ?? null);
	}

	/**
	 * @param string|array $params
	 */
	protected function redirect($params = null)
	{
		Admin::redirect(AttributeStock::admin_url($params));
	}
}
