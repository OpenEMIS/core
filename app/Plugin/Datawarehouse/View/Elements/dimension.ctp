<?php 
$typeOption = array('numerator', 'denominator');
foreach($typeOption as $type){ ?>
<div class="tab-pane" id="tab-<?php echo $type;?>">

    <?php echo $this->Form->input('DatawarehouseField.'.$type.'_datawarehouse_module_id', array('empty'=>__('--Module--'), 'class'=>$type.'ModuleOption form-control', 'options'=>$datawarehouseModuleOptions,  'label'=>array('text'=> $this->Label->get('Datawarehouse.module'),'class'=>'col-md-3 control-label'), 'onchange'=>'objDatawarehouse.populateByModule(this, "'.$type.'");','url'=>$this->params['controller']."/ajax_populate_by_module/"));?>
	<?php echo $this->Form->input('DatawarehouseField.'.$type.'_datawarehouse_operator', array('empty'=>__('--Function--'),'class'=>$type.'OperatorOption form-control','options'=>${$type.'DatawarehouseOperatorFieldOptions'},  'label'=>array('text'=> $this->Label->get('Datawarehouse.function'),'class'=>'col-md-3 control-label'), 'onchange'=>'objDatawarehouse.populateByModuleOperator(this, "'.$type.'");', 'url'=>$this->params['controller']."/ajax_populate_by_operator/"));?>
	<?php echo $this->Form->input('DatawarehouseField.'.$type.'_datawarehouse_field', array('empty'=>__('--Dimension--'), 'class'=>$type.'FieldOption form-control', 'options'=>${$type.'DatawarehouseFieldOptions'}, 'label'=>array('text'=> $this->Label->get('Datawarehouse.dimensions'),'class'=>'col-md-3 control-label'),  'onchange'=>'objDatawarehouse.populateByField(this, "'.$type.'");'));?>
	<?php echo $this->Form->input($model.'.'.$type.'_datawarehouse_field_id', array('class'=>$type.'FieldID', 'type'=> 'hidden')); ?>
</div>
<?php } ?>