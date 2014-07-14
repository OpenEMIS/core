<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->script('/DataProcessing/js/datawarehouse', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');

echo $this->Html->link(__('Back'), array('action' => 'indicator'), array('class' => 'divider', 'id'=>'back'));

$this->end();
$this->start('contentBody');
?>
<?php

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'],'action' => 'indicatorAdd'), 'file');
echo $this->Form->create($model, $formOptions);
?>

<?php if(!empty($this->data[$modelName]['id'])){ echo $this->Form->input('id', array('type'=> 'hidden')); } ?>
<?php  echo $this->Form->input($modelName.'.name', array('label'=>array('text'=> $this->Label->get('Datawarehouse.indicator'),'class'=>'col-md-3 control-label'))); ?>
<?php echo $this->Form->input($modelName.'.description', array('type'=>'textarea'));?>
<?php echo $this->Form->input($modelName.'.code');?>
<?php echo $this->Form->input($modelName.'.datawarehouse_unit_id', array('options'=>$datawarehouseUnitOptions, 'label'=>array('text'=> $this->Label->get('Datawarehouse.unit'),'class'=>'col-md-3 control-label')));?>


<div id="divNumerator" class="form-group">
	<div class="col-md-12">
	<fieldset class="section_group">
		<legend><?php echo __('Numerator'); ?></legend>
		<div class="row">
		<?php echo $this->Form->input('DatawarehouseField.datawarehouse_module_id', array('empty'=>__('--Module--'), 'class'=>'numeratorModuleOption form-control', 'options'=>$datawarehouseModuleOptions, 'label'=>false, 'div'=>false, 'onchange'=>'objDatawarehouse.populateByModule(this, "numerator");',
			'url'=>$this->params['controller']."/getFieldOptionByModuleId/"));?>
		<?php echo $this->Form->input('DatawarehouseField.datawarehouse_operator_id', array('empty'=>__('--Operator--'),'class'=>'numeratorOperatorOption form-control','options'=>$datawarehouseOperatorFieldOptions, 'label'=>false, 'div'=>false));?>
		<?php echo $this->Form->input('DatawarehouseField.datawarehouse_field_id', array('empty'=>__('--Field--'), 'class'=>'numeratorFieldOption form-control', 
		'options'=>$datawarehouseFieldOptions, 'label'=>false, 'div'=>false));?>
		<?php echo $this->Form->input($modelName.'.datawarehouse_field_id', array('class'=>'numeratorFieldID', 'type'=> 'hidden')); ?>
		</div>
		<div class="row">
			<div class="table numerator-condition" style="padding-left:15px;width:98%;">
			<div class="delete-numerator-condition" name="data[DeleteNumeratorCondition][{index}][id]"></div>
			<table class="table table-striped table-hover table-bordered table_body">
			<thead>
				<th><?php echo __('Dimension');?></th>
				<th><?php echo __('Value');?></th>
			</thead>
			<tbody></tbody>
			</table>
			</div>
			<div style="padding-left:15px;width:98%;">
				<a class="void icon_plus" onclick="objDatawarehouse.addNumeratorCondition(this)" url="DataProcessing/ajax_add_numerator_condition"  href="javascript: void(0)"><?php echo __('Add Dimension Row');?></a>
			</div>
		</div>
	</fieldset>
	</div>
</div>
<div id="divDenominator" class="hide">
<fieldset class="section_group">
	<legend><?php echo __('Denominator'); ?></legend>
	
</fieldset>
</div>
<?php echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'indicator'))); ?>
<?php echo $this->Form->end(); ?>

<?php $this->end(); ?>	
