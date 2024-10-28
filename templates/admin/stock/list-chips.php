<?php
defined('ABSPATH') or die;

/**
 * @var Mewz\WCAS\Models\AttributeStock $stock
 * @var string $type
 * @var string $class
 * @var array $chips
 * @var bool $wrap
 * @var int $show_max
 * @var int $collapsed_max
 */

if (empty($chips)) {
	return;
}

$wrap ??= true;
$class = !empty($class) ? " $class" : '';
$show_max ??= 3;
$show_collapsed ??= max(1, $show_max - 1);
$total_chips = count($chips);
$n = 0;
?>

<?php if ($wrap): ?>
	<div class="mewz-wcas-chips">
<?php endif; ?>

<?php foreach ($chips as $chip): ?>

	<?= !empty($chip['url']) ? '<a href="' . esc_url($chip['url']) . '"' : '<span' ?> class="mewz-wcas-chip mewz-wcas-chip-<?= $type . $class; ?>"<?= isset($chip['title']) ? ' title="' . esc_attr($chip['title']) . '"' : '' ?>><?php

		if (isset($chip['label'])) {
			echo '<span class="chip-label">' . esc_html($chip['label']) . '<span class="hidden-text">: </span></span>';
		}

		echo esc_html($chip['value']);

		if (isset($chip['meta']) && strlen($chip['meta'])) {
			echo ' <span class="chip-meta">' . esc_html($chip['meta']) . '</span>';
		}

	?><?= !empty($chip['url']) ? '</a>' : '</span>' ?>

	<?php if ($total_chips > $show_max && ++$n == $show_collapsed): ?>
		<button type="button" class="mewz-wcas-chip show-more" data-show="<?= $type ?>" title="<?= esc_attr__('Show more', 'woocommerce-attribute-stock') ?>">+<?= $total_chips - $n ?></button>
		<?php $class .= ' hidden'; ?>
	<?php endif; ?>

<?php endforeach; ?>

<?php if ($wrap): ?>
	</div>
<?php endif; ?>
