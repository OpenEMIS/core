
<?php $label = isset($attr['label']) ? $attr['label'] : $attr['field']; ?>
<?php if ($label): ?>
<div class="input select<?= $attr['null'] == false ? ' required' : '' ?>">
	<label for="<?= $attr['id'] ?>"><?= $label ?></label>
<?php endif; ?>
	<?php
	$errorMsg = '';
	if (array_key_exists('fieldName', $attr)) {
		$errorMsg = $this->Form->error($attr['fieldName']);
	} else {
		$errorMsg = $this->Form->error($attr['field']);
	}
	$divErrorCSS = (!empty($errorMsg))? 'error': '';
	$inputErrorCSS = (!empty($errorMsg))? 'form-error': '';
	$inputWrapperStyle = (array_key_exists('inputWrapperStyle', $attr)) ? $attr['inputWrapperStyle'] : '';
	?>
	<div class="tree-form <?= isset($attr['class']) ? $attr['class'] : '' ?> <?php echo $divErrorCSS; ?>" id="<?= $attr['id'] ?>" style="<?= $inputWrapperStyle; ?>" ng-controller="SgTreeCtrl as SgTree" ng-init="SgTree.model='<?=$attr['source-model'] ?>'; SgTree.outputValue=<?= isset($attr['value']) ? $attr['value'] : null?>;">
		<kd-tree-dropdown-ng input-model="SgTree.inputModelText" output-model="outputModelText"></kd-tree-dropdown-ng>
		<?php
			$fieldName = (array_key_exists('fieldName', $attr))? $attr['fieldName']: $attr['model'].'.'.$attr['field'];
			echo $this->Form->hidden($fieldName, [
				'ng-value' => 'SgTree.outputValue'
			]);
		 ?>
	</div>
	<?php
	echo $errorMsg;
	?>
<?php if ($label): ?>
</div>
<?php endif; ?>
