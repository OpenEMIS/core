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
	<div class="tree-form <?= isset($attr['class']) ? $attr['class'] : '' ?> <?php echo $divErrorCSS; ?>" id="<?= $attr['id'] ?>" style="<?= $inputWrapperStyle; ?>" ng-controller="SgTreeCtrl as SgTree" ng-init="SgTree.model='<?= $attr['source_model'] ?>'; <?= !empty($attr['value'])? 'SgTree.outputValue='.$attr['value'] : 'SgTree.outputValue=null'?>; SgTree.userId=<?= $this->request->session()->read('Auth.User.id') ?>; SgTree.displayCountry=<?= isset($attr['displayCountry']) && !$attr['displayCountry'] ? 0 : 1 ?>; <?= !empty($attr['onchange'])? 'SgTree.triggerOnChange='.$attr['onchange'] : 'SgTree.triggerOnChange=false'?>; ">
		<kd-tree-dropdown-ng id="<?=$attr['field'] ?>-tree" expand-parent="SgTree.triggerLoad(refreshList)" output-model="outputModelText" model-type="single" text-config="textConfig"></kd-tree-dropdown-ng>
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
