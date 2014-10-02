<div class="tab-pane" id="tab-review">
     <?php if(!empty($this->request->data)){
     	$data = $this->request->data;
      ?>
      <fieldset class="section_group">
		<legend><?php echo __('Indicator'); ?></legend>
     	<div class="row">
			<div class="col-md-3"><?php echo $this->Label->get('Datawarehouse.indicator');?></div>
			<div class="col-md-6"><?php echo $data['DatawarehouseIndicator']['name'];?></div>
		</div>
		<div class="row">
			<div class="col-md-3"><?php echo $this->Label->get('general.description');?></div>
			<div class="col-md-6"><?php echo $data['DatawarehouseIndicator']['description'];?></div>
		</div>
		<div class="row">
			<div class="col-md-3"><?php echo $this->Label->get('general.code');?></div>
			<div class="col-md-6"><?php echo $data['DatawarehouseIndicator']['code'];?></div>
		</div>
		<div class="row">
			<div class="col-md-3"><?php echo $this->Label->get('Datawarehouse.classification');?></div>
			<div class="col-md-6"><?php echo $data['DatawarehouseIndicator']['classification'];?></div>
		</div>
		<div class="row">
			<div class="col-md-3"><?php echo $this->Label->get('Datawarehouse.unit');?></div>
			<div class="col-md-6"><?php echo $datawarehouseUnitOptions[$data['DatawarehouseIndicator']['datawarehouse_unit_id']];?></div>
		</div>
	 </fieldset>
	<?php 
	$typeOption = array('numerator');
    if(isset($data['DatawarehouseIndicator']['datawarehouse_unit_id']) && $data['DatawarehouseIndicator']['datawarehouse_unit_id']!='1'){
		$typeOption = array('numerator', 'denominator');
    }
	foreach($typeOption as $type){
		$moduleOptions = ${$type.'DatawarehouseDimensionOptions'};
		$dimensionOptions = ${$type.'DatawarehouseDimensionOptions'};
	 ?>
	   <fieldset class="section_group">
		<legend><?php echo __(ucwords($type)); ?></legend>
     	<div class="row">
			<div class="col-md-3"><?php echo $this->Label->get('Datawarehouse.module');?></div>
			<div class="col-md-6"><?php echo $moduleOptions[$data['DatawarehouseField'][$type.'_datawarehouse_module_id']];?></div>
		</div>
		<div class="row">
			<div class="col-md-3"><?php echo $this->Label->get('Datawarehouse.function');?></div>
			<div class="col-md-6"><?php echo $data['DatawarehouseField'][$type.'_datawarehouse_operator']; ?></div>
		</div>
		<div class="row">
			<div class="col-md-3"><?php echo $this->Label->get('Datawarehouse.dimensions');?></div>
			<div class="col-md-6"><?php if(!empty($data['DatawarehouseField'][$type.'_datawarehouse_dimension_id'])){ foreach($data['DatawarehouseField'][$type.'_datawarehouse_dimension_id'] as $val) { 
				echo $dimensionOptions[$val] . '<br />'; } }?></div>
		</div>
		<div style="height: 200px; overflow-x: hidden;overflow-y: scroll;">
			<div class="row">
				<div class="col-md-3"></div>
				<div class="col-md-6"><?php foreach($data['DatawarehouseField'][$type.'_datawarehouse_subgroup_id'] as $val) { 
					echo $val . '<br />'; }?></div>
			</div>
		</div>
	 </fieldset>
	 <?php  } ?>
     <?php } ?>
	 <div class="form-group"><div class="col-md-offset-4">
        <input type="submit" value="<?php echo __("Previous"); ?>" name='prevStep' class="btn_cancel btn_right"/>
        <input type="submit" value="<?php echo __('Finish');?>" name='save' class="btn_save btn_right" >
        <a href="indicator" class="btn_cancel btn_left"><?php echo __('Cancel');?></a></div>
    </div>
</div>