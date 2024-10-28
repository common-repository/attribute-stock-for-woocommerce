<?php
namespace Mewz\WCAS\Aspects\Admin\Attributes;

use Mewz\Framework\Base\Aspect;
use Mewz\WCAS\Models\AttributeStock;
use Mewz\WCAS\Util\Matches;

class AttributeTermEdit extends Aspect
{
	public $enqueue = '@admin/attributes';

	public function __hooks()
	{
		$taxonomy = $this->context->taxonomy;
		add_action($taxonomy . '_add_form_fields', [$this, 'display_add_term_fields']);
		add_action($taxonomy . '_edit_form_fields', [$this, 'display_edit_term_fields']);
	}

	public function display_add_term_fields()
	{
		$this->view->render('admin/attributes/add-fields');
	}

	/**
	 * @param \WP_Term $term
	 */
	public function display_edit_term_fields($term = null)
	{
		$stocks = Matches::query_stock($term->taxonomy, $term->term_id, 'edit');

		$this->display_edit_fields($stocks, ['term' => $term]);
	}

	/**
	 * @param AttributeStock[] $stocks
	 * @param array $data
	 */
	public function display_edit_fields($stocks, $data = [])
	{
		if (count($stocks) > 1) {
			$enabled_stocks = [];

			foreach ($stocks as $stock) {
				if ($stock->enabled()) {
					$enabled_stocks[] = $stock;
				}
			}

			if (count($enabled_stocks) <= 1) {
				$stocks = $enabled_stocks;
			}
		}

		if (!$stocks) {
			$stocks = [new AttributeStock(null, 'edit')];
		}

		$data['stocks'] = $stocks;

		$this->view->render('admin/attributes/edit-fields', $data);
	}
}
