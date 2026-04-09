<?php if ($ControllerAction['action'] == 'index') : ?>
<?php else : ?>
	<?php $hasError = isset($attr['error']) && !empty($attr['error']); //POCOR-6233 ?>
	<div class="input<?= $hasError ? ' error' : '' ?>">
		<label><?= $attr['attr']['label']; ?></label>
		<div class="input-selection"><?= isset($attr['output']) ? $attr['output'] : ''; ?></div>
		<?php if ($hasError): //POCOR-6233: start - show inline error below checkboxes ?>
			<div class="error-message"><?= h($attr['error']); ?></div>
		<?php endif; //POCOR-6233: end ?>
	</div>
<?php endif ?>
