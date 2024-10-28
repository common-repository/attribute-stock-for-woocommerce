<?php
namespace Mewz\WCAS\Aspects\Common;

use Mewz\Framework\Base\Aspect;
use Mewz\WCAS\Models\AttributeStock;
use Mewz\WCAS\Util;

class CleanUp extends Aspect
{
	public function __hooks()
	{
		// clear caches
		add_action('clean_post_cache', [$this, 'clear_post_cache'], 10, 2);
		add_action('mewz_attribute_stock_saved', [$this, 'clear_stock_cache'], 0);
		add_action('mewz_attribute_stock_before_save', [$this, 'clear_attribute_cache'], 100);
		add_action('update_option_mewz_wcas_limit_product_stock', [$this, 'clear_stock_cache']);
		add_action('update_option_mewz_wcas_allow_backorders', [$this, 'clear_stock_cache']);
		add_action('update_option_mewz_wcas_unmatched_any_variations', [$this, 'clear_stock_cache']);
		add_action('mewz_wcas_match_rules_saved', [$this, 'clear_match_rules_cache']);
		add_action('mewz_wcas_components_saved', [$this, 'clear_components_cache']);
		add_action('updated_term_meta', [$this, 'clear_term_meta_cache'], 10, 4);
		add_action('deleted_term_meta', [$this, 'clear_term_meta_cache'], 10, 4);

		// delete associated data
		add_action('delete_post', [$this, 'delete_post']);
		add_action('delete_term', [$this, 'delete_term'], 10, 3);
		add_action('woocommerce_attribute_deleted', [$this, 'deleted_attribute']);
	}

	public function clear_post_cache($post_id, \WP_Post $post)
	{
		if ($post->post_type === AttributeStock::POST_TYPE) {
			$this->clear_stock_cache();
		}
		elseif (in_array($post->post_type, ['product', 'product_variation'])) {
			$this->cache->invalidate('product_' . $post_id);

			if ($post->post_parent) {
				$this->cache->invalidate('product_' . $post->post_parent);
			}
		}
	}

	public function clear_stock_cache()
	{
		$this->cache->invalidate('stock');

		// clear product transients
		wc_delete_product_transients();
	}

	public function clear_attribute_cache($stock)
	{
		// clear attribute transients
		$attribute_ids = Util\Matches::query()
			->where('r.stock_id', $stock->id())
			->col('a.attribute_id');

		$taxonomies = [];

		foreach ($attribute_ids as $attribute_id) {
			if ($taxonomy = Util\Attributes::get_attribute_name((int)$attribute_id, true)) {
				$taxonomies[] = $taxonomy;
			}
		}

		if ($taxonomies) {
			\WC_Cache_Helper::invalidate_attribute_count($taxonomies);
		}
	}

	public function clear_match_rules_cache()
	{
		$this->cache->invalidate('match_rules');
	}

	public function clear_components_cache()
	{
		$this->cache->invalidate('components');
	}

	public function clear_term_meta_cache($meta_id, $object_id, $meta_key, $meta_value)
	{
		if ($meta_key === 'mewz_wcas_multiplier') {
			$this->cache->delete('term_multipliers');
			$this->cache->invalidate('multipliers');
		}
	}

	public function delete_post($post_id)
	{
		if (get_post_type($post_id) === AttributeStock::POST_TYPE) {
			Util\Matches::save_rules($post_id, false);
			Util\Components::save_components($post_id, false);
		}
	}

	public function delete_term($term_id, $tt_id, $taxonomy)
	{
		if (strpos($taxonomy, 'pa_') === 0) {
			Util\Compatibility::safe_post_type([Util\Matches::class, 'remove_attribute'], $taxonomy, $term_id);
		}
	}

	public function deleted_attribute($attribute_id)
	{
		Util\Matches::remove_attribute($attribute_id);
	}
}
