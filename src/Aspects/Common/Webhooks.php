<?php
namespace Mewz\WCAS\Aspects\Common;

use Mewz\Framework\Base\Aspect;
use Mewz\WCAS\Models\AttributeStock;

class Webhooks extends Aspect
{
	public function __hooks()
	{
		add_filter('woocommerce_valid_webhook_resources', [$this, 'valid_webhook_resources']);
		add_filter('woocommerce_webhook_topics', [$this, 'webhook_topic_menu'] );
		add_filter('woocommerce_webhook_topic_hooks', [$this, 'webhook_topic_hooks'], 10, 2);
		add_filter('woocommerce_webhook_payload', [$this, 'webhook_payload'], 10, 4);

		add_action('mewz_attribute_stock_saved', [$this, 'stock_saved'], 10, 3);
		add_action('mewz_wcas_stock_change', [$this, 'stock_change']);
		add_action('wp_trash_post', [$this, 'trash_post']);
		add_action('untrashed_post', [$this, 'untrashed_post']);
	}

	public function valid_webhook_resources($resources)
	{
		$resources[] = AttributeStock::POST_TYPE;

	    return $resources;
	}

	public function webhook_topic_menu($topics)
	{
		$topics += [
			AttributeStock::POST_TYPE . '.created' => __('Attribute Stock created', 'woocommerce-attribute-stock'),
			AttributeStock::POST_TYPE . '.updated' => __('Attribute Stock updated', 'woocommerce-attribute-stock'),
			AttributeStock::POST_TYPE . '.deleted' => __('Attribute Stock deleted', 'woocommerce-attribute-stock'),
			AttributeStock::POST_TYPE . '.restored' => __('Attribute Stock restored', 'woocommerce-attribute-stock'),
		];

		// keep action topic at the end of the list
		if (isset($topics['action'])) {
			$action = $topics['action'];
			unset($topics['action']);
			$topics['action'] = $action;
		}

		return $topics;
	}

	public function webhook_topic_hooks($topics, $webhook)
	{
		// add attribute stock webhook topics
		$topics += [
			AttributeStock::POST_TYPE . '.created'  => ['mewz_wcas_webhook_stock_created'],
			AttributeStock::POST_TYPE . '.updated'  => ['mewz_wcas_webhook_stock_updated'],
			AttributeStock::POST_TYPE . '.deleted'  => ['mewz_wcas_webhook_stock_deleted'],
			AttributeStock::POST_TYPE . '.restored' => ['mewz_wcas_webhook_stock_restored'],
		];

		// trigger product.updated webhook on stock change action
		if (isset($topics['product.updated'])) {
			$topics['product.updated'][] = 'mewz_wcas_product_stock_changed';
		}

		return $topics;
	}

	public static function webhook_payload($payload, $resource, $resource_id, $webhook_id) {

		if ($resource === AttributeStock::POST_TYPE && $resource_id && !$payload) {
			$request = new \WP_REST_Request('GET', "/wc/v3/attribute-stock/{$resource_id}");
			$response = rest_do_request($request);
			$server = rest_get_server();
			$payload = wp_json_encode($server->response_to_data($response, false));
			$payload = json_decode($payload, true);
		}

		return $payload;
	}

	public function stock_saved($stock, $operation)
	{
		if ($operation === 'insert') {
			do_action('mewz_wcas_webhook_stock_created', $stock->id());
		} else {
			do_action('mewz_wcas_webhook_stock_updated', $stock->id());
		}
	}

	public function stock_change($stock_ids)
	{
	    foreach ($stock_ids as $stock_id) {
	        do_action('mewz_wcas_webhook_stock_updated', $stock_id);
	    }
	}

	public function trash_post($post_id)
	{
		if (get_post_type($post_id) === AttributeStock::POST_TYPE) {
			do_action('mewz_wcas_webhook_stock_deleted', $post_id);
		}
	}

	public function untrashed_post($post_id)
	{
		if (get_post_type($post_id) === AttributeStock::POST_TYPE) {
			do_action('mewz_wcas_webhook_stock_restored', $post_id);
		}
	}
}
