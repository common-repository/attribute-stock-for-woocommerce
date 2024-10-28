<?php
namespace Mewz\Framework\Base;

abstract class Aspect extends ServiceProxy
{
	/**
	 * One or more script handles to enqueue for this aspect.
	 *
	 * @var string|string[]
	 */
	public $enqueue = [];

	/**
	 * This method is called as soon as the aspect is loaded. Similar to a constructor,
	 * but can return false to prevent the aspect from being loaded.
	 *
	 * @return void|bool
	 */
	public function __load() {}

	/**
	 * All actions and filters for this aspect are added in this method.
	 *
	 * @return void
	 */
	public function __hooks() {}

	/**
	 * This method is called after scripts have been enqueued. Can be used to add/edit
	 * scripts, or export JS data.
	 *
	 * @return void
	 */
	public function __scripts() {}
}
