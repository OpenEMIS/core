<?php if ($action == 'index') : ?>

<?php else : ?>
	<div class="input">
		<label><?= $attr['attr']['label']; ?></label>
		<div class="input-checkbox">
			<?= isset($attr['output']) ? $attr['output'] : ''; ?>
		</div>
	</div>
<?php endif ?>
