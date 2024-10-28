<?php
namespace Mewz\Framework\Base;

use Mewz\Framework\Services;

/**
 * This interface exists only to provide IDE completion for magic service class properties.
 *
 * @property Services\Aspects  $aspects
 * @property Services\Assets   $assets
 * @property Services\Cache    $cache
 * @property Services\Context  $context
 * @property Services\Scripts  $scripts
 * @property Services\Tasks    $tasks
 * @property Services\View     $view
 */
interface ServiceAccess
{
	public function __get($name);
}
