<?php
namespace Mewz\WCAS\Aspects\Common;

use Mewz\Framework\Base\Aspect;
use Mewz\WCAS\Models\AttributeStock;

class Shortcode extends Aspect
{
	public function __hooks()
	{
		add_shortcode('attribute_stock', [$this, 'attribute_stock']);
	}

	public function attribute_stock($atts)
	{
		if (!empty($atts['id'])) {
			$stock = mewz_wcas_get_stock((int)trim($atts['id']));
		} elseif (!empty($atts['sku'])) {
			$stock = AttributeStock::find([
				'meta_key' => '_sku',
				'meta_value' => $atts['sku'],
			]);
		} elseif (!empty($atts['title'])) {
			$stock = AttributeStock::find([
				'title' => $atts['title'],
			]);
		} else {
			return esc_html__('Missing "id", "sku", or "title" attribute for shortcode [attribute_stock].', 'woocommerce-attribute-stock');
		}

		if (!empty($stock) && $stock->valid()) {
			return $stock->quantity();
		} else {
			return '-';
		}
	}
}
