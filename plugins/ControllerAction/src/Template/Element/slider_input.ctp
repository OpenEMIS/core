<?php
	$label = isset($attr['label']) ? $attr['label'] : $attr['field'];
	$attr['field'] = str_replace('.', '_', $attr['field']);
?>

<div class="input">
	<?php
	$this->Form->unlockField($attr['fieldName']);
	if ($label): ?>
	<label for='sss'><?= $label ?></label>
	<?php endif; ?>
	<div class="slider-wrapper input-slider<?= $attr['null'] == false ? ' required' : '' ?>" style="display: inline-block; width: 100%;">
		<slider ng-model="<?=$attr['field'] ?>" value=" <?=$attr['rating']?>" min="<?=$attr['min']?>" step="0.5" max="<?= $attr['max']?>"></slider><span style="font-size: 12px">{{<?=$attr['field']?> | number : 1}}</span>
	    <?= $this->Form->hidden($attr['fieldName'], [
	            'label' => false,
	            'type' => 'number',
	            'value' => '{{'.$attr['field'].'}}'
	        ]);
	    ?>
	</div>
</div>
