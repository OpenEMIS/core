<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$obj = $data[$model];
$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.list'), array('action' => 'indicator'), array('class' => 'divider'));
if($_edit) {
	echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'indicatorEdit', $obj['id']), array('class' => 'divider'));
}

$this->end();

$this->start('contentBody'); ?>
<?php echo $this->element('alert'); ?>
<?php if(!empty($data)){ 
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
		<div class="col-md-3"><?php echo $this->Label->get('Datawarehouse.unit');?></div>
		<div class="col-md-6"><?php echo $data['DatawarehouseUnit']['name'];?></div>
	</div>
 </fieldset>
<?php 
$typeOption = array('numerator');
if(isset($data['DatawarehouseIndicator']['datawarehouse_unit_id']) && $data['DatawarehouseIndicator']['datawarehouse_unit_id']!='1'){
	$typeOption = array('numerator', 'denominator');
}
foreach($typeOption as $type){
	$typeSubgroupModel = $data['DatawarehouseIndicatorSubgroup'];
	$typeDimensionModel = $data['DatawarehouseIndicatorDimension'];
	if($type=='denominator'){
		$typeSubgroupModel = $data['Denominator']['DatawarehouseIndicatorSubgroup'];
		$typeDimensionModel = $data['Denominator']['DatawarehouseIndicatorDimension'];
	}
 ?>
   <fieldset class="section_group">
	<legend><?php echo __(ucwords($type)); ?></legend>
 	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('Datawarehouse.module');?></div>
		<div class="col-md-6"><?php echo $data['DatawarehouseField'][$type.'_datawarehouse_module_id'];?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('Datawarehouse.function');?></div>
		<div class="col-md-6"><?php echo $data['DatawarehouseField'][$type.'_datawarehouse_operator']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('Datawarehouse.dimensions');?></div>
		<div class="col-md-6"><?php if(!empty($typeDimensionModel)){ foreach($typeDimensionModel as $val) { 
			echo $numeratorDatawarewarehouseDimensionOptions[$val['datawarehouse_dimension_id']] . '<br />'; } }?></div>
	</div>
	<div style="height: 200px; overflow-x: hidden;overflow-y: scroll;">
		<div class="row">
			<div class="col-md-3"></div>
			<div class="col-md-6"><?php if(!empty($typeSubgroupModel)){ foreach($typeSubgroupModel as $val) { 
				echo $val['subgroup'] . '<br />'; } }?></div>
		</div>
	</div>
 </fieldset>
 <?php  } ?>
 <?php } ?>

<div class="row">
    <div class="col-md-3"><?php echo __('Modified by'); ?></div>
    <div class="col-md-6"><?php echo trim($data['ModifiedUser']['first_name'] . ' ' . $data['ModifiedUser']['last_name']); ?></div>
</div>

<div class="row">
    <div class="col-md-3"><?php echo __('Modified on'); ?></div>
    <div class="col-md-6"><?php echo $obj['modified']; ?></div>
</div>

<div class="row">
    <div class="col-md-3"><?php echo __('Created by'); ?></div>
    <div class="col-md-6"><?php echo trim($data['CreatedUser']['first_name'] . ' ' . $data['CreatedUser']['last_name']); ?></div>
</div>

<div class="row">
    <div class="col-md-3"><?php echo __('Created on'); ?></div>
    <div class="col-md-6"><?php echo $obj['created']; ?></div>
</div>

<?php $this->end(); ?>
