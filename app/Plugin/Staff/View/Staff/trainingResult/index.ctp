<?php /*
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="training_result" class="content_wrapper">
    <h1>
        <span><?php echo __($subheader); ?></span>
    </h1>
    <?php echo $this->element('alert'); ?>
    <?php if(isset($data)) { ?>
    <div class="table allow_hover full_width" action="<?php echo $this->params['controller'];?>/trainingResultView/">
        <div class="table_head">
       		<div class="table_cell"><?php echo __('Code'); ?></div>
            <div class="table_cell"><?php echo __('Title'); ?></div>
            <div class="table_cell"><?php echo __('Credit'); ?></div>
            <div class="table_cell"><?php echo __('Status'); ?></div>
        </div>
        <div class="table_body">
        	<?php foreach($data as $id=>$val) {  ?>
            <div class="table_row" row-id="<?php echo $val['TrainingSessionTrainee']['id']; ?>">
            	<div class="table_cell"><?php echo $val['TrainingCourse']['code'] ?></div>
                <div class="table_cell"><?php echo $val['TrainingCourse']['title'] ?></div>
                <div class="table_cell"><?php echo $val['TrainingCourse']['credit_hours']; ?></div>
                <div class="table_cell"><?php echo $this->TrainingUtility->getTrainingStatus('TrainingSessionResult', $val[$modelName]['id'], $val['TrainingStatus']['name'], $val['TrainingStatus']['id']); ?></div>
                </div>
           <?php } ?>
             </div>
        </div>
    </div>
    <?php } ?>
</div>
 * 
 */?>

<?php

echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentBody');
$tableHeaders = array(__('Code'), __('Title'), __('Credit'), __('Status'));
$tableData = array();
foreach ($data as $obj) {
	$row = array();
	$row[] = $obj['TrainingCourse']['code'];
	$row[] = $this->Html->link($obj['TrainingCourse']['title'], array('action' => 'healthConsultationView', $obj[$model]['id']), array('escape' => false));
	$row[] = $obj['TrainingCourse']['credit_hours'];
	$row[] = $this->TrainingUtility->getTrainingStatus('TrainingSessionResult', $obj[$model]['id'], $obj['TrainingStatus']['name'], $obj['TrainingStatus']['id']);;
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));

$this->end();
?>