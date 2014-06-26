<?php /*
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Teachers/css/teachers', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Teachers/js/teachers', false);
echo $this->Html->script('/Teachers/js/training', false);
?>

<?php echo $this->element('breadcrumb'); ?>
<div id="training" class="content_wrapper">
	<h1>
		<span><?php echo __('Training'); ?></span>
        <?php
        //if($_add) {
        //    echo $this->Html->link('Add', array(), array('class' => 'divider void', 'onclick' => "Training.show('TrainingAdd')"));
        //}
        if($_edit) {
            echo $this->Html->link(__('Edit'), array('action' => 'trainingEdit'), array('class' => 'divider'));
        }
        ?>
	</h1>
    <?php echo $this->element('alert'); ?>

		<div class="table full_width">
			<div class="table_head">
				<div class="table_cell cell_title"><?php echo __('Completed Date'); ?></div>
				<div class="table_cell"><?php echo __('Category'); ?></div>
			</div>
			
			<div class="table_body">
				<?php foreach($data as $obj): ?>
				<div class="table_row">
					<div class="table_cell"><?php echo $this->Utility->formatDate($obj['completed_date']); ?></div>
					<div class="table_cell"><?php echo $obj['name']; ?></div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>

	<?php
    echo $this->Form->create('TeacherTraining', array(
        'id' => 'TeacherTraining',
        'model' => 'TeacherTraining',
        'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
    ));
    ?>
    <?php echo $this->Form->end(); ?>

</div>*/?>

<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
    echo $this->Html->link(__('Add'), array('action' => 'trainingAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Completed Date'), __('Category'));
$tableData = array();
foreach($data as $obj) {
	$row = array();
	$row[] = $obj[$model]['completed_date'] ;
    $row[] = $this->Html->link($obj['StaffTrainingCategory']['name'], array('action' => 'trainingView', $obj[$model]['id']), array('escape' => false)) ;
        
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));

 $this->end(); ?>