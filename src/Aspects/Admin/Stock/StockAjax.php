<?php
namespace Mewz\WCAS\Aspects\Admin\Stock;

use Mewz\Framework\Base\Aspect;
use Mewz\WCAS\Models\AttributeStock;

class StockAjax extends Aspect
{
	public function __hooks()
	{
		register_rest_route('mewz/wcas', '/inline-edit/(?P<id>\d+)', [
			'methods' => \WP_REST_Server::EDITABLE,
			'callback' => [$this, 'update_stock'],
			'permission_callback' => [$this, 'permission_check'],
		]);
	}

	public function permission_check(\WP_REST_Request $request)
	{
		$stock = AttributeStock::instance((int)$request->get_param('id'), 'edit');

		if (!$stock->valid()) {
			return new \WP_Error('mewz_wcas_stock_invalid_id', __('Invalid attribute stock ID.', 'woocommerce-attribute-stock'), ['status' => 404]);
		}

		return current_user_can('edit_post', $stock->id());
	}

	public function update_stock(\WP_REST_Request $request)
	{
		$stock = AttributeStock::instance((int)$request->get_param('id'), 'edit');
		$action = $request->get_param('action');
		$value = $request->get_param('value');

		if (strpos($action, 'meta_') === 0) {
			$stock->update_meta(substr($action, 5), $value);
			$updated = [$action => $value];
		} else {
			$stock->$action($value);
			$updated = $stock->save();
		}

		if ($updated === false) {
			return new \WP_Error('mewz_wcas_stock_save_error', $stock->get_error_message());
		}

		// update modified date and trigger post update actions
		wp_update_post(['ID' => $stock->id()]);

		$return = [
			'updated' => key($updated),
			'value' => current($updated),
		];

		if (isset($updated['quantity'])) {
			$return['formatted_quantity'] = $stock->formatted_quantity();
		}

		$return = apply_filters('mewz_wcas_inline_edit_response', $return, $stock, $request);

		return rest_ensure_response($return);
	}
}
