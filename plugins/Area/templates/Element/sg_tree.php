<?php $label = isset($attr['label']) ? $attr['label'] : $attr['field']; ?>
<?php if ($label): ?>
<div class="input select<?= $attr['null'] == false ? ' required' : '' ?>">
	<label for="<?= $attr['id'] ?>"><?= $label ?></label>
<?php endif; ?>
	<?php
	$errorMsg = '';
	if (isset($attr['fieldName'])) {
		$errorMsg = $this->Form->error($attr['fieldName']);
	} else {
		$errorMsg = $this->Form->error($attr['field']);
	}
	$divErrorCSS = (!empty($errorMsg))? 'error': '';
	$inputErrorCSS = (!empty($errorMsg))? 'form-error': '';
	$inputWrapperStyle = (isset($attr['inputWrapperStyle'])) ? $attr['inputWrapperStyle'] : '';
	//comment in cakephp4
	//echo $this->Form->unlockField($attr['field'].'-tree');
	$fieldName = (isset($attr['fieldName']))? $attr['fieldName']: $attr['model'].'.'.$attr['field'];
	//comment in cakephp4
	//echo $this->Form->unlockField($fieldName);
	?>
	<div class="tree-form <?= isset($attr['class']) ? $attr['class'] : '' ?> <?php echo $divErrorCSS; ?>" id="<?= $attr['id'] ?>" style="<?= $inputWrapperStyle; ?>" ng-controller="SgTreeCtrl as SgTree" ng-init="SgTree.model='<?= $attr['source_model'] ?>'; <?= !empty($attr['value'])? 'SgTree.outputValue='.$attr['value'] : 'SgTree.outputValue=null'?>; SgTree.userId=<?= $this->request->getSession()->read('Auth.User.id') ?>; SgTree.displayCountry=<?= isset($attr['displayCountry']) && !$attr['displayCountry'] ? 0 : 1 ?>; <?= !empty($attr['onchange'])? 'SgTree.triggerOnChange='.$attr['onchange'] : 'SgTree.triggerOnChange=false'?>; ">
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
