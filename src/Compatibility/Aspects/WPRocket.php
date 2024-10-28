<?php
namespace Mewz\WCAS\Compatibility\Aspects;

use Mewz\Framework\Base\Aspect;
use Mewz\WCAS\Models\AttributeStock;

class WPRocket extends Aspect
{
	public function __hooks()
	{
		add_action('mewz_wcas_stock_change', [$this, 'stock_change']);
	}

	public function stock_change($stock_ids)
	{
		// updating an attribute stock item could potentially affect every product and/or page
		// on the site, so we need to clear all cache whenever any active stock item changes
		foreach ($stock_ids as $stock_id) {
			$stock = AttributeStock::instance($stock_id);

			if ($stock->valid() && !$stock->internal()) {
				add_action('shutdown', 'rocket_clean_domain');
				break;
			}
		}
	}
}
