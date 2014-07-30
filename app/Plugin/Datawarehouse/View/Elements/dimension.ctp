<?php 
$typeOption = array('numerator', 'denominator');
foreach($typeOption as $type){ ?>
<div class="tab-pane" id="tab-<?php echo $type;?>">
    <?php echo $this->Form->input('DatawarehouseField.'.$type.'_datawarehouse_module_id', array('class'=>$type.'ModuleOption form-control', 'options'=>$datawarehouseModuleOptions,  'label'=>array('text'=> $this->Label->get('Datawarehouse.module'),'class'=>'col-md-3 control-label'), 'onchange'=>'objDatawarehouse.populateByModule(this, "'.$type.'");','url'=>$this->params['controller']."/ajax_populate_by_module/"));?>
	<?php echo $this->Form->input('DatawarehouseField.'.$type.'_datawarehouse_operator', array('class'=>$type.'OperatorOption form-control','options'=>${$type.'DatawarehouseOperatorFieldOptions'},  'label'=>array('text'=> $this->Label->get('Datawarehouse.function'),'class'=>'col-md-3 control-label')));?>
	<?php //echo $this->Form->input('DatawarehouseField.'.$type.'_datawarehouse_field', array('class'=>$type.'FieldOption form-control', 'options'=>${$type.'DatawarehouseFieldOptions'}, 'label'=>array('text'=> $this->Label->get('Datawarehouse.dimensions'),'class'=>'col-md-3 control-label'),  'onchange'=>'objDatawarehouse.populateByField(this, "'.$type.'");'));?>
	

	<div class="<?php echo $type;?>Dimension">
		<?php echo $this->Form->input('DatawarehouseField.'.$type.'_datawarehouse_field_id', array('type'=>'select', 'multiple'=>'checkbox', 'label'=>array('text'=> $this->Label->get('Datawarehouse.dimensions'),'class'=>'col-md-3 control-label'), 'div'=>'form-group form-dimension', 'class'=>'col-md-6 filter-dimension', 'options'=>${$type.'DatawarehouseDimensionOptions'})); ?>
		<div style="height: 200px; overflow-x: hidden;overflow-y: scroll;">
		<?php echo $this->Form->input('DatawarehouseField.'.$type.'_datawarehouse_subgroup_id', array('type'=>'select', 'multiple'=>'checkbox', 'label'=>false, 'class'=>'col-md-6 filter-option', 'selected'=>${$type.'SelectedSubgroup'}, 'between'=>'<div class="col-md-offset-3 col-md-9">', 'options'=>${$type.'DatawarehouseSubgroupOptions'})); ?>
		</div>
	</div>
	 <div class="form-group"><div class="col-md-offset-4">
        <input type="submit" value="<?php echo __("Previous"); ?>" name='prevStep' class="btn_save btn_right"/>
        <input type="submit" value="<?php echo __('Next');?>" name='nextStep' class="btn_save btn_right" >
        <a href="indicator" class="btn_cancel btn_left"><?php echo __('Cancel');?></a></div>
    </div>
</div>
<?php } ?>