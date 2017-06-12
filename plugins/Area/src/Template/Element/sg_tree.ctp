
<?= $this->Html->script('Area.tree/sg.tree.svc', ['block' => true]); ?>
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
	echo $this->Form->unlockField($attr['field'].'-tree');
	$fieldName = (array_key_exists('fieldName', $attr))? $attr['fieldName']: $attr['model'].'.'.$attr['field'];
	echo $this->Form->unlockField($fieldName);
	?>
	<div class="tree-form <?= isset($attr['class']) ? $attr['class'] : '' ?> <?php echo $divErrorCSS; ?>" id="<?= $attr['id'] ?>" style="<?= $inputWrapperStyle; ?>" ng-controller="SgTreeCtrl as SgTree" ng-init="SgTree.model='<?= $attr['source_model'] ?>'; <?= !is_null($attr['value']) ? 'SgTree.outputValue='.$attr['value'] : 'SgTree.outputValue=null'?>; SgTree.authorisedArea='<?= json_encode($attr['authorisedArea']) ?>'; SgTree.displayCountry=<?= isset($attr['displayCountry']) && $attr['displayCountry'] ? 1 : 0 ?>;">
		<kd-tree-dropdown-ng id="<?=$attr['field'] ?>-tree" input-model="SgTree.inputModelText" output-model="outputModelText" model-type="single"></kd-tree-dropdown-ng>
		<?php

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
