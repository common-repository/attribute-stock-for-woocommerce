<?php
namespace Mewz\WCAS\Aspects\Admin\Stock;

use Mewz\Framework\Base\Aspect;
use Mewz\Framework\Util\Multilang;
use Mewz\Framework\Util\Admin;
use Mewz\WCAS\Models\AttributeStock;
use Mewz\WCAS\Util\Attributes;
use Mewz\WCAS\Util\Components;
use Mewz\WCAS\Util\Products;

class StockEdit extends Aspect
{
	public $enqueue = '@admin/stock-edit';

	public function __hooks()
	{
		$this->spoof_backlink_referer();

		add_filter('woocommerce_screen_ids', [$this, 'woocommerce_screen_ids']);
		add_action('add_meta_boxes_' . AttributeStock::POST_TYPE, [$this, 'add_meta_boxes']);
		add_filter('post_updated_messages', [$this, 'post_updated_messages']);
		add_action('admin_notices', [$this, 'admin_notices'], 20);
	}

	public function __scripts()
	{
		wp_dequeue_script('autosave');
	}

	public function woocommerce_screen_ids($screen_ids)
	{
		$screen_ids[] = AttributeStock::POST_TYPE;

		return $screen_ids;
	}

	public function add_meta_boxes()
	{
		global $wp_meta_boxes;

		add_meta_box('mewz-wcas-status-metabox', __('Status'), [$this, 'display_status_metabox'], AttributeStock::POST_TYPE, 'side', 'high');
		add_meta_box('mewz-wcas-details-metabox', __('Stock details', 'woocommerce-attribute-stock'), [$this, 'display_details_metabox'], AttributeStock::POST_TYPE, 'normal', 'high');
		add_meta_box('mewz-wcas-matches-metabox', __('Match rules', 'woocommerce-attribute-stock'), [$this, 'display_matches_metabox'], AttributeStock::POST_TYPE, 'normal');

		remove_meta_box('submitdiv', AttributeStock::POST_TYPE, 'side');
		remove_meta_box('slugdiv', AttributeStock::POST_TYPE, 'normal');

		// override Tags title
		foreach ($wp_meta_boxes[AttributeStock::POST_TYPE] as &$a) {
		    foreach ($a as &$b) {
			    foreach ($b as $key => &$metabox) {
				    if ($metabox && $key === 'tagsdiv-product_tag') {
					    $metabox['title'] = __('Tags');
						break;
				    }
			    }
		    }
		}
	}

	public function display_status_metabox($post)
	{
		$stock = AttributeStock::instance($post, 'edit');

		$this->view->render('admin/stock/metabox-status', ['stock' => $stock]);
	}

	public function display_details_metabox($post)
	{
		$stock = AttributeStock::instance($post, 'edit');

		$tabs = [
			'inventory' => __('Inventory', 'woocommerce'),
			'settings' => __('Settings'),
			'components' => __('Components', 'woocommerce-attribute-stock'),
		];

		foreach ($tabs as $key => $tab) {
		    add_action('mewz_wcas_stock_details_panel_' . $key, [$this, 'details_panel_' . $key], 10, 2);
		}

		$tabs = apply_filters('mewz_wcas_stock_details_tabs', $tabs);

		foreach ($tabs as $key => $tab) {
			if (is_string($tab)) {
				$tabs[$key] = ['label' => $tab];
			}
		}

		$this->view->render('admin/stock/metabox-details', [
			'stock' => $stock,
			'tabs' => $tabs,
		]);
	}

	public function display_matches_metabox($post)
	{
		$stock = AttributeStock::instance($post, 'edit');

		$tabs = [
			'attributes' => __('Attributes', 'woocommerce'),
			'filters' => __('Filters', 'woocommerce-attribute-stock'),
		];

		foreach ($tabs as $key => $tab) {
			add_action('mewz_wcas_matches_panel_' . $key, [$this, 'matches_panel_' . $key], 10, 2);
		}

		$tabs = apply_filters('mewz_wcas_matches_tabs', $tabs);

		foreach ($tabs as $key => $tab) {
			if (is_string($tab)) {
				$tabs[$key] = ['label' => $tab];
			}
		}

		if (Multilang::active()) {
			$reset_lang = Multilang::set_lang(Multilang::get_lang('default'));
		}

		$this->view->render('admin/stock/metabox-matches', [
			'stock' => $stock,
			'tabs' => $tabs,
		]);

		if (!empty($reset_lang)) {
			Multilang::reset_lang();
		}
	}

	public function details_panel_inventory(AttributeStock $stock, $tab)
	{
		$this->view->render('admin/stock/panel-inventory', ['stock' => $stock]);
	}

	public function details_panel_settings(AttributeStock $stock, $tab)
	{
		$this->view->render('admin/stock/panel-settings', ['stock' => $stock]);
	}

	public function details_panel_components(AttributeStock $stock, $tab)
	{
		$this->scripts->export_data('components', [
			'name' => 'mewz_wcas_components',
			'components' => $stock->components(),
			'stockList' => $this->get_stock_list($stock->id()),
			'i18n' => $this->get_components_i18n(),
		]);
	}

	public function matches_panel_attributes(AttributeStock $stock, $tab)
	{
		$this->scripts->export_data('matchRules', [
			'name' => 'mewz_wcas_rules',
			'attributes' => $this->get_attribute_data(),
			'rules' => $this->get_match_rule_data($stock),
			'locale' => str_replace('_', '-', get_bloginfo('language')),
			'i18n' => $this->get_match_rules_i18n(),
		]);
	}

	public function matches_panel_filters(AttributeStock $stock, $tab)
	{
		$this->view->render('admin/stock/panel-filters', [
			'stock' => $stock,
			'products' => $this->get_product_options($stock->products()),
			'exclude_products' => $this->get_product_options($stock->exclude_products()),
			'categories' => Admin::get_category_options(),
			'product_types' => Products::get_product_types(),
		]);
	}

	public function get_attribute_data()
	{
		$attributes = Attributes::get_attributes();
		$term_options = Attributes::get_term_options($attributes);
		$term_multipliers = Attributes::get_term_multipliers();
		$show_names = Attributes::find_irregular_names($attributes, 'label');
		$attribute_data = [];

		foreach ($attributes as $attr) {
			$display_name = isset($show_names[$attr->label]) ? $attr->name : null;
			$terms = $term_options[$attr->taxonomy] ?? [];

			if ($terms) {
				foreach ($terms as &$term) {
					$term_id = (int)$term[0];

					if (isset($term_multipliers[$attr->taxonomy][$term_id])) {
						$term[] = $term_multipliers[$attr->taxonomy][$term_id];
					}
				}
			}

			$attribute_data[$attr->id] = [
				'label' => Attributes::get_display_label($attr->label, $display_name),
				'terms' => $terms,
			];
		}

		return apply_filters('mewz_wcas_match_rules_attribute_data', $attribute_data);
	}

	public function get_match_rule_data(AttributeStock $stock)
	{
		$rules = array_values($stock->match_rules());

		foreach ($rules as &$rule) {
			$attributes = [];

		    foreach ($rule['attributes'] as $attr_id => $term_ids) {
			    $attributes[] = [$attr_id, $term_ids];
		    }

			$rule['attributes'] = $attributes;
		}

		return $rules;
	}

	public function get_match_rules_i18n()
	{
	    return apply_filters('mewz_wcas_edit_match_rules_i18n', [
		    'addAttribute'         => __('Add attribute', 'woocommerce'),
		    'any'                  => __('Any', 'woocommerce-attribute-stock'),
		    'anyOption'            => __('Any %s', 'woocommerce'),
		    'attributePlaceholder' => __('Attribute', 'woocommerce') . '...',
		    'closeAll'             => __('Close all', 'woocommerce'),
		    'dragTip'              => __('Drag to re-order', 'woocommerce-attribute-stock'),
		    'duplicateRule'        => __('Duplicate', 'woocommerce'),
		    'expandAll'            => __('Expand all', 'woocommerce'),
		    'multiplierInherited'  => __('Stock multiplier inherited from attribute terms', 'woocommerce-attribute-stock'),
		    'multiplierLabel'      => __('Stock multiplier', 'woocommerce-attribute-stock'),
		    'multiplierTip'        => __('The amount of stock reduced per item purchased. Can be set to 0 to force out of stock, or to -1 to stop matching.', 'woocommerce-attribute-stock'),
		    'newRule'              => __('New rule', 'woocommerce-attribute-stock'),
		    'newRuleTip'           => __('Attribute rules are matched against products from top to bottom in order. Only the first matched rule will be used, unless <strong>Multiplex matching</strong> is enabled. A product or variation must have all attributes in a rule to match.', 'woocommerce-attribute-stock'),
		    'removeAttribute'      => __('Remove'),
		    'removeRule'           => __('Remove'),
		    'restoreRule'          => __('Restore last removed rule', 'woocommerce-attribute-stock'),
			'ruleTitle'            => __('Rule #%s', 'woocommerce-attribute-stock'),
			'stopRuleTip'          => __('Stop rule — When matched, excludes this and subsequent rules from matching,', 'woocommerce-attribute-stock'),
		    'termPlaceholder'      => '...',
	    ]);
	}

	public function get_components_i18n()
	{
		return apply_filters('mewz_wcas_edit_components_i18n', [
			'parent' => [
				'label'          => __('Parent components', 'woocommerce-attribute-stock'),
				'fieldTip'       => __('Parent components are stock items that use this stock item as a component.', 'woocommerce-attribute-stock'),
				'addPlaceholder' => __('Add parent component...', 'woocommerce-attribute-stock'),
				'quantityTip'    => __('Child quantity per parent', 'woocommerce-attribute-stock'),
			],
			'child' => [
				'label'          => __('Child components', 'woocommerce-attribute-stock'),
				'fieldTip'       => __('Child components are stock items that this stock item uses as components.', 'woocommerce-attribute-stock'),
				'addPlaceholder' => __('Add child component...', 'woocommerce-attribute-stock'),
				'quantityTip'    => __('Child quantity per parent', 'woocommerce-attribute-stock'),
			],
			'remove'             => __('Remove'),
			'disabled'           => __('Disabled', 'woocommerce'),
		]);
	}

	public function get_stock_list($exclude_id = null)
	{
		$args = [
			'orderby' => 'title',
			'order' => 'ASC',
			'post__not_in' => $exclude_id ? [$exclude_id] : null,
		];

		$stock_ids = AttributeStock::query($args, 'edit', 'id');
		$stock_list = [];

		_prime_post_caches($stock_ids);

		foreach ($stock_ids as $stock_id) {
			$stock = AttributeStock::instance($stock_id, 'edit');

			$stock_list[] = [
				'id' => $stock->id(),
				'title' => $stock->title(),
				'sku' => $stock->sku(),
				'image' => wp_get_attachment_image($stock->image_id(), [32, 32]),
				'enabled' => $stock->enabled(),
			];
		}

		return $stock_list;
	}

	public function get_product_options($product_ids)
	{
		$options = [];

		foreach ($product_ids as $product_id) {
			if ($product = wc_get_product($product_id)) {
				$options[$product_id] = htmlspecialchars(Products::get_formatted_product_name($product));
			}
		}

		return $options;
	}

	public function post_updated_messages($messages)
	{
		global $post;

		$stock = AttributeStock::instance($post, 'edit');

		$messages[AttributeStock::POST_TYPE] = [
			0 => '', // Unused. Messages start at index 1.
			1 => __('Attribute stock updated.', 'woocommerce-attribute-stock'),
			2 => __('Attribute stock field updated.', 'woocommerce-attribute-stock'),
			3 => __('Attribute stock field deleted.', 'woocommerce-attribute-stock'),
			4 => __('Attribute stock updated.', 'woocommerce-attribute-stock'),
			5 => __('Revision restored.', 'woocommerce'),
			6 => __('Attribute stock updated.', 'woocommerce-attribute-stock'),
			7 => __('Attribute stock saved.', 'woocommerce-attribute-stock'),
			8 => __('Attribute stock submitted.', 'woocommerce-attribute-stock'),
			9 => sprintf(
				/* translators: %s: date */
				__( 'Attribute stock scheduled for: %s.', 'woocommerce-attribute-stock' ),
				'<strong>' . $stock->created(false, 'admin-full') . '</strong>'
			),
			10 => __('Attribute stock updated.', 'woocommerce-attribute-stock'),
		];

		if ($back_link = $this->get_back_link()) {
			$messages[AttributeStock::POST_TYPE][4] .= '</p><p>' . $back_link;
		}

		return $messages;
	}

	public function get_back_link()
	{
		$referer = wp_get_referer();

		if ($referer) {
			if (strpos($referer, '/edit.php') !== false && strpos($referer, 'post_type=' . AttributeStock::POST_TYPE) !== false) {
				$text = __('Back to attribute stock', 'woocommerce-attribute-stock');
			}
			elseif (strpos($referer, '/term.php?taxonomy=') !== false) {
				if (preg_match('/tag_ID=(\d+)/', $referer, $matches) && isset($matches[1]) && $term = get_term($matches[1])) {
					$text = sprintf(__('Back to "%s" attribute term', 'woocommerce-attribute-stock'), $term->name);
				} else {
					$text = __('Back to attribute term', 'woocommerce-attribute-stock');
				}
			}
			elseif (strpos($referer, '/edit-tags.php?taxonomy=') !== false) {
				if (preg_match('/taxonomy=(\w+)/', $referer, $matches) && isset($matches[1]) && $attribute = Attributes::get_attribute($matches[1])) {
					$text = sprintf(__('Back to "%s" attribute terms', 'woocommerce-attribute-stock'), $attribute->label);
				} else {
					$text = __('Back to attribute terms', 'woocommerce-attribute-stock');
				}
			}
			elseif (strpos($referer, '/edit.php?post_type=product&page=product_attributes&edit=') !== false) {
				if (preg_match('/edit=(\d+)/', $referer, $matches) && isset($matches[1]) && $attribute = Attributes::get_attribute($matches[1])) {
					$text = sprintf(__('Back to "%s" attribute', 'woocommerce-attribute-stock'), $attribute->label);
				} else {
					$text = __('Back to attribute', 'woocommerce-attribute-stock');
				}
			}
			elseif (strpos($referer, '/edit.php?post_type=product&page=product_attributes') !== false) {
				$text = __('Back to attributes', 'woocommerce-attribute-stock');
			}
			elseif (strpos($referer, '/admin.php?page=wc-reports&tab=' . AttributeStock::POST_TYPE) !== false) {
				$text = __('Back to stock report', 'woocommerce-attribute-stock');
			}
		}

		if (!isset($text)) {
			$text = __('Back to attribute stock', 'woocommerce-attribute-stock');
			$referer = AttributeStock::admin_url();
		}

		return '<a href="' . esc_url($referer) . '">&larr; ' . esc_html($text) . '</a>';
	}

	public function spoof_backlink_referer()
	{
		if (!empty($_GET['back'])) {
			$_REQUEST['_wp_http_referer'] = $_GET['back'];
		}
	}

	public function admin_notices()
	{
		global $post;

		// check for component circular references
		if (Components::using_components() && Components::has_components($post->ID)) {
			$stock_items[$post->ID] = [
				'stock_id' => $post->ID,
				'stock_qty' => 0,
				'multiplier' => 1,
			];

			if (
				($tree = Components::get_unsorted_tree($stock_items, false))
				&& ($tree = Components::sort_tree($tree))
				&& $tree instanceof \WP_Error
				&& $tree->get_error_code() === 'circular_reference'
			) {
				trigger_error($tree->get_error_message(), E_USER_WARNING);

				$message = __('Circular reference detected in component tree. Component stock will be disabled until you\'ve resolved this.', 'woocommerce-attribute-stock');
				Admin::display_notice('warning', '<p><strong>' . __('Warning:') . '</strong> ' . $message . '</p>', true, true);
			}
		}
	}
}
