<?php
	$label = isset($attr['label']) ? $attr['label'] : $attr['field'];
	$required = (isset($attr['attr']['required']) && $attr['attr']['required']) ? 'required' : '';
	$sliderField = str_replace('.', '_', $attr['field']);
	$hasTicks = !empty($attr['ticks']) && !empty($attr['ticksLabels']);
	$ticksJson = $hasTicks ? h(json_encode(array_values($attr['ticks']))) : '';
	$ticksLabelsJson = $hasTicks ? h(json_encode(array_values($attr['ticksLabels']))) : '';
?>

<div class="input <?= $required ?>">
	<?php $this->Form->unlockField($attr['fieldName']); ?>
	<?php if ($label): ?>
		<label for='sss'><?= $label ?></label>
	<?php endif; ?>
	<div class="slider-wrapper input-slider" style="display: inline-block; width: 100%;">
		<slider ng-model="<?= $sliderField ?>" value=" <?=$attr['rating']?>" min="<?=$attr['min']?>" step="<?=$attr['step'] ?>" max="<?= $attr['max']?>"></slider><span style="font-size: 12px"><?php if ($hasTicks): ?>{{<?=$sliderField?> | tickLabel:<?= $ticksJson ?>:<?= $ticksLabelsJson ?>}}<?php else: ?>{{<?=$sliderField?> | number : 1}}<?php endif; ?></span>
	    <?=
			$this->Form->hidden($attr['fieldName'], [
				'label' => false,
				'type' => 'number',
				'value' => '{{'.$sliderField.'}}'
			]);
	    ?>
	</div>
</div>
