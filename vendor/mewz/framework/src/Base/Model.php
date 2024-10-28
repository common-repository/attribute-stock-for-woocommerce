<?php
namespace Mewz\Framework\Base;

abstract class Model
{
	/** @var string The unique post model type (without 'mewz' prefix), used for hook tags, cache keys, etc. */
	const MODEL_TYPE = null;

	/** @var int */
	protected $id;

	/** @var string Context for accessing the object and object data */
	protected $context;

	/** @var array Mutable object data */
	protected $data = [];

	/** @var array Stores original values when data props are set */
	protected $prev_data = [];

	/** @var array Computed changes to data */
	protected $changes;

	/** @var array Valid data properties that can be set (populated automatically) */
	protected $valid_props = [];

	/** @var \WP_Error Stores a WP error instance, e.g. if insert operation fails */
	protected $error;

	/** @var static[][] Model instances by context */
	protected static $instance = [];

	/** @var array Object data properties */
	protected static $props = [];

	/** @var array Alias object property mappings */
	protected static $alias_props = [];

	/**
	 * @param int|mixed $id Unique ID of the data object
	 * @param string $context Context for accessing data ('view' = filtered values, 'edit' = raw values)
	 */
	public function __construct($id = null, $context = 'view')
	{
		$this->id = $id;
		$this->context = $context;

		$this->set_valid_props();
	}

	public function __call($name, $args)
	{
		if (strpos($name, 'set_') === 0) {
			$this->set(substr($name, 4), ...$args);
		}

		throw new \BadMethodCallException("Method '" . static::class . "::$name()' does not exist.");
	}

	/**
	 * Gets a previous instance or creates a new instance if one doesn't exist.
	 *
	 * Note: This should only be used when reading properties. For writing/saving,
	 * a new instance should be created with the `new` keyword.
	 *
	 * @param int|static $id Unique ID of the object
	 * @param string $context Context for accessing the object
	 *                        ('view' = filtered values, 'edit' = raw values)
	 *
	 * @return static
	 */
	public static function instance($id, $context = 'view')
	{
		if ($id instanceof static) {
			$id = $id->id;
		}

		if (!$id) {
			return new static(null, $context);
		}

		if (isset(self::$instance[$context][static::MODEL_TYPE][$id])) {
			return self::$instance[$context][static::MODEL_TYPE][$id];
		}

		return self::$instance[$context][static::MODEL_TYPE][$id] = new static($id, $context);
	}

	public static function hook_name($suffix)
	{
		return 'mewz_' . static::MODEL_TYPE . '_' . $suffix;
	}

	public static function get_alias_prop($prop)
	{
		return static::$alias_props[$prop] ?? false;
	}

	public static function apply_defaults()
	{
		$defaults = apply_filters(static::hook_name('defaults'), []);
		if (!$defaults) return;

		static::$props = array_merge(static::$props, array_intersect_key($defaults, static::$props));
	}

	protected function set_valid_props()
	{
		$this->data = static::$props;

		foreach ($this->data as $prop => $value) {
			$this->valid_props[$prop] = $prop;
		}

		if (static::$alias_props) {
			foreach (static::$alias_props as $alias => $prop) {
				$this->valid_props[$alias] = $alias;
			}
		}
	}

	public function get_valid_props()
	{
	    return $this->valid_props;
	}

	public function id()
	{
		return $this->id;
	}

	public function exists()
	{
		return $this->id > 0;
	}

	public function context()
	{
		return $this->context;
	}

	public function get_data($raw = false)
	{
		if ($raw) {
			return $this->data;
		}

		$data = [];

		foreach ($this->valid_props as $prop) {
			if (method_exists($this, $prop)) {
				$data[$prop] = $this->$prop();
			}
		}

		return $data;
	}

	public function get_changes($raw = false)
	{
		if ($this->changes === null) {
			$this->changes = [];

			foreach ($this->prev_data as $prop => $value) {
				if (is_numeric($value) ? ($this->data[$prop] != $value) : ($this->data[$prop] !== $value)) {
					$this->changes[$prop] = $this->data[$prop];
				}
			}
		}

		if (!$raw && $this->changes && static::$alias_props) {
			$aliases = array_flip(static::$alias_props);
			$changes = [];

			foreach ($this->changes as $prop => $value) {
				$prop = $aliases[$prop] ?? $prop;
				$changes[$prop] = $value;
			}

			return $changes;
		}

		return $this->changes;
	}

	public function apply_changes($prop = null)
	{
		if ($prop === null) {
			$this->prev_data = [];
		} else {
			unset($this->prev_data[$prop]);
		}

		$this->changes = null;
	}

	public function undo_changes($prop = null)
	{
		if ($prop === null) {
			$this->data = array_merge($this->data, $this->prev_data);
		} elseif (array_key_exists($prop, $this->prev_data)) {
			$this->data[$prop] = $this->prev_data[$prop];
		} else {
			return;
		}

		$this->apply_changes($prop);
	}

	public function get($prop, $context = null)
	{
		if (isset(static::$alias_props[$prop])) {
			$prop = static::$alias_props[$prop];
		}

		$context ??= $this->context;
		$value = $this->data[$prop] ?? null;

		if ($context === 'view') {
			$value = apply_filters(static::hook_name('get_' . $prop), $value, $this);
		}

		return $value;
	}

	public function set($prop, $value)
	{
		if (!isset($this->valid_props[$prop])) {
			return false;
		}

		if (isset(static::$alias_props[$prop])) {
			$prop = static::$alias_props[$prop];
		}

		$current = $this->data[$prop] ?? null;

		if ($value !== $current) {
			$this->prev_data[$prop] = $current;
			$this->data[$prop] = $value;
			$this->changes = null;
		}

		return true;
	}

	/**
	 * @param array $data Array of key/value pairs
	 */
	public function bind(array $data)
	{
		foreach ($data as $prop => $value) {
			$method = 'set_' . $prop;

			if (isset($this->valid_props[$prop]) || method_exists($this, $method)) {
				$this->$method($value);
			}
		}
	}

	public function get_original($prop)
	{
		$prop = self::get_alias_prop($prop) ?: $prop;

		if (array_key_exists($prop, $this->prev_data)) {
			$current = $this->data[$prop];
			$this->data[$prop] = $this->prev_data[$prop];
			$original = $this->$prop();
			$this->data[$prop] = $current;

			return $original;
		} else {
			return $this->$prop();
		}
	}

	public function get_contextual($prop, $context)
	{
		if ($context === $this->context) {
			return $this->$prop();
		} else {
			$prev_context = $this->context;
			$this->context = $context;
			$value = $this->$prop();
			$this->context = $prev_context;

			return $value;
		}
	}

	/**
	 * @param string $context
	 * @param callable $func
	 *
	 * @return mixed Return value of the callback function
	 */
	public function with_context($context, callable $func)
	{
		if ($context === $this->context) {
			return $func($this);
		} else {
			$prev_context = $this->context;
			$this->context = $context;
			$value = $func($this);
			$this->context = $prev_context;

			return $value;
		}
	}

	/**
	 * @return \WP_Error
	 */
	public function get_error()
	{
	    return $this->error;
	}

	/**
	 * @return string
	 */
	public function get_error_message()
	{
		return $this->error ? $this->error->get_error_message() : null;
	}

	/**
	 * Save an error message or error object.
	 *
	 * @param \WP_Error|string $error Error message or object to save
	 * @param mixed $return Return this value (defaults to false)
	 *
	 * @return mixed
	 */
	protected function error($error, $return = false)
	{
		if ($error instanceof \WP_Error) {
			$this->error = $error;
		} else {
			$this->error = new \WP_Error('mewz_' . static::MODEL_TYPE, $error, $this);
		}

		return $return;
	}

	/**
	 * Append an error to the last saved error object (or save a new error if it doesn't exist).
	 *
	 * @param \WP_Error|string $error Error message or object to add
	 * @param mixed $return Return this value (defaults to false)
	 *
	 * @return mixed
	 */
	protected function add_error($error, $return = false)
	{
		if ($this->error === null) {
			$this->error($error);
		}
		elseif ($error instanceof \WP_Error) {
			foreach ($error->get_error_codes() as $error_code) {
				$error_messages = $error->get_error_messages($error_code);
				$this->error->add($error_code, array_shift($error_messages), $error->get_error_data($error_code));

				if ($error_messages) {
					foreach ($error_messages as $error_message) {
						$this->error->add($error_code, $error_message);
					}
				}
			}
		}
		else {
			$this->error->add('mewz_' . static::MODEL_TYPE, $error, $this);
		}

		return $return;
	}
}
