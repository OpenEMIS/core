<?php if ($ControllerAction['action'] == 'index') : ?>
<?php else : ?>
<style>
/* Silent/never-answered mandatory checkbox — matches the disabled kd-checkbox-radio gray appearance */
.cf-checkbox-silent [type="checkbox"]:indeterminate + label { opacity: 0.5; }
.cf-checkbox-silent [type="checkbox"]:not(:checked):not(:indeterminate) + label { opacity: 0.5; }
</style>
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
