<?php
	$label = isset($attr['label']) ? $attr['label'] : $attr['field'];
	$required = (isset($attr['attr']['required']) && $attr['attr']['required']) ? 'required' : '';
	$sliderField = str_replace('.', '_', $attr['field']);
?>

<div class="input <?= $required ?>">
	<?php $this->Form->unlockField($attr['fieldName']); ?>
	<?php if ($label): ?>
		<label for='sss'><?= $label ?></label>
	<?php endif; ?>
	<div class="slider-wrapper input-slider" style="display: inline-block; width: 100%;">
		<slider ng-model="<?= $sliderField ?>" value=" <?=$attr['rating']?>" min="<?=$attr['min']?>" step="<?=$attr['step'] ?>" max="<?= $attr['max']?>"></slider><span style="font-size: 12px">{{<?=$sliderField?> | number : 1}}</span>
	    <?=
			$this->Form->hidden($attr['fieldName'], [
				'label' => false,
				'type' => 'number',
				'value' => '{{'.$sliderField.'}}'
			]);
	    ?>
	</div>
</div>
