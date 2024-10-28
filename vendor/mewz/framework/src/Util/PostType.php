<?php
namespace Mewz\Framework\Util;

class PostType
{
	public static function add($post_type, array $args)
	{
		$args = apply_filters("mewz_post_type_{$post_type}_args", $args);

		if (!isset($args['labels']['name'])) {
			if (isset($args['label'])) {
				$args['labels']['name'] = $args['label'];
			} else {
				throw new \InvalidArgumentException("Required 'name' label not set for post type '$post_type'.");
			}
		}

		if (!isset($args['labels']['singular_name'])) {
			$args['labels']['singular_name'] = $args['labels']['name'];
		}

		if (isset($args['show_in_menu']) && is_string($args['show_in_menu'])) {
			$args['labels']['all_items'] = $args['labels']['name'];
		}

		$args['labels'] = self::merge_labels($args['labels'], self::default_post_type_labels());

		if (!empty($args['taxonomies'])) {
			$args['taxonomies'] = (array)$args['taxonomies'];
		}

		return register_post_type($post_type, $args);
	}

	public static function add_taxonomy($taxonomy, array $args)
	{
		$args = apply_filters("mewz_taxonomy_{$taxonomy}_args", $args);

		if (!isset($args['labels']['name'])) {
			if (isset($args['label'])) {
				$args['labels']['name'] = $args['label'];
			} else {
				throw new \InvalidArgumentException("Required 'name' label not set for taxonomy '$taxonomy'.");
			}
		}

		if (!isset($args['labels']['singular_name'])) {
			$args['labels']['singular_name'] = $args['labels']['name'];
		}

		$args['labels'] = self::merge_labels($args['labels'], self::default_taxonomy_labels());

		$post_types = !empty($args['post_types']) ? (array)$args['post_types'] : null;

		return register_taxonomy($taxonomy, $post_types, $args);
	}

	public static function add_capabilities($capability_type, $roles)
	{
		$wp_roles = wp_roles();

		foreach (self::get_capabilities($capability_type) as $cap) {
		    foreach ((array)$roles as $role) {
			    $wp_roles->add_cap($role, $cap);
		    }
		}
	}

	public static function get_capabilities($capability_type)
	{
	    return [
		    "delete_{$capability_type}",
			"delete_others_{$capability_type}",
			"delete_private_{$capability_type}",
			"delete_published_{$capability_type}",
			"edit_{$capability_type}",
			"edit_others_{$capability_type}",
			"edit_private_{$capability_type}",
			"edit_published_{$capability_type}",
			"publish_{$capability_type}",
			"read_private_{$capability_type}",
	    ];
	}

	protected static function merge_labels($labels, $defaults)
	{
		$replace = [
			'plural' => $labels['name'],
			'singular' => $labels['singular_name'],
			'plural_lower' => strtolower($labels['name']),
			'singular_lower' => strtolower($labels['singular_name']),
		];

		foreach ($defaults as $label_key => $default_label) {
			if (!isset($labels[$label_key])) {
				$labels[$label_key] = sprintf($default_label[0], $replace[$default_label[1]]);
			}
		}

		if (!isset($labels['menu_name'])) {
			$labels['menu_name'] = $labels['name'];
		}

		return $labels;
	}

	public static function default_post_type_labels()
	{
		static $labels;

		return $labels ?: $labels = [
			'add_new'                  => [__('Add New', 'woocommerce'),                    'singular'],
			'add_new_item'             => [__('Add New %s', 'mewz-framework'),              'singular'],
			'edit_item'                => [__('Edit %s', 'woocommerce'),                    'singular'],
			'new_item'                 => [__('New %s', 'mewz-framework'),                  'singular'],
			'view_item'                => [_x('View %s', 'post type view singular', 'mewz-framework'), 'singular'],
			'view_items'               => [_x('View %s', 'post type view plural', 'mewz-framework'), 'plural'],
			'search_items'             => [__('Search %s', 'woocommerce'),                  'plural'],
			'not_found'                => [__('No &quot;%s&quot; found', 'woocommerce' ),   'plural_lower'],
			'not_found_in_trash'       => [__('No %s found in Trash.', 'mewz-framework'),   'plural_lower'],
			'parent_item_colon'        => [__('Parent %s', 'woocommerce'),                  'singular'],
			'all_items'                => [__('All %s', 'woocommerce'),                     'plural'],
			'archives'                 => [__('%s Archives', 'mewz-framework'),             'singular'],
			'attributes'               => [__('%s Attributes', 'mewz-framework'),           'singular'],
			'insert_into_item'         => [__('Insert into %s', 'mewz-framework'),          'plural_lower'],
			'uploaded_to_this_item'    => [__('Uploaded to this %s', 'mewz-framework'),     'singular_lower'],
			'filter_items_list'        => [__('Filter %s list', 'mewz-framework'),          'plural_lower'],
			'items_list_navigation'    => [__('%s list navigation', 'mewz-framework'),      'plural'],
			'items_list'               => [__('%s list', 'mewz-framework'),                 'plural'],
			'item_published'           => [__('%s published.', 'mewz-framework'),           'singular'],
			'item_published_privately' => [__('%s published privately.', 'mewz-framework'), 'singular'],
			'item_reverted_to_draft'   => [__('%s reverted to draft.', 'mewz-framework'),   'singular'],
			'item_scheduled'           => [__('%s scheduled.', 'mewz-framework'),           'singular'],
			'item_updated'             => [__('%s updated.', 'mewz-framework'),             'singular'],
		];
	}

	public static function default_taxonomy_labels()
	{
		static $labels;

		return $labels ?: $labels = [
			'add_new'                    => [_x('Add New', 'woocommerce'),                         'singular'],
			'add_new_item'               => [__('Add New %s', 'mewz-framework'),                   'singular'],
			'search_items'               => [__('Search %s', 'woocommerce'),                       'plural'],
			'popular_items'              => [__('Popular %s', 'mewz-framework'),                   'plural'],
			'all_items'                  => [__('All %s', 'woocommerce'),                          'plural'],
			'parent_item'                => [__('Parent %s', 'woocommerce'),                       'singular'],
			'parent_item_colon'          => [__('Parent %s:', 'mewz-framework'),                   'singular'],
			'edit_item'                  => [__('Edit %s', 'woocommerce'),                         'singular'],
			'view_item'                  => [_x('View %s', 'post type view singular', 'mewz-framework'), 'singular'],
			'update_item'                => [__('Update %s', 'mewz-framework'),                    'singular'],
			'new_item_name'              => [__('New %s Name', 'mewz-framework'),                  'singular'],
			'separate_items_with_commas' => [__('Separate %s with commas', 'mewz-framework'),      'plural_lower'],
			'add_or_remove_items'        => [__('Add or remove %', 'mewz-framework'),              'plural_lower'],
			'choose_from_most_used'      => [__('Choose from the most used %s', 'mewz-framework'), 'plural_lower'],
			'not_found'                  => [__('No %s found.', 'mewz-framework'),                 'plural_lower'],
			'no_terms'                   => [__('No %s', 'mewz-framework'),                        'plural_lower'],
			'items_list_navigation'      => [__('%s list navigation', 'mewz-framework'),           'plural'],
			'items_list'                 => [__('%s list', 'mewz-framework'),                      'plural'],
			'back_to_items'              => [__('&larr; Back to %s', 'mewz-framework'),            'plural'],
		];
	}

	public static function get_post_type_single_label($post_type)
	{
		$object = get_post_type_object($post_type);
		if (!$object) return false;

		return $object->labels->singular_name ?? null;
	}

	public static function get_post_type_plural_label($post_type)
	{
		$object = get_post_type_object($post_type);
		if (!$object) return false;

		return $object->labels->name ?? null;
	}

	public static function get_post_type_icon($post_type)
	{
		$object = get_post_type_object($post_type);
		if (!$object) return false;

		return $object->menu_icon;
	}
}
