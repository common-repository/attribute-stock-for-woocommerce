<?php
namespace Mewz\WCAS\Aspects\Admin\Attributes;

use Mewz\WCAS\Util\Matches;

class AttributeEdit extends AttributeTermEdit
{
	public function __hooks()
	{
		add_action('woocommerce_after_add_attribute_fields', [$this, 'display_add_attribute_fields']);
		add_action('woocommerce_after_edit_attribute_fields', [$this, 'display_edit_attribute_fields']);
	}

	public function display_add_attribute_fields()
	{
		$this->view->render('admin/attributes/add-fields', ['attribute_level' => true]);
	}

	public function display_edit_attribute_fields()
	{
		$stocks = Matches::query_stock($_GET['edit'], null, 'edit');

		$this->display_edit_fields($stocks, [
			'term' => null,
			'attribute_level' => true,
		]);
	}
}
