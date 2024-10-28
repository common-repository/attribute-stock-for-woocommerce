<?php
namespace Mewz\WCAS\Aspects\Admin\Attributes;

use Mewz\Framework\Base\Aspect;
use Mewz\Framework\Util\Number;
use Mewz\WCAS\Models\AttributeStock;
use Mewz\WCAS\Util\Attributes;
use Mewz\WCAS\Util\Compatibility;
use Mewz\WCAS\Util\Matches;

class AttributeTermSave extends Aspect
{
	public function __hooks()
	{
		add_action('created_term', [$this, 'created_term'], 10, 3);
		add_action('edited_term', [$this, 'edited_term'], 10, 3);
	}

	public function created_term($term_id, $tt_id, $taxonomy)
	{
		if (empty($_REQUEST['mewz_wcas']) || strpos($taxonomy, 'pa_') !== 0) {
			return;
		}

		$this->save_attribute_stock('added', $taxonomy, $term_id, $_REQUEST['mewz_wcas']);
	}

	public function edited_term($term_id, $tt_id, $taxonomy)
	{
		if (empty($_REQUEST['mewz_wcas']) || !isset($_REQUEST['taxonomy'], $_REQUEST['tag_ID']) || $_REQUEST['taxonomy'] !== $taxonomy || $_REQUEST['tag_ID'] != $term_id || strpos($taxonomy, 'pa_') !== 0) {
			return;
		}

		$this->save_attribute_stock('updated', $taxonomy, $term_id, $_REQUEST['mewz_wcas']);
	}

	public function save_attribute_stock($action, $attribute, $term_id, $data)
	{
		$multiplier = isset($data['multiplier']) && $data['multiplier'] >= 0 ? Number::safe_decimal($data['multiplier']) : '';

		if ($multiplier !== '') {
			update_term_meta($term_id, 'mewz_wcas_multiplier', $data['multiplier']);
		} else {
			delete_term_meta($term_id, 'mewz_wcas_multiplier');
		}

		if ($action === 'added' && empty($data['manage_stock'])) {
			return;
		}

		if ($action === 'added' || empty($data['stock_id'])) {
			$stock = new AttributeStock(null, 'edit');
		}
		elseif ($action === 'updated') {
			$stock = new AttributeStock($data['stock_id'], 'edit');

			if (!$stock->valid()) {
				$stock = new AttributeStock(null, 'edit');
			}
		}
		else return;

		$new = !$stock->exists();

		if (!empty($data['manage_stock'])) {
			if ($new) {
				if (!$term_id) {
					$data['title'] = sanitize_text_field($_REQUEST['attribute_label']);
				} else {
					$attribute_label = Attributes::get_attribute_label($attribute);
					$term_label = $_REQUEST['tag-name'] ?? $_REQUEST['name'];

					$data['title'] = $attribute_label . ': ' . sanitize_text_field($term_label);
				}
			}

			$stock->bind($data);
			$stock->set_enabled();

			$saved = Compatibility::safe_post_type([$stock, 'save']);

			if ($saved && $new) {
				$attribute_id = Attributes::get_attribute_id($attribute);

				if (!$term_id) {
					if (!in_array($attribute_id, $stock->meta('attribute_level', false))) {
						$stock->add_meta('attribute_level', $attribute_id);
						$this->cache->invalidate('attribute_level');
					}

					Matches::add_single_rule($stock->id(), $attribute_id);
				} else {
					Matches::add_single_rule($stock->id(), $attribute_id, $term_id);
				}
			}
		} elseif (!$new) {
			$stock->set_enabled(false);
			$stock->save();
		}
	}
}
