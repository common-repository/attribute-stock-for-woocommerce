<?php
namespace Mewz\Framework\Services;

/**
 * Helper class for working with WordPress Object Cache and Transients API.
 */
class Cache
{
	/** @var string */
	protected $prefix;

	/** @var string */
	protected $version;

	/** @var string */
	protected $prefix_version;

	/** @var string */
	protected $cache_tokens_group;

	/** @var array */
	protected $tokens = [];

	/** @var bool */
	public $persistent = false;

	/** @var bool */
	public $enabled = true;

	/**
	 * @param string $prefix Prefix to use for object cache groups and transient keys
	 * @param string $version Optional version string to group entries in sites with multiple versions/languages
	 * @param bool $enabled Start enabled or disabled (e.g. during development or debugging)
	 */
	public function __construct($prefix, $version = '', $enabled = true)
	{
		global $wp_object_cache;

		$this->prefix = $prefix . '_';
		$this->prefix_version = $this->prefix . $version . '_';
		$this->cache_tokens_group = $this->prefix . 'cache_tokens';
		$this->persistent = get_class($wp_object_cache) !== 'WP_Object_Cache';
		$this->enabled = $enabled;
	}

	/**
	 * @param string $key
	 * @param string|array $tags
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function get($key, $tags = null, $default = null)
	{
		if (!$this->enabled) return $default;

		$found = null;
		$value = wp_cache_get($key, $this->group_name($tags), false, $found);

		if ($found === null) {
			$found = $value !== false;
		}

		return $found ? $value : $default;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @param string|array $tags
	 * @param int $ttl
	 *
	 * @return bool
	 */
	public function set($key, $value, $tags = null, $ttl = 0)
	{
		if (!$this->enabled) return false;

		return wp_cache_set($key, $value, $this->group_name($tags), $ttl);
	}

	/**
	 * @param string $key
	 * @param string|array $tags
	 *
	 * @return bool
	 */
	public function delete($key, $tags = null)
	{
		if (!$this->enabled) return false;

		return wp_cache_delete($key, $this->group_name($tags));
	}

	/**
	 * @param string|array $tags
	 * @param bool $tokenize
	 *
	 * @return string
	 */
	public function group_name($tags, $tokenize = true)
	{
		if ($tags === null) {
			return $this->prefix_version . 'default';
		}

		if ($tokenize) {
			$tags = (array)$tags;

			foreach ($tags as &$tag) {
				$tag = $this->get_token($tag) . $tag;
			}

			return $this->prefix_version . implode('|', $tags);
		}

		if (is_array($tags)) {
			$tags = implode('|', $tags);
		}

		return $this->prefix_version . $tags;
	}

	/**
	 * @param string $tag
	 *
	 * @return int
	 */
	public function get_token($tag)
	{
		if (isset($this->tokens[$tag])) {
			return $this->tokens[$tag];
		}

		$tokens_group = $this->cache_tokens_group;
		$token = wp_cache_get($tag, $tokens_group);

		if ($token === false) {
			$this->tokens[$tag] = $token = 1;
			wp_cache_set($tag, $token, $tokens_group);
		}

		return $token;
	}

	/**
	 * Invalidates all object caches with tag(s).
	 *
	 * @param string ...$tags
	 */
	public function invalidate(...$tags)
	{
		foreach ($tags as $tag) {
			$this->tokens[$tag] = wp_cache_incr($tag, 1, $this->cache_tokens_group);
			//do_action($this->prefix . 'invalidate_cache_' . $tag);
		}
	}

	/**
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function get_transient($key, $default = null)
	{
		if (!$this->enabled) return $default;

		$value = get_transient($this->prefix_version . $key);

		return $value !== false ? $value : $default;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @param int $ttl
	 *
	 * @return bool
	 */
	public function set_transient($key, $value, $ttl = 0)
	{
		if (!$this->enabled) return false;

		return set_transient($this->prefix_version . $key, $value, $ttl);
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function delete_transient($key)
	{
		return delete_transient($this->prefix_version . $key);
	}
}
