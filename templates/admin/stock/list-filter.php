<?php
defined('ABSPATH') or die;

/**
 * @var Mewz\WCAS\Models\AttributeStock $stock
 * @var string $name
 * @var string $class
 * @var string $placeholder
 * @var array $options
 * @var array $grouped
 * @var array $hidden
 */

$class = !empty($class) ? ' ' . $class : '';
$value = $_REQUEST[$name] ?? '';
?>

<select name="<?= esc_attr($name) ?>" id="filter_<?= esc_attr($name) ?>" class="list-filter list-filter-<?= esc_attr(str_replace('_', '-', $name)) ?><?= $class ?>"<?= !empty($hidden) ? ' hidden' : '' ?> title="<?= esc_attr($placeholder) ?>">

	<option value=""><?= esc_html($placeholder) ?></option>

	<?php if (!empty($grouped)): ?>

		<?php foreach ($options as $group): ?>
			<optgroup label="<?= esc_html($group['label']) ?>">
				<?php foreach ($group['options'] as $key => $option): ?>
					<option value="<?= esc_attr($key) ?>" <?php selected($key, $value) ?>><?= esc_html($option) ?></option>
				<?php endforeach; ?>
			</optgroup>
		<?php endforeach; ?>

	<?php else: ?>

		<?php foreach ($options as $key => $option): ?>
			<option value="<?= esc_attr($key) ?>" <?php selected($key, $value) ?>><?= esc_html($option) ?></option>
		<?php endforeach; ?>

	<?php endif; ?>

</select>
