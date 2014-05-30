<?php  /*
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="training_need" class="content_wrapper">
    <h1>
        <span><?php echo __($subheader); ?></span>
        <?php
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'trainingNeedAdd'), array('class' => 'divider'));
		}
		?>
    </h1>
    <?php echo $this->element('alert'); ?>
    <?php if(isset($data)) { ?>
    <div class="table allow_hover full_width" action="<?php echo $this->params['controller'];?>/trainingNeedView/">
        <div class="table_head">
       		<div class="table_cell"><?php echo __('Code'); ?></div>
            <div class="table_cell"><?php echo __('Title'); ?></div>
            <div class="table_cell"><?php echo __('Credit'); ?></div>
            <div class="table_cell"><?php echo __('Status'); ?></div>
        </div>
        <div class="table_body">
        	<?php foreach($data as $id=>$val) { ?>
            <div class="table_row" row-id="<?php echo $val[$modelName]['id']; ?>">
            	<div class="table_cell"><?php echo $val['TrainingCourse']['code'] ?></div>
                <div class="table_cell"><?php echo $val['TrainingCourse']['title'] ?></div>
                <div class="table_cell"><?php echo $val['TrainingCourse']['credit_hours']; ?></div>
                <div class="table_cell"><?php echo $this->TrainingUtility->getTrainingStatus($modelName, $val[$modelName]['id'], $val['TrainingStatus']['name'], $val[$modelName]['training_status_id']); ?></div>
                </div>
           <?php } ?>
             </div>
        </div>
    </div>
    <?php } ?>
</div>
*/ ?>

<?php

echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'trainingNeedAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Code'), __('Title'), __('Credit'), __('Status'));
$tableData = array();
foreach ($data as $obj) {
	$row = array();
	$row[] = $obj['TrainingCourse']['code'];
	$row[] = $this->Html->link($obj['TrainingCourse']['title'] , array('action' => 'trainingNeedView', $obj[$model]['id']), array('escape' => false));
	$row[] = $obj['TrainingCourse']['credit_hours'];
	$status = $this->TrainingUtility->getTrainingStatus($model, $obj[$model]['id'], $obj['TrainingStatus']['name'], $obj[$model]['training_status_id']);
	$row[] = $status;
	
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));

$this->end();
?>