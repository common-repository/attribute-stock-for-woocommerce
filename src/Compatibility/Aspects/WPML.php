<?php
namespace Mewz\WCAS\Compatibility\Aspects;

use Mewz\Framework\Base\Aspect;
use Mewz\WCAS\Aspects\Workers\AutoProductLimits;
use Mewz\WCAS\Util\Limits;

class WPML extends Aspect
{
	public $sync;
	public $removed_hooks = false;

	public function __run()
	{
		/** @var \woocommerce_wpml $woocommerce_wpml */
		global $woocommerce_wpml;

		if ($woocommerce_wpml && $woocommerce_wpml->sync_product_data) {
			$this->sync = $woocommerce_wpml->sync_product_data;
		} else {
			return false;
		}
	}

	public function __hooks()
	{
		// prevent WPML syncing overridden stock values to translated products during stock changes
		add_action('init', [$this, 'init']);
		add_action('mewz_wcas_task_trigger_product_stock_changes', [$this, 'before_task_trigger_product_stock_changes'], 0);
	}

	public function init()
	{
		if (!Limits::product_limits_active() && !class_exists(AutoProductLimits::class, false)) {
			return;
		}

		if (has_action('woocommerce_product_set_stock_status', [$this->sync, 'sync_stock_status_for_translations'])) {
			remove_action('woocommerce_product_set_stock_status', [$this->sync, 'sync_stock_status_for_translations'], 100);
			add_action('woocommerce_product_set_stock_status', [$this, 'sync_stock_status_for_translations'], 100, 3);
		}

		if (has_action('woocommerce_variation_set_stock_status', [$this->sync, 'sync_stock_status_for_translations'])) {
			remove_action('woocommerce_variation_set_stock_status', [$this->sync, 'sync_stock_status_for_translations']);
			add_action('woocommerce_variation_set_stock_status', [$this, 'sync_stock_status_for_translations'], 10, 3);
		}

		add_filter('mewz_wcas_limit_product_stock_quantity', [$this, 'limit_product_stock_quantity'], 10, 3);
	}

	public function sync_stock_status_for_translations($product_id, $stock_status, $product)
	{
		$stock_status = $product->get_stock_status('edit');

		$this->sync->sync_stock_status_for_translations($product_id, $stock_status, $product);
	}

	public function before_task_trigger_product_stock_changes()
	{
		remove_action('woocommerce_product_set_stock', [$this->sync, 'sync_product_stock_hook']);
		remove_action('woocommerce_variation_set_stock', [$this->sync, 'sync_product_stock_hook']);

		remove_action('woocommerce_product_set_stock_status', [$this->sync, 'sync_stock_status_for_translations'], 100);
		remove_action('woocommerce_variation_set_stock_status', [$this->sync, 'sync_stock_status_for_translations']);

		remove_action('woocommerce_product_set_stock_status', [$this, 'sync_stock_status_for_translations'], 100);
		remove_action('woocommerce_variation_set_stock_status', [$this, 'sync_stock_status_for_translations']);

		$this->removed_hooks = true;
	}

	public function limit_product_stock_quantity($limit, $product, $value)
	{
		if ($this->removed_hooks) {
			remove_filter('mewz_wcas_limit_product_stock_quantity', [$this, 'limit_product_stock_quantity']);
			return $limit;
		}

		if ($limit) {
			$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);

			foreach ($trace as $frame) {
				if (isset($frame['class']) && $frame['class'] === \WCML_Synchronize_Product_Data::class) {
					return false;
				}
			}
		}

		return $limit;
	}
}
