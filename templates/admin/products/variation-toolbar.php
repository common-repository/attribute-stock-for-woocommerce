<?php
use Mewz\Framework\Util\Number;

defined('ABSPATH') or die;

/**
 * @var \WP_Post $post
 * @var float|float[] $multiplier
 * @var bool $inherited_multiplier
 */
?>

<span class="mewz_wcas_variation_multiplier<?= $inherited_multiplier ? ' inherited' : '' ?>"<?= $inherited_multiplier ? ' title="' . esc_attr__('Stock multiplier inherited from attribute terms', 'woocommerce-attribute-stock') . '"' : '' ?>>
	<?php if (is_array($multiplier)): ?>
		&times;<?= Number::local_format($multiplier[0]) ?>&ndash;<?= Number::local_format($multiplier[1]) ?>
	<?php else: ?>
		&times;<?= Number::local_format($multiplier) ?>
	<?php endif; ?>
</span>
