<?php
namespace Mewz\WCAS\Compatibility\Aspects;

use Mewz\Framework\Base\Aspect;

class LumiseProductDesigner extends Aspect
{
	public function __hooks()
	{
		add_filter('woocommerce_add_cart_item', [$this, 'add_cart_item'], 5, 2);
	}

	public function add_cart_item($item, $key)
	{
		if (!empty($item['lumise_data']['id']) && empty($item['variation']) && strpos($item['lumise_data']['id'], 'variable:') === 0) {
			$variation_id = (int)substr($item['lumise_data']['id'], 9);

			/** @var \WC_Product_Variation $variation */
			$variation = wc_get_product($variation_id);
			if (!$variation) return $item;

			$item['variation'] = $variation->get_variation_attributes(false);
		}

		return $item;
	}
}
