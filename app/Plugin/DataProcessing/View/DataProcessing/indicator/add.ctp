<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->script('/DataProcessing/js/datawarehouse', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');

if(!empty($this->data[$model]['id'])){
	echo $this->Html->link(__('Back'), array('action' => 'indicatorView', $this->data[$model]['id']), array('class' => 'divider', 'id'=>'back'));
}else{
	echo $this->Html->link(__('Back'), array('action' => 'indicator'), array('class' => 'divider', 'id'=>'back'));
}

$this->end();
$this->start('contentBody');
?>
<?php

$formOptions = $this->FormUtility->getFormOptions();
echo $this->Form->create($model, $formOptions);
?>

<?php if(!empty($this->data[$model]['id'])){ echo $this->Form->input('id', array('type'=> 'hidden')); } ?>
<?php if(!empty($this->data['DatawarehouseIndicatorCondition']['id'])){ echo $this->Form->input('DatawarehouseIndicatorCondition.id', array('type'=> 'hidden')); } ?>
<?php if(!empty($this->data['Denominator']['id'])){ echo $this->Form->input('Denominator.id', array('type'=> 'hidden')); } ?>
<?php  echo $this->Form->input($model.'.name', array('label'=>array('text'=> $this->Label->get('Datawarehouse.indicator'),'class'=>'col-md-3 control-label'))); ?>
<?php echo $this->Form->input($model.'.description', array('type'=>'textarea'));?>
<?php echo $this->Form->input($model.'.code');?>
<?php echo $this->Form->input($model.'.datawarehouse_unit_id', array('options'=>$datawarehouseUnitOptions, 'label'=>array('text'=> $this->Label->get('Datawarehouse.unit'),'class'=>'col-md-3 control-label'), 'onchange'=>'objDatawarehouse.getUnitType(this)'));?>


<?php echo $this->element('datawarehouse_condition');?>

<?php echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'indicator'))); ?>
<?php echo $this->Form->end(); ?>

<?php $this->end(); ?>	
