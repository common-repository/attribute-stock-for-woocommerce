<?php
namespace Mewz\WCAS\Aspects\Admin\Products;

use Mewz\Framework\Base\Aspect;
use Mewz\Framework\Util\Number;

class ProductEdit extends Aspect
{
	public $enqueue = '@admin/product-edit';

	public function __hooks()
	{
		add_action('woocommerce_product_options_inventory_product_data', [$this, 'display_options']);
		add_action('woocommerce_admin_process_product_object', [$this, 'process_options']);
	}

	public function display_options()
	{
		global $product_object;

		if (!$product_object) return;

		$this->view->render('admin/products/product-fields', [
			'product' => $product_object,
		]);
	}

	public function process_options(\WC_Product $product)
	{
		if (!empty($_POST['_mewz_wcas_exclude'])) {
			$product->update_meta_data('_mewz_wcas_exclude', 1);
		} else {
			$product->delete_meta_data('_mewz_wcas_exclude');
		}

		if (isset($_POST['_mewz_wcas_multiplier']) && $_POST['_mewz_wcas_multiplier'] !== '') {
			$value = max(0, (float)$_POST['_mewz_wcas_multiplier']);
			$product->update_meta_data('_mewz_wcas_multiplier', Number::safe_decimal($value));
		} else {
			$product->delete_meta_data('_mewz_wcas_multiplier');
		}
	}
}
