<?php
	$model = $ControllerAction['table'];
	$alias = $model->alias();
?>

<?php if ($ControllerAction['action'] == 'add' || $ControllerAction['action'] == 'edit') : ?>
	<div class="input">
		<label for="<?= $attr['id'] ?>"><?= isset($attr['label']) ? $attr['label'] : $attr['field'] ?></label>
<?php endif ?>
		<div class="input-selection">
			<div class="input">
				<label class="selection-label"><b><?= __('9'); ?></b>&nbsp;:&nbsp;<?= __('Numbers'); ?></label>
				<label class="selection-label"><b><?= __('a'); ?></b>&nbsp;:&nbsp;<?= __('Letter'); ?></label>
				<label class="selection-label"><b><?= __('w'); ?></b>&nbsp;:&nbsp;<?= __('Alphanumeric'); ?></label>
				<label class="selection-label"><b><?= __('*'); ?></b>&nbsp;:&nbsp;<?= __('Any Character'); ?></label>
				<label class="selection-label"><b><?= __('?'); ?></b>&nbsp;:&nbsp;<?= __('Optional - any characters following will become optional'); ?></label>
				<label class="selection-label"><b><?= __('Example Format'); ?></b>&nbsp;:&nbsp;<?= __('9999aaaa'); ?>&nbsp;=>&nbsp;<b><?= __('Example Input'); ?></b>&nbsp;:&nbsp;<?= __('1234abcd'); ?></label>
			</div>
		</div>
<?php if ($ControllerAction['action'] == 'add' || $ControllerAction['action'] == 'edit') : ?>
	</div>
<?php endif ?>
