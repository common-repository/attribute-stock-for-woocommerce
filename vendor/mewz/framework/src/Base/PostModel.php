<?php
namespace Mewz\Framework\Base;

use Mewz\Framework\Util\Date;
use Mewz\Framework\Util\Number;
use Mewz\QueryBuilder\DB;

abstract class PostModel extends Model
{
	/** @var string The WP custom post type */
	const POST_TYPE = 'post';

	/** @var \WP_Post The post object */
	protected $post;

	/** @var static[][] PostModel instances by context */
	protected static $instance = [];

	/** @var array Post object property mappings */
	protected static $post_props = [
		'author_id' => 'post_author',
		'comment_count' => 'comment_count',
		'comment_status' => 'comment_status',
		'content' => 'post_content',
		'content_filtered' => 'post_content_filtered',
		'created' => 'post_date',
		'created_gmt' => 'post_date_gmt',
		'excerpt' => 'post_excerpt',
		'guid' => 'guid',
		'menu_order' => 'menu_order',
		'mime_type' => 'post_mime_type',
		'modified' => 'post_modified',
		'modified_gmt' => 'post_modified_gmt',
		'parent_id' => 'post_parent',
		'password' => 'post_password',
		'ping_status' => 'ping_status',
		'pinged' => 'pinged',
		'post_type' => 'post_type',
		'slug' => 'post_name',
		'status' => 'post_status',
		'title' => 'post_title',
		'to_ping' => 'to_ping',
	];

	/**
	 * @param int|static|\WP_Post $id The post ID or WP_Post object
	 * @param string $context Context for accessing data ('view' = filtered values, 'edit' = raw values, 'object' = raw values + no load)
	 */
	public function __construct($id = null, $context = 'view')
	{
		$this->context = $context;
		$this->set_valid_props();

		if ($id) {
			if ($context === 'object') {
				$this->load_data($id, false, false);
			} else {
				$this->load_data($id);
			}
		}
	}

	/**
	 * @inheritDoc
	 *
	 * @param int|static|\WP_Post $id The post ID, model object, or WP_Post object
	 * @param string $context Context for accessing the object
	 *                        ('view' = filtered values, 'edit' = raw values, 'object' creates a new empty object)
	 *
	 * @return static
	 */
	public static function instance($id = null, $context = 'view')
	{
		if ($context === 'object') {
			return new static($id, $context);
		}

		if ($id instanceof \WP_Post) {
			$post_id = $id->ID;
		} elseif ($id instanceof static) {
			$post_id = $id = $id->id();
		} else {
			$post_id = $id;
		}

		if (!$post_id) {
			return new static(null, $context);
		}

		if (isset(self::$instance[$context][$post_id])) {
			return self::$instance[$context][$post_id];
		}

		return self::$instance[$context][$post_id] = new static($id, $context);
	}

	/**
	 * @param int $id The post ID
	 * @param string $context Context for accessing the object
	 */
	public static function has_instance($id, $context = 'view')
	{
		return isset(self::$instance[$context][$id]);
	}

	/**
	 * @param int $id The post ID
	 */
	public static function unset_instances($id)
	{
		foreach (self::$instance as $context => $instances) {
		    unset(self::$instance[$context][$id]);
		}
	}

	public static function get_post_prop($prop)
	{
		$prop = self::get_alias_prop($prop) ?: $prop;

		if (isset(static::$post_props[$prop])) {
			return static::$post_props[$prop];
		} elseif (in_array($prop, static::$post_props)) {
			return $prop;
		} else {
			return false;
		}
	}

	protected function set_valid_props()
	{
		foreach (self::$post_props as $prop => $key) {
			$this->valid_props[$prop] = $prop;
		}

		parent::set_valid_props();

		// don't allow changing the post type
		unset($this->valid_props['post_type']);
	}

	/**
	 * @param int|static|\WP_Post $post The post ID or WP_Post object
	 * @param bool $load_post Load post data if $post is an ID
	 * @param bool $load_meta Load meta prop data along with the post data
	 *
	 * @return int|null The post ID on success, null on invalid post object
	 */
	protected function load_data($post, $load_post = true, $load_meta = true)
	{
		if (!is_object($post)) {
			if ($load_post) {
				$post = \WP_Post::get_instance($post);
			} else {
				$this->id = (int)$post;
				return null;
			}
		}

		if ($post instanceof static) {
			$post = $post->get_post();
		}

		if (!$post || $post->post_type !== static::POST_TYPE) {
			return null;
		}

		$this->id = $post->ID;

		/*if ($post->post_status === 'auto-draft') {
			$this->data['status'] = 'auto-draft';
			return null;
		}*/

		$this->post = $post;

		if ($load_meta && $this->data && $metadata = get_metadata('post', $this->id)) {
			foreach ($this->data as $prop => $value) {
				if (!isset(self::$post_props[$prop]) && isset($metadata[$meta_key = '_' . $prop]) && $metadata[$meta_key][0] !== '') {
					$value = $metadata[$meta_key][0];

					if (is_string($this->data[$prop])) {
						$this->data[$prop] = (string)$value;
					} elseif (is_bool($this->data[$prop])) {
						$this->data[$prop] = (bool)$value;
					} elseif (is_numeric($value)) {
						$this->data[$prop] = $value === (string)(int)$value ? (int)$value : $value;
					} else {
						$this->data[$prop] = maybe_unserialize($value);
					}
				}
			}
		}

		foreach (self::$post_props as $prop => $key) {
			$this->data[$prop] = $post->$key;
		}

		$this->data = apply_filters(static::hook_name('loaded_data'), $this->data, $this);

		return $this->id;
	}

	/**
	 * Reloads the post data, and optionally the meta prop data, regardless of context.
	 *
	 * @param bool $reload_meta Whether to reload the meta prop data
	 */
	public function reload($reload_meta = true)
	{
		if ($this->id) {
			$this->load_data($this->id, true, $reload_meta);
		}
	}

	/**
	 * IMPORTANT: This post object could be outdated. If you need an up-to-date post object, use {@see get_post()}.
	 *
	 * @return \WP_Post
	 */
	public function get_post()
	{
		if (!$this->post) {
			$this->post = \WP_Post::get_instance($this->id());
		}

		return $this->post;
	}

	/**
	 * @return bool
	 */
	public function exists()
	{
		return $this->id > 0 && $this->status() !== 'auto-draft';
	}

	/**
	 * @var string $context
	 *
	 * @return bool
	 */
	public function valid($context = null)
	{
		if ($this->id <= 0) {
			return false;
		}

		$context ??= $this->context;

		if ($context === 'view') {
			return $this->status() === 'publish';
		} else {
			return !in_array($this->status(), ['auto-draft', 'trash']);
		}
	}

	/**
	 * @see WP_Post::$post_title
	 *
	 * @return string
	 */
	public function title()
	{
		return $this->get('title');
	}

	/**
	 * @see WP_Post::$post_title
	 *
	 * @param string $value
	 */
	public function set_title($value)
	{
		$this->set('title', $value);
	}

	/**
	 * @see WP_Post::$post_name
	 *
	 * @return string
	 */
	public function slug()
	{
		return $this->get('slug');
	}

	/**
	 * @see WP_Post::$post_name
	 *
	 * @param string $value
	 */
	public function set_slug($value)
	{
		$this->set('slug', $value);
	}

	/**
	 * @see WP_Post::$post_status
	 *
	 * @return string
	 */
	public function status()
	{
		return $this->get('status');
	}

	/**
	 * Checks if the post is currently trashed.
	 *
	 * @see WP_Post::$post_status
	 *
	 * @return bool
	 */
	public function trashed()
	{
		return $this->get('status') === 'trash';
	}

	/**
	 * @see WP_Post::$post_status
	 *
	 * @param string $value
	 */
	public function set_status($value)
	{
		$this->set('status', $value);
	}

	/**
	 * @see WP_Post::$post_author
	 *
	 * @return string
	 */
	public function author_id()
	{
		return $this->get('author_id');
	}

	/**
	 * @see WP_Post::$post_author
	 *
	 * @param int $value
	 */
	public function set_author_id($value)
	{
		$this->set('author_id', (int)$value);
	}

	/**
	 * @see WP_Post::$post_date
	 *
	 * @param bool $gmt
	 * @param string $format
	 * @param string $sep
	 *
	 * @return string
	 */
	public function created($gmt = false, $format = null, $sep = null)
	{
		$date = $this->get($gmt ? 'created_gmt' : 'created');

		if ($format !== null) {
			$date = Date::i18n_format($format, $date, $sep);
		}

		return $date;
	}

	/**
	 * @see WP_Post::$post_date
	 *
	 * @param string $value
	 * @param bool $gmt
	 */
	public function set_created($value, $gmt = false)
	{
		$this->set($gmt ? 'created_gmt' : 'created', $value);
	}

	/**
	 * @see WP_Post::$post_modified
	 *
	 * @param bool $gmt
	 * @param string $format
	 * @param string $sep
	 *
	 * @return string
	 */
	public function modified($gmt = false, $format = null, $sep = null)
	{
		$date = $this->get($gmt ? 'modified_gmt' : 'modified');

		if ($format !== null) {
			$date = Date::i18n_format($format, $date, $sep);
		}

		return $date;
	}

	/**
	 * @see WP_Post::$post_modified
	 *
	 * @param string $value
	 * @param bool $gmt
	 */
	public function set_modified($value, $gmt = false)
	{
		$this->set($gmt ? 'modified_gmt' : 'modified', $value);
	}

	/**
	 * @see WP_Post::$post_content
	 *
	 * @return string
	 */
	public function content()
	{
		return $this->get('content');
	}

	/**
	 * @see WP_Post::$post_content
	 *
	 * @param string $value
	 */
	public function set_content($value)
	{
		$this->set('content', $value);
	}

	/**
	 * @see WP_Post::$post_excerpt
	 *
	 * @return string
	 */
	public function excerpt()
	{
		return $this->get('excerpt');
	}

	/**
	 * @see WP_Post::$post_excerpt
	 *
	 * @param string $value
	 */
	public function set_excerpt($value)
	{
		$this->set('excerpt', $value);
	}

	/**
	 * @see WP_Post::$guid
	 *
	 * @return string
	 */
	public function guid()
	{
		return $this->get('guid');
	}

	/**
	 * @see WP_Post::$guid
	 *
	 * @param string $value
	 */
	public function set_guid($value)
	{
		$this->set('guid', $value);
	}

	/**
	 * @see WP_Post::$menu_order
	 *
	 * @return int
	 */
	public function menu_order()
	{
		return $this->get('menu_order');
	}

	/**
	 * @see WP_Post::$menu_order
	 *
	 * @param int $value
	 */
	public function set_menu_order($value)
	{
		$this->set('menu_order', (int)$value);
	}

	/**
	 * @see WP_Post::$mime_type
	 *
	 * @return string
	 */
	public function mime_type()
	{
		return $this->get('mime_type');
	}

	/**
	 * @see WP_Post::$mime_type
	 *
	 * @param string $value
	 */
	public function set_mime_type($value)
	{
		$this->set('mime_type', $value);
	}

	/**
	 * @see WP_Post::$parent_id
	 *
	 * @return int
	 */
	public function parent_id()
	{
		return $this->get('parent_id');
	}

	/**
	 * @see WP_Post::$parent_id
	 *
	 * @param int $value
	 */
	public function set_parent_id($value)
	{
		$this->set('parent_id', (int)$value);
	}

	/**
	 * @see WP_Post::$password
	 *
	 * @return string
	 */
	public function password()
	{
		return $this->get('password');
	}

	/**
	 * @see WP_Post::$password
	 *
	 * @param string $value
	 */
	public function set_password($value)
	{
		$this->set('password', $value);
	}

	/**
	 * @see WP_Post::$ping_status
	 *
	 * @return string
	 */
	public function ping_status()
	{
		return $this->get('ping_status');
	}

	/**
	 * @see WP_Post::$ping_status
	 *
	 * @param string $value
	 */
	public function set_ping_status($value)
	{
		$this->set('ping_status', $value);
	}

	/**
	 * @see WP_Post::$pinged
	 *
	 * @return string
	 */
	public function pinged()
	{
		return $this->get('pinged');
	}

	/**
	 * @see WP_Post::$pinged
	 *
	 * @param string $value
	 */
	public function set_pinged($value)
	{
		$this->set('pinged', $value);
	}

	/**
	 * @see WP_Post::$to_ping
	 *
	 * @return string
	 */
	public function to_ping()
	{
		return $this->get('to_ping');
	}

	/**
	 * @see WP_Post::$to_ping
	 *
	 * @param string $value
	 */
	public function set_to_ping($value)
	{
		$this->set('to_ping', $value);
	}

	/**
	 * @see get_permalink()
	 *
	 * @return string|null
	 */
	public function url()
	{
	    return get_permalink($this->post ?: $this->id);
	}

	/**
	 * @see get_edit_post_link()
	 *
	 * @param string|array $params
	 *
	 * @return string|null
	 */
	public function edit_url($params = null)
	{
		$url = get_edit_post_link($this->post ?: $this->id, 'raw');

		if ($url && $params) {
			if (is_array($params)) {
				$params = http_build_query($params);
			}

			$url .= '&' . $params;
		}

		return $url;
	}

	/**
	 * @param string|array $params
	 *
	 * @return string|null
	 */
	public static function admin_url($params = null)
	{
		$url = 'edit.php?post_type=' . static::POST_TYPE;

		if ($params) {
			if (is_array($params)) {
				$params = http_build_query($params);
			}

			$url .= '&' . $params;
		}

		return admin_url($url);
	}

	/**
	 * Saves the post via {@see wp_insert_post()} or {@see wp_update_post()} accordingly.
	 * Extra data is saved as post meta prefixed with '_'.
	 *
	 * @param bool $direct_update If true, update operation saves post data directly to database
	 *                            instead of using {@see wp_update_post()}. This should be used
	 *                            for example when saving during an admin post screen save to avoid
	 *                            multiple calls to {@see wp_update_post()} for the same post.
	 *
	 * @return array|false Array of updated data on success (empty if nothing to update), false on failure
	 */
	public function save($direct_update = false)
	{
		do_action(static::hook_name('before_save'), $this);

		$updated = [];

		if (!$this->id) {
			$post_id = $this->insert();

			if ($post_id === false) {
				return false;
			} elseif ($post_id === null) {
				return [];
			} else {
				$updated['id'] = $post_id;
			}

			$this->id = $post_id;
			$operation = 'insert';
		}
		elseif ($this->prev_data) {
			$updated = $this->update($direct_update);
			if (!$updated) return $updated;

			$operation = 'update';
		}
		else return [];

		$this->post = null;
		self::unset_instances($this->id);

		do_action(static::hook_name('saved'), $this, $operation, $updated);

		return $updated;
	}

	/**
	 * @return int|false|null The post ID on success, false on failure, null if nothing to insert
	 */
	protected function insert()
	{
		do_action(static::hook_name('before_insert'), $this);

		$data = $this->collect_insert_data();

		if (empty($data['post'])) {
			return null;
		}

		$post_id = wp_insert_post($data['post'], true);

		if ($post_id instanceof \WP_Error) {
			return $this->error($post_id);
		}

		if ($data['meta']) {
			foreach ($data['meta'] as $key => $value) {
				if ($value !== null) {
					add_metadata('post', $post_id, $key, $value);
				}
			}
		}

		do_action(static::hook_name('inserted'), $this, $data);

		return $post_id;
	}

	/**
	 * @param bool $direct_update Save post data directly to the database
	 *
	 * @return array|false An array of updated data on success (empty if nothing to update), false on failure
	 */
	protected function update($direct_update = false)
	{
		do_action(static::hook_name('before_update'), $this, $direct_update);

		$data = $this->collect_update_data();
		$updated = false;

		if ($data['post']) {
			if ($direct_update) {
				unset($data['post']['ID']);

				$result = DB::table('posts')->where('ID', $this->id)->update($data['post']);

				if ($result) {
					clean_post_cache($this->id);
				} else {
					$result = new \WP_Error('db_update_error', __('Could not update post in the database'), DB::$wpdb->last_error);
				}
			} else {
				$result = wp_update_post($data['post'], true);
			}

			if ($result instanceof \WP_Error) {
				return $this->error($result);
			}

			$updated = true;
		}

		if ($data['meta']) {
			foreach ($data['meta'] as $key => $value) {
				if ($value === null) {
					delete_metadata('post', $this->id, $key);
				} else {
					update_metadata('post', $this->id, $key, $value);
				}
			}

			$updated = true;
		}

		if (!$updated) return [];

		$updated = $this->get_changes();
		$this->apply_changes();

		do_action(static::hook_name('updated'), $this, $data, $updated);

		return $updated;
	}

	protected function collect_insert_data()
	{
		$data = $this->data;

		if (empty($data['status'])) {
			$data['status'] = 'publish';
		}

		$data = $this->collect_save_data($data);

		if ($data['post']) {
			$data['post'] = ['post_type' => static::POST_TYPE] + $data['post'];
		}

		return $data;
	}

	protected function collect_update_data()
	{
		$data = $this->collect_save_data($this->get_changes(true));

		if ($data['post']) {
			$data['post'] = ['ID' => $this->id] + $data['post'];
		}

		return $data;
	}

	protected function collect_save_data($data)
	{
		$data = apply_filters(static::hook_name('save_data'), $data, $this);
		$post = $meta = [];

		foreach ($data as $prop => $value) {
			if (isset(self::$post_props[$prop])) {
				$post[self::$post_props[$prop]] = $value;
			} else {
				if (is_bool($value)) {
					$value = (int)$value;
				} elseif (is_float($value)) {
					$value = Number::safe_decimal($value);
				}

				$meta['_' . $prop] = $value;
			}
		}

		return compact('post', 'meta');
	}

	/**
	 * Delete the post.
	 *
	 * @param bool $permanent If false, the post is moved to trash
	 *
	 * @return bool
	 */
	public function delete($permanent = false)
	{
		if (!$this->id) return false;

		$id = $this->id;

		if ($permanent) {
			$result = wp_delete_post($this->id, true);

			if ($result) {
				$this->id = null;
				$this->post = null;
				$this->apply_changes();
			}
		} else {
			$result = wp_trash_post($this->id);

			if ($result) {
				$this->data['status'] = 'trash';
				$this->apply_changes('status');

				if ($this->post) {
					$this->post->post_status = 'trash';
				}
			}
		}

		self::unset_instances($id);

		do_action(self::hook_name('deleted'), $this, $permanent, $result);

		return (bool)$result;
	}

	/**
	 * Moves the post to trash.
	 *
	 * @return bool
	 */
	public function trash()
	{
		return $this->delete(false);
	}

	/**
	 * Restores the post from trash.
	 *
	 * @return bool
	 */
	public function untrash()
	{
		if (!$this->id) return false;

		$prev_status = $this->meta('_wp_trash_meta_status');
		$result = wp_untrash_post($this->id);

		if ($result) {
			$this->data['status'] = $prev_status;
			$this->apply_changes('status');

			if ($this->post) {
				$this->post->post_status = $prev_status;
			}
		}

		self::unset_instances($this->id);

		do_action(self::hook_name('untrashed'), $this, $result);

		return (bool)$result;
	}

	/**
	 * Creates a new object from this object's data.
	 *
	 * @param array $data Extra data to bind to the copy
	 * @param string $context Context for the copy ('view' or 'edit')
	 *
	 * @return static|false
	 */
	public function duplicate($data = [], $context = 'view')
	{
		if (!$this->data) return false;

		$copy = new static(null, $context);
		$copy->data = $this->data;

		$data = apply_filters(static::hook_name('duplicate_data'), $data, $copy, $this);

		$title = $copy->get('title', 'edit');

		if (!isset($data['title'])) {
			if (preg_match('/ \((\d+)\)$/', $title, $matches)) {
				$title = substr($title, 0, -strlen($matches[0])) . ' (' . ((int)$matches[1] + 1) . ')';
			} else {
				$title .= ' (2)';
			}

			$copy->set_title($title);
		}

		if (!isset($data['slug'])) {
			$copy->set_slug(sanitize_title($title));
		}

		if (!isset($data['author_id']) && $author_id = get_current_user_id()) {
			$copy->set_author_id($author_id);
		}

		$now = current_time('mysql');
		$copy->set_created($now);
		$copy->set_modified($now);

		if ($data) {
			$copy->bind($data);
		}

		do_action(static::hook_name('duplicating'), $copy, $this, $data);

		$copy->save();

		do_action(static::hook_name('duplicated'), $copy, $this, $data);

		return $copy;
	}

	/**
	 * Gets a meta value for this post object by key.
	 *
	 * @see get_metadata()
	 *
	 * @param string $key
	 * @param bool $single
	 *
	 * @return mixed
	 */
	public function meta($key, $single = true)
	{
		$value = get_metadata('post', $this->id, $key, $single);

		if ($single) {
			$value = (string)$value;
		} elseif (!is_array($value)) {
			$value = [];
		}

		return $value;
	}

	/**
	 * Gets all metadata for this post object.
	 *
	 * @see get_metadata()
	 *
	 * @param bool $include_props
	 * @param bool $unserialize
	 *
	 * @return array|false
	 */
	public function get_metadata($include_props = false, $unserialize = false)
	{
		$metadata = get_metadata('post', $this->id);

		if ($metadata) {
			if (!$include_props) {
				foreach ($metadata as $key => $meta) {
					if ($key[0] === '_') {
						unset($metadata[$key]);
					}
				}
			}

			if ($unserialize) {
				foreach ($metadata as &$meta) {
					$meta = array_map('maybe_unserialize', $meta);
				}
			}
		}

		return $metadata;
	}

	/**
	 * Wrapper for {@see add_metadata()}.
	 *
	 * @param $key
	 * @param $value
	 * @param bool $unique
	 *
	 * @return false|int
	 */
	public function add_meta($key, $value, $unique = false)
	{
		return add_metadata('post', $this->id, $key, $value, $unique);
	}

	/**
	 * Wrapper for {@see update_metadata()}.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param string $prev_value
	 *
	 * @return bool|int
	 */
	public function update_meta($key, $value, $prev_value = '')
	{
		return update_metadata('post', $this->id, $key, $value, $prev_value);
	}

	/**
	 * Wrapper for {@see metadata_exists()}.
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function meta_exists($key)
	{
		return metadata_exists('post', $this->id, $key);
	}

	/**
	 * Wrapper for {@see delete_metadata()}.
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @return bool
	 */
	public function delete_meta($key, $value = '')
	{
		return delete_metadata('post', $this->id, $key, $value);
	}

	/**
	 * Deletes all matching meta from all posts of this post type.
	 *
	 * @param string $key Only delete meta with this `meta_key`
	 * @param string $value Only delete meta with this `meta_value`
	 *
	 * @return int|false
	 */
	public static function delete_all_meta($key = null, $value = null)
	{
		$query = DB::table('postmeta', 'pm')
			->join('posts', 'p')->on('p.ID = pm.post_id')
			->where('p.post_type', static::POST_TYPE);

		if ($key !== null) {
			$query->where('pm.meta_key', $key);
		}

		if ($value !== null) {
			$query->where('pm.meta_value', $value);
		}

		if (!$post_ids = $query->distinct()->col('p.ID')) {
			return false;
		}

		if (!$deleted = $query->delete()) {
			return false;
		}

		foreach ($post_ids as $post_id) {
			wp_cache_delete($post_id, 'post_meta');
		}

		return $deleted;
	}

	/**
	 * @param array $args WP_Query args
	 * @param string $context 'view' or 'edit'
	 * @param string $return 'object', 'id' or 'query'
	 *
	 * @return static[]|int[]|\WP_Query
	 */
	public static function query(array $args = [], $context = 'view', $return = 'object')
	{
		$query_args = [
			'post_type' => static::POST_TYPE,
			'post_status' => $context === 'view' ? 'publish' : ['publish', 'draft'],
			'posts_per_page' => -1,
			'no_found_rows' => true,
			'ignore_sticky_posts' => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		];

		if ($return === 'id') {
			$query_args['fields'] = 'ids';
		}

		if ($args) {
			$query_args = array_merge($query_args, $args);
		}

		$query = new \WP_Query($query_args);

		if ($return === 'query') {
			return $query;
		}

		if (!$query->posts) return [];

		if (isset($query_args['fields'])) {
			return $query->posts;
		} else {
			$results = [];

			foreach ($query->posts as $post) {
				$results[] = static::instance($post, $context);
			}

			return $results;
		}
	}

	/**
	 * @param array $args
	 * @param string $context 'view' or 'edit'
	 * @param string $return 'object' or 'id'
	 *
	 * @return static|int
	 */
	public static function find(array $args = [], $context = 'view', $return = 'object')
	{
		$args['posts_per_page'] = 1;
		$results = static::query($args, $context, $return);

		if ($results) {
			return $results[0];
		} elseif ($return === 'id') {
			return 0;
		} else {
			return static::instance(null, $context);
		}
	}
}
