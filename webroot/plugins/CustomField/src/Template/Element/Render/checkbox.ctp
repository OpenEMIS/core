<?php if ($ControllerAction['action'] == 'index') : ?>
<?php else : ?>
	<div class="input">
		<label><?= $attr['attr']['label']; ?></label>
		<div class="input-selection"><?= isset($attr['output']) ? $attr['output'] : ''; ?></div>
	</div>
<?php endif ?>
