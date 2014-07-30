<div class="tab-pane active" id="tab-indicator">
    <?php if(!empty($this->data[$model]['id'])){ echo $this->Form->input('id', array('type'=> 'hidden')); } ?>
    <?php if(!empty($this->data['DatawarehouseIndicatorCondition']['id'])){ echo $this->Form->input('DatawarehouseIndicatorCondition.id', array('type'=> 'hidden')); } ?>
    <?php if(!empty($this->data['Denominator']['id'])){ echo $this->Form->input('Denominator.id', array('type'=> 'hidden')); } ?>
    <?php  echo $this->Form->input($model.'.name', array('label'=>array('text'=> $this->Label->get('Datawarehouse.indicator'),'class'=>'col-md-3 control-label'))); ?>
    <?php echo $this->Form->input($model.'.description', array('type'=>'textarea'));?>
    <?php echo $this->Form->input($model.'.code');?>
    <?php echo $this->Form->input($model.'.datawarehouse_unit_id', array('options'=>$datawarehouseUnitOptions, 'label'=>array('text'=> $this->Label->get('Datawarehouse.unit'),'class'=>'col-md-3 control-label'), 'onchange'=>'objDatawarehouse.getUnitType(this)'));?>

    <div class="form-group"><div class="col-md-offset-4">
        <input type="submit" value="<?php echo __('Next');?>" name='nextStep' class="btn_save btn_right" >
        <a href="indicator" class="btn_cancel btn_left"><?php echo __('Cancel');?></a></div>
    </div>
</div>
