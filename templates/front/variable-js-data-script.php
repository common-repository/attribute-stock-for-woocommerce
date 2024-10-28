<?php
defined('ABSPATH') or die;

/**
 * @var array $js_data
 * @var string $inline_script
 */

if (empty($js_data) || empty($inline_script)) {
	return;
}

if ($data = json_encode($js_data)) {
	$data = addcslashes($data, "\\'");
}
?>

<script type="text/javascript">
	// WooCommerce Attribute Stock: Expand product variations dynamically
	(() => {
		const variableData = JSON.parse('<?= $data ?>');

		if (!window.mewzWcas || !mewzWcas.expandProductVariations) {
			<?= $inline_script ?>;
		}

		mewzWcas.expandProductVariations(variableData);
	})();
</script>
