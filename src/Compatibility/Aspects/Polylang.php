<?php
namespace Mewz\WCAS\Compatibility\Aspects;

use Mewz\Framework\Base\Aspect;
use Mewz\WCAS\Aspects\Workers\AutoProductLimits;
use Mewz\WCAS\Util\Limits;

class Polylang extends Aspect
{
	public $stock;

	public function __run()
	{
	    $this->stock = PLLWC()->stock;

		return (bool)$this->stock;
	}

	public function __hooks()
	{
		// prevent Polylang syncing overridden stock values to translated products during stock changes
		add_action('init', [$this, 'init']);
		add_action('mewz_wcas_task_trigger_product_stock_changes', [$this, 'before_task_trigger_product_stock_changes'], 0);
	}

	public function init()
	{
		if (!Limits::product_limits_active() && !class_exists(AutoProductLimits::class, false)) {
			return;
		}

		if (has_action('woocommerce_product_set_stock_status', [$this->stock, 'set_stock_status'])) {
			remove_action('woocommerce_product_set_stock_status', [$this->stock, 'set_stock_status']);
			add_action('woocommerce_product_set_stock_status', [$this, 'set_stock_status'], 10, 3);
		}

		if (has_action('woocommerce_variation_set_stock_status', [$this->stock, 'set_stock_status'])) {
			remove_action('woocommerce_variation_set_stock_status', [$this->stock, 'set_stock_status']);
			add_action('woocommerce_variation_set_stock_status', [$this, 'set_stock_status'], 10, 3);
		}
	}

	public function set_stock_status($product_id, $stock_status, $product)
	{
		$stock_status = $product->get_stock_status('edit');

		$this->stock->set_stock_status($product_id, $stock_status, $product);
	}

	public function before_task_trigger_product_stock_changes()
	{
		remove_action('woocommerce_product_set_stock_status', [$this->stock, 'set_stock_status']);
		remove_action('woocommerce_variation_set_stock_status', [$this->stock, 'set_stock_status']);

		remove_action('woocommerce_product_set_stock_status', [$this, 'set_stock_status']);
		remove_action('woocommerce_variation_set_stock_status', [$this, 'set_stock_status']);
	}
}
