<?php
namespace Mewz\WCAS\Aspects\Admin\Attributes;

class AttributeSave extends AttributeTermSave
{
	public function __hooks()
	{
		add_action('woocommerce_attribute_added', [$this, 'attribute_added']);
		add_action('woocommerce_attribute_updated', [$this, 'attribute_updated']);
	}

	public function attribute_added($attribute_id)
	{
		if (empty($_REQUEST['mewz_wcas']['manage_stock'])) return;

		$this->save_attribute_stock('added', $attribute_id, null, $_REQUEST['mewz_wcas']);
	}

	public function attribute_updated($attribute_id)
	{
		if (empty($_REQUEST['mewz_wcas']) || !isset($_REQUEST['edit']) || $_REQUEST['edit'] != $attribute_id) {
			return;
		}

		$this->save_attribute_stock('updated', $attribute_id, null, $_REQUEST['mewz_wcas']);
	}
}
