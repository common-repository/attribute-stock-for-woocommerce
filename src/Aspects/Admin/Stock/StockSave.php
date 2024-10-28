<?php
namespace Mewz\WCAS\Aspects\Admin\Stock;

use Mewz\Framework\Base\Aspect;
use Mewz\WCAS\Models\AttributeStock;

class StockSave extends Aspect
{
	public function __hooks()
	{
		add_filter('wp_insert_post_data', [$this, 'wp_insert_post_data'], 10, 4);
		add_action('save_post_' . AttributeStock::POST_TYPE, [$this, 'save_attribute_stock'], 10, 3);
		add_action('post_updated', [$this, 'post_updated'], 15, 3);
	}

	public function wp_insert_post_data($data, $postarr, $unsanitized_postarr = null, $update = true)
	{
		if (!$update || empty($_POST['post_ID']) || empty($postarr['ID']) || empty($postarr['post_type']) || $postarr['post_type'] !== AttributeStock::POST_TYPE || empty($_POST['mewz_wcas']) || (int)$_POST['post_ID'] !== (int)$postarr['ID'] || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) {
			return $data;
		}

		if (current_user_can('publish_post', (int)$postarr['ID'])) {
			$data['post_status'] = !empty($_POST['mewz_wcas']['enabled']) ? 'publish' : 'draft';
		}

	    return $data;
	}

	public function save_attribute_stock($post_id, $post, $update)
	{
		if (!$update || empty($_POST['post_ID']) || empty($_POST['mewz_wcas']) || (int)$_POST['post_ID'] !== $post->ID || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || $post->post_type === 'revision') {
			return;
		}

		$stock = new AttributeStock($post, 'edit');
		if (!$stock->valid()) return;

		$data = wp_unslash($_POST['mewz_wcas']);

		// post status is handled above
		unset($data['enabled']);

		// reset slug to title
		if ($stock->slug() !== ($slug = sanitize_title($post->post_title))) {
			$data['slug'] = wp_unique_post_slug($slug, $post->ID, $post->post_status, $post->post_type, $post->post_parent);
		}

		// save checkboxes/switches
		$data['multiplex'] = !empty($data['multiplex']);
		$data['lock_multipliers'] = !empty($data['lock_multipliers']);

		// only save limit product stock option in full version
		if (!MEWZ_WCAS_LITE) {
			$data['internal'] = !empty($data['internal']);

			if (!$data['internal']) {
				$data['product_sku'] = !empty($data['product_sku']);
				$data['product_image'] = !empty($data['product_image']);
			}
		}

		// save empty multiselect lists
		empty($data['products']) && $data['products'] = [];
		empty($data['exclude_products']) && $data['exclude_products'] = [];
		empty($data['categories']) && $data['categories'] = [];
		empty($data['exclude_categories']) && $data['exclude_categories'] = [];
		empty($data['product_types']) && $data['product_types'] = [];

		$stock->bind($data);

		if (empty($_POST['mewz_wcas_noupdate']['components'])) {
			$stock->save_components($_POST['mewz_wcas_components'] ?? []);
		}

		if (empty($_POST['mewz_wcas_noupdate']['rules'])) {
			$stock->save_match_rules($_POST['mewz_wcas_rules'] ?? []);
		}

		$stock->save(true);

		add_filter('redirect_post_location', [$this, 'redirect_post_location'], 10, 2);
	}

	public function redirect_post_location($location, $post_id)
	{
		if (!empty($_REQUEST['referredby']) && strpos($location, 'message=4') !== false && strpos($location, '&back=') === false) {
			$location = add_query_arg(['back' => urlencode(stripslashes($_REQUEST['referredby']))], $location);
		}

	    return $location;
	}

	public function post_updated($post_id, $post_after, $post_before)
	{
		if ($post_after->post_type === AttributeStock::POST_TYPE) {
			// remove '_wp_old_slug' meta as it's not needed for attribute stock posts
			delete_post_meta($post_id, '_wp_old_slug');
		}
	}
}
