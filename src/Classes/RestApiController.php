<?php
namespace Mewz\WCAS\Classes;

use Mewz\WCAS\Models\AttributeStock;

class RestApiController extends \WC_REST_Posts_Controller
{
	protected $namespace = 'wc/v3';
	protected $rest_base = 'attribute-stock';
	protected $post_type = AttributeStock::POST_TYPE;

	public function __construct()
	{
		add_filter("woocommerce_rest_{$this->post_type}_query", [$this, 'filter_query_args'], 5, 2);
	}

	public function register_routes()
	{
		register_rest_route($this->namespace, '/' . $this->rest_base, [
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [$this, 'get_items'],
				'permission_callback' => [$this, 'get_items_permissions_check'],
				'args' => $this->get_collection_params(),
			],
			[
				'methods' => \WP_REST_Server::CREATABLE,
				'callback' => [$this, 'create_item'],
				'permission_callback' => [$this, 'create_item_permissions_check'],
				'args' => $this->get_endpoint_args_for_item_schema(\WP_REST_Server::CREATABLE),
			],
			'schema' => [$this, 'get_public_item_schema'],
		]);

		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
			'args' => [
				'id' => [
					'description' => __('Unique identifier for the resource.', 'woocommerce'),
					'type' => 'integer',
				],
			],
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [$this, 'get_item'],
				'permission_callback' => [$this, 'get_item_permissions_check'],
				'args' => [
					'context' => $this->get_context_param(['default' => 'view']),
				],
			],
			[
				'methods' => \WP_REST_Server::EDITABLE,
				'callback' => [$this, 'update_item'],
				'permission_callback' => [$this, 'update_item_permissions_check'],
				'args' => $this->get_endpoint_args_for_item_schema(\WP_REST_Server::EDITABLE),
			],
			[
				'methods' => \WP_REST_Server::DELETABLE,
				'callback' => [$this, 'delete_item'],
				'permission_callback' => [$this, 'delete_item_permissions_check'],
				'args' => [
					'force' => [
						'default' => false,
						'type' => 'boolean',
						'description' => __('Whether to bypass trash and force deletion.', 'woocommerce'),
					],
				],
			],
			'schema' => [$this, 'get_public_item_schema'],
		]);

		register_rest_route($this->namespace, '/' . $this->rest_base . '/batch', [
			[
				'methods' => \WP_REST_Server::EDITABLE,
				'callback' => [$this, 'batch_items'],
				'permission_callback' => [$this, 'batch_items_permissions_check'],
				'args' => $this->get_endpoint_args_for_item_schema(\WP_REST_Server::EDITABLE),
			],
			'schema' => [$this, 'get_public_batch_schema'],
		]);
	}

	/**
	 * @return array
	 */
	public function get_item_schema()
	{
		$schema = [
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'title' => $this->post_type,
			'type' => 'object',
			'properties' => [
				'id' => [
					'description' => __('Unique identifier for the object.', 'woocommerce'),
					'type' => 'integer',
					'context' => ['view', 'edit'],
					'readonly' => true,
				],
				'title' => [
					'type' => 'string',
					'context' => ['view', 'edit'],
				],
				'slug' => [
					'type' => 'string',
					'context' => ['view', 'edit'],
					'readonly' => true,
				],
				'sku' => [
					'type' => 'string',
					'context' => ['view', 'edit'],
				],
				'enabled' => [
					'type' => 'boolean',
					'context' => ['view', 'edit'],
				],
				'status' => [
					'type' => 'string',
					'context' => ['view', 'edit'],
				],
				'quantity' => [
					'type' => ['number', 'string'],
					'context' => ['view', 'edit'],
				],
				'low_stock' => [
					'type' => ['number', 'string'],
					'context' => ['view', 'edit'],
				],
				'internal' => [
					'type' => 'boolean',
					'context' => ['view', 'edit'],
				],
				'multiplex' => [
					'type' => 'boolean',
					'context' => ['view', 'edit'],
				],
				'lock_multipliers' => [
					'type' => 'boolean',
					'context' => ['view', 'edit'],
				],
				'product_sku' => [
					'type' => 'boolean',
					'context' => ['view', 'edit'],
				],
				'product_image' => [
					'type' => 'boolean',
					'context' => ['view', 'edit'],
				],
				'image_id' => [
					'type' => 'integer',
					'context' => ['view', 'edit'],
				],
				'components' => [
					'type' => 'object',
					'context' => ['view', 'edit'],
					'properties' => [
						'parent' => [
							'type' => 'array',
							'context' => ['view', 'edit'],
						],
						'child' => [
							'type' => 'array',
							'context' => ['view', 'edit'],
						],
					],
				],
				'match_rules' => [
					'type' => 'array',
					'context' => ['view', 'edit'],
				],
				'products' => [
					'type' => 'array',
					'context' => ['view', 'edit'],
				],
				'exclude_products' => [
					'type' => 'array',
					'context' => ['view', 'edit'],
				],
				'categories' => [
					'type' => 'array',
					'context' => ['view', 'edit'],
				],
				'exclude_categories' => [
					'type' => 'array',
					'context' => ['view', 'edit'],
				],
				'product_types' => [
					'type' => 'array',
					'context' => ['view', 'edit'],
				],
				'tags' => [
					'type' => 'array',
					'context' => ['view', 'edit'],
				],
				'notes' => [
					'type' => 'string',
					'context' => ['view', 'edit'],
				],
				'created' => [
					'type' => 'date-time',
					'context' => ['view', 'edit'],
					'readonly' => true,
				],
				'created_gmt' => [
					'type' => 'date-time',
					'context' => ['view', 'edit'],
					'readonly' => true,
				],
				'modified' => [
					'type' => 'date-time',
					'context' => ['view', 'edit'],
					'readonly' => true,
				],
				'modified_gmt' => [
					'type' => 'date-time',
					'context' => ['view', 'edit'],
					'readonly' => true,
				],
			],
		];

		return $this->add_additional_fields_schema($schema);
	}

	/**
	 * @return array
	 */
	public function get_collection_params()
	{
		$params = parent::get_collection_params();

		$params['status'] = [
			'default' => 'any',
			'type' => 'array',
			'items' => [
				'type' => 'string',
				'enum' => array_merge(['any'], array_keys(get_post_stati())),
			],
			'validate_callback' => 'rest_validate_request_arg',
		];

		$params['enabled'] = [
			'type' => 'boolean',
			'validate_callback' => 'rest_validate_request_arg',
		];

		$params['orderby']['default'] = 'title';
		$params['orderby']['enum'][] = 'quantity';
		$params['orderby']['enum'][] = 'sku';

		$params['order']['default'] = 'asc';

		/**
		 * Filters collection parameters for the posts controller.
		 *
		 * The dynamic part of the filter `$this->post_type` refers to the post
		 * type slug for the controller.
		 *
		 * This filter registers the collection parameter, but does not map the
		 * collection parameter to an internal WP_Query parameter. Use the
		 * `rest_{$this->post_type}_query` filter to set WP_Query parameters.
		 *
		 * @since 4.7.0
		 *
		 * @param array        $query_params JSON Schema-formatted collection parameters.
		 * @param \WP_Post_Type $post_type    Post type object.
		 */
		return apply_filters("rest_{$this->post_type}_collection_params", $params, $this->post_type);
	}

	/**
	 * @param array $args
	 * @param \WP_REST_Request $request
	 *
	 * @return array
	 */
	public function filter_query_args($args, $request)
	{
		$enabled = $request->get_param('enabled');

		if ($enabled !== null) {
			$args['post_status'] = $enabled ? 'publish' : 'draft';
		} else {
			$args['post_status'] = $request->get_param('status');
		}

		if (!empty($args['orderby']) && is_string($args['orderby'])) {
			$args['orderby'] = [$args['orderby'] => $args['order'] ?? 'asc'];
		}

		$meta_keys = ['quantity', 'sku'];
		$orderby = [];
		$args['meta_query'] = [];

		foreach ($args['orderby'] as $key => $order) {
			if (in_array($key, $meta_keys)) {
				$meta_key = '_' . $key;

				$args['meta_query'][] = [
					'relation' => 'OR',
					['key' => $meta_key, 'compare' => 'NOT EXISTS'],
					['key' => $meta_key],
				];

				$key = $key === 'quantity' ? 'meta_value_num' : 'meta_value';
			}

			if (!isset($orderby[$key])) {
				$orderby[$key] = $order;
			}
		}

		if (!isset($orderby['post_title'])) {
			$orderby['post_title'] = 'asc';
		}

		if (!isset($orderby['post_date'])) {
			$orderby['post_date'] = 'desc';
		}

		$args['orderby'] = $orderby;
		unset($args['order']);

		return $args;
	}

	/**
	 * @param \WP_Post|AttributeStock $item
	 * @param \WP_REST_Request $request
	 *
	 * @return mixed|\WP_REST_Response|\WP_Error
	 */
	public function prepare_item_for_response($item, $request)
	{
		$context = $request->get_param('context') ?: 'view';
		$stock = AttributeStock::instance($item, $context);

		if (!$stock->exists()) {
			return new \WP_Error("woocommerce_rest_{$this->post_type}_invalid_id", __('Invalid ID.', 'woocommerce'), ['status' => 404]);
		}

		$schema = $this->get_item_schema();
		$data = [];

		foreach ($schema['properties'] as $key => $property) {
			if ($key === 'tags') {
				$data[$key] = $stock->tags(['fields' => 'names']);
			} else {
				$data[$key] = method_exists($stock, $key) ? $stock->$key() : $stock->get($key);
			}
		}

		$data = $this->add_additional_fields_to_object($data, $request);
		$data = $this->filter_response_by_context($data, $context);

		$response = rest_ensure_response($data);
		$response->add_links($this->prepare_links($stock->get_post(), $request));

		return apply_filters("woocommerce_rest_prepare_{$this->post_type}_object", $response, $stock, $request);
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_item($request)
	{
		if ($request->get_param('id')) {
			return new \WP_Error("woocommerce_rest_{$this->post_type}_exists", sprintf(__('Cannot create existing %s.', 'woocommerce'), $this->post_type), ['status' => 400]);
		}

		try {
			$stock = new AttributeStock(null, 'edit');
			$stock = $this->update_stock($stock, $request);

			if ($error = $stock->get_error()) {
				$error->add_data(['status' => 500]);
				return $error;
			}

			/**
			 * Fires after a single item is created or updated via the REST API.
			 *
			 * @param \WP_Post $post Post object.
			 * @param \WP_REST_Request $request Request object.
			 * @param boolean $creating True when creating item, false when updating.
			 */
			do_action("woocommerce_rest_insert_{$this->post_type}", $stock, $request, false);

			$request->set_param('context', 'edit');
			$response = $this->prepare_item_for_response($stock, $request);
			$response->set_status(201);
			$response->header('Location', rest_url(sprintf('/%s/%s/%d', $this->namespace, $this->rest_base, $stock->id())));

			return $response;
		}
		catch (\Exception $e) {
			return new \WP_Error($e->getCode(), $e->getMessage(), ['status' => 500]);
		}
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_item($request)
	{
		$stock_id = $request->get_param('id');

		if (!$stock_id || get_post_type($stock_id) !== $this->post_type) {
			return new \WP_Error("woocommerce_rest_{$this->post_type}_invalid_id", __('ID is invalid.', 'woocommerce'), ['status' => 400]);
		}

		try {
			$stock = AttributeStock::instance($stock_id, 'edit');
			$stock = $this->update_stock($stock, $request);

			if ($error = $stock->get_error()) {
				$error->add_data(['status' => 500]);
				return $error;
			}

			/**
			 * Fires after a single item is created or updated via the REST API.
			 *
			 * @param \WP_Post $post Post object.
			 * @param \WP_REST_Request $request Request object.
			 * @param boolean $creating True when creating item, false when updating.
			 */
			do_action("woocommerce_rest_insert_{$this->post_type}", $stock, $request, false);

			$request->set_param('context', 'edit');
			$response = $this->prepare_item_for_response($stock, $request);

			return rest_ensure_response($response);
		}
		catch (\Exception $e) {
			return new \WP_Error($e->getCode(), $e->getMessage(), ['status' => 500]);
		}
	}

	/**
	 * @param AttributeStock $stock
	 * @param \WP_REST_Request$request
	 *
	 * @return AttributeStock
	 */
	public function update_stock($stock, $request)
	{
		$schema = $this->get_item_schema();
		$match_rules = null;
		$tags = null;

		foreach ($schema['properties'] as $key => $property) {
			if (!empty($property['readonly'])) continue;

			$value = $request->get_param($key);

			if ($value === null) continue;

			if ($key === 'match_rules') {
				$match_rules = $value;
			} elseif ($key === 'tags') {
				$tags = $value;
			} elseif (method_exists($stock, $method = 'set_' . $key)) {
				$stock->$method($value);
			}
		}

		if ($stock->exists() && ($changes = $stock->get_changes()) && isset($changes['title'])) {
			$slug = sanitize_title($stock->title());
			if ($stock->trashed()) $slug .= '__trashed';
			$stock->set_slug($slug);
		}

		$stock->save();

		if ($match_rules && $stock->exists()) {
			$stock->save_match_rules($match_rules);
		}

		if ($tags !== null) {
			$tag_ids = [];

			foreach ($tags as $tag) {
				$tag = term_exists($tag, 'product_tag') ?: wp_insert_term($tag, 'product_tag');
				$tag_ids[] = (int)$tag['term_id'];
			}

			if ($tag_ids) {
				wp_set_object_terms($stock->id(), $tag_ids, 'product_tag');
			} else {
				wp_delete_object_term_relationships($stock->id(), 'product_tag');
			}
		}

		return $stock;
	}
}
