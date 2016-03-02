<?php
	$model = $ControllerAction['table'];
	$alias = $model->alias();
?>

<?php if ($ControllerAction['action'] == 'view') : ?>
	<div class="input-selection">
		<div class="input">
			<label class="selection-label"><b>9</b>&nbsp;(<?php echo __('Numbers') ?>)</label>
			<label class="selection-label"><b>a</b>&nbsp;(<?php echo __('Letter') ?>)</label>
			<label class="selection-label"><b>w</b>&nbsp;(<?php echo __('Alphanumeric') ?>)</label>
			<label class="selection-label"><b>*</b>&nbsp;(<?php echo __('Any Character') ?>)</label>
			<label class="selection-label"><b>?</b>&nbsp;(<?php echo __('Optional - any characters following will become optional') ?>)</label>
		</div>
	</div>
<?php elseif ($ControllerAction['action'] == 'add' || $ControllerAction['action'] == 'edit') : ?>
	<div class="input">
		<label for="<?= $attr['id'] ?>"><?= isset($attr['label']) ? $attr['label'] : $attr['field'] ?></label>
		<div class="input-selection">
			<div class="input">
				<label class="selection-label"><b>9</b>&nbsp;(<?php echo __('Numbers') ?>)</label>
				<label class="selection-label"><b>a</b>&nbsp;(<?php echo __('Letter') ?>)</label>
				<label class="selection-label"><b>w</b>&nbsp;(<?php echo __('Alphanumeric') ?>)</label>
				<label class="selection-label"><b>*</b>&nbsp;(<?php echo __('Any Character') ?>)</label>
				<label class="selection-label"><b>?</b>&nbsp;(<?php echo __('Optional - any characters following will become optional') ?>)</label>
			</div>
		</div>
	</div>
<?php endif ?>
