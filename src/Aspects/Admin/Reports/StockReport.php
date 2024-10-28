<?php
namespace Mewz\WCAS\Aspects\Admin\Reports;

use Mewz\Framework\Base\Aspect;
use Mewz\WCAS\Classes\AttributeStockReport;
use Mewz\WCAS\Models\AttributeStock;

class StockReport extends Aspect
{
	public $enqueue = '@admin/reports';

	public function __hooks()
	{
		add_filter('woocommerce_admin_reports', [$this, 'admin_reports']);
	}

	public function admin_reports($reports)
	{
		$reports[AttributeStock::POST_TYPE] = [
			'title' => __('Attribute Stock', 'woocommerce-attribute-stock'),
			'reports' => [
				'low_in_stock' => [
					'title' => __('Low in stock', 'woocommerce'),
					'description' => '',
					'hide_title' => true,
					'callback' => [$this, 'output_report'],
				],
				'out_of_stock' => [
					'title' => __('Out of stock', 'woocommerce'),
					'description' => '',
					'hide_title' => true,
					'callback' => [$this, 'output_report'],
				],
				'most_stocked' => [
					'title' => __('Most stocked', 'woocommerce'),
					'description' => '',
					'hide_title' => true,
					'callback' => [$this, 'output_report'],
				],
			],
		];

		return $reports;
	}

	public function output_report($type)
	{
		$report = new AttributeStockReport();
		$report->output_report($type);
	}
}
