<?php if ($ControllerAction['action'] == 'index') : ?>
<?php else : ?>
	<div class="input">
		<label>
			<?= $attr['attr']['label']; ?>
			<?php if (isset($attr['attr']['required']) && $attr['attr']['required'] === 'required'): ?>
				<span style="color:#d9534f;margin-left:2px;">*</span>
			<?php endif; ?>
		</label>
		<div class="input-selection">
			<?= isset($attr['output']) ? $attr['output'] : ''; ?>
		</div>
	</div>
<?php endif ?>
