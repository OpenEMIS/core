<?php
echo $this->Html->script('config', false);
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');
if($_edit) {
    echo $this->Html->link(__('Back'), array('action' => 'status'), array('class' => 'divider', 'id'=>'back'));
}
$this->end();
$this->start('contentBody');
?>


<?php echo $this->element('alert'); ?>
<?php

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action), 'file');
echo $this->Form->create($model, $formOptions);

?>
<?php if(!empty($this->data[$modelName]['id'])){ echo $this->Form->input('id', array('type'=> 'hidden')); } ?>
<?php //echo $this->Form->input('institution_id', array('type'=> 'hidden'));  ?>

<?php 
    $dateEnabled = date('Y-m-d');
    $dateDisabled = date('Y-m-d');
    $disabled = false;
    if(isset($this->data[$modelName]['date_enabled'])){
        $dateEnabled = $this->data[$modelName]['date_enabled'];
    }
    if(isset($this->data[$modelName]['date_disabled'])){
        $dateDisabled = $this->data[$modelName]['date_disabled'];
    }
    if($displayType != 'add'){
        $disabled = 'disabled';
    }
?>
<?php 
    echo $this->Form->input('rubric_template_id', array('disabled'=>$disabled, 'options' => $rubricOptions)); 
    echo $this->Form->input('year', array('disabled'=>$disabled, 'options' => $yearOptions)); 
    echo $this->FormUtility->datepicker('date_enabled', array('id' => 'DateEnabled', 'data-date' => $dateEnabled));
    echo $this->FormUtility->datepicker('date_disabled', array('id' => 'DateDisabled', 'data-date' => $dateDisabled));
?>
<div class="controls view_controls">
    <input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
    <?php echo $this->Html->link(__('Cancel'), array('action' => 'status'), array('class' => 'btn_cancel btn_left')); ?>
</div>

<?php echo $this->Form->end(); ?>

<?php $this->end(); ?>  
