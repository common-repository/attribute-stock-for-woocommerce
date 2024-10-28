<?php
namespace Mewz\WCAS\Aspects\Admin\Products;

use Mewz\Framework\Base\Aspect;
use Mewz\Framework\Util\Number;
use Mewz\WCAS\Util\Products;

class ProductVariationEdit extends Aspect
{
	public function __hooks()
	{
		add_action('woocommerce_variation_header', [$this, 'variation_header'], 5, 2);
		add_action('woocommerce_variation_options', [$this, 'variation_options'], -10, 3);
		add_action('woocommerce_variation_options_dimensions', [$this, 'variation_fields'], -10, 3);
		add_action('woocommerce_admin_process_variation_object', [$this, 'admin_process_variation_object'], 10, 2);
	}

	public function variation_header($post, $index)
	{
		if (!$post || empty($post->ID)) return;

		$multiplier = get_post_meta($post->ID, '_mewz_wcas_multiplier', true);

		if (
			$multiplier === ''
			&& ($product = wc_get_product($post))
			&& Products::use_multiplier($product, 'product')
		) {
			$multiplier = Products::match_term_multiplier($product, null, true, '');
			$inherited_multiplier = true;
		}

		if ($multiplier !== '') {
			$this->view->render('admin/products/variation-toolbar', [
				'post' => $post,
				'multiplier' => $multiplier,
				'inherited_multiplier' => !empty($inherited_multiplier),
			]);
		}
	}

	public function variation_options($index, $data, $post)
	{
		if (!$post || empty($post->ID)) return;

		$this->view->render('admin/products/variation-options', [
			'post' => $post,
			'index' => $index,
		]);
	}

	public function variation_fields($index, $data, $post)
	{
		if (!$post || empty($post->ID)) return;

		$this->view->render('admin/products/variation-fields', [
			'post' => $post,
			'index' => $index,
		]);
	}

	public function admin_process_variation_object($variation, $i)
	{
		if (!empty($_POST['variable_mewz_wcas_exclude'][$i])) {
			$variation->update_meta_data('_mewz_wcas_exclude', 1);
		} else {
			$variation->delete_meta_data('_mewz_wcas_exclude');
		}

		if (isset($_POST['variable_mewz_wcas_multiplier'][$i]) && $_POST['variable_mewz_wcas_multiplier'][$i] !== '') {
			$value = max(0, (float)$_POST['variable_mewz_wcas_multiplier'][$i]);
			$variation->update_meta_data('_mewz_wcas_multiplier', Number::safe_decimal($value));
		} else {
			$variation->delete_meta_data('_mewz_wcas_multiplier');
		}
	}
}
