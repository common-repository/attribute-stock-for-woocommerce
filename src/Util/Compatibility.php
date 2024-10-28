<?php
namespace Mewz\WCAS\Util;

use Mewz\WCAS\Models\AttributeStock;

class Compatibility
{
	/**
	 * Prevent some silly plugins thinking a product is being saved when $_POST['post_type'] = 'product'.
	 *
	 * @param callable $callback
	 * @param mixed ...$args
	 *
	 * @return mixed
	 */
	public static function safe_post_type($callback, ...$args)
	{
		if (isset($_POST['post_type']) && $_POST['post_type'] === 'product') {
			$_POST['post_type'] = AttributeStock::POST_TYPE;
			$result = $callback(...$args);
			$_POST['post_type'] = 'product';
		} else {
			$result = $callback(...$args);
		}

		return $result;
	}
}
