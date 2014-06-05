<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
?>
<?php $obj = $data[$modelName]; 
?>
<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Results'));
$obj = $data[$modelName];
$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.list'), array('action' => 'result'), array('class' => 'divider'));
if($_edit) {
	if($obj['training_status_id'] == 1){
	    echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'resultEdit', $obj['id']), array('class' => 'divider'));
	}
}
$this->end();

$this->start('contentBody'); ?>
<?php echo $this->element('alert'); ?>

<div class="row">
	<div class="col-md-3"><?php echo __('Course Code'); ?></div>
	<div class="col-md-6"><?php echo $trainingCourses['TrainingCourse']['code']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Course Title'); ?></div>
	<div class="col-md-6"><?php echo $trainingCourses['TrainingCourse']['title']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Provider'); ?></div>
	<div class="col-md-6">
		<?php if(!empty($trainingProviders)){
			echo $trainingProviders['TrainingProvider']['name'];
		} ?>
	</div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Start Date'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingSession']['start_date'];?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('End Date'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingSession']['end_date']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Status'); ?></div>
	<div class="col-md-6"><?php echo (isset($workflowStatus)?  $workflowStatus : $this->TrainingUtility->getTrainingStatus($model,$obj['id'],$data['TrainingStatus']['name'],$data['TrainingStatus']['id'])); ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Location'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingSession']['location']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Trainer'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingSession']['trainer']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Trainees'); ?></div>
	<div class="col-md-6">
		<?php 
		if (!empty($trainingSessionTrainees)){ ?>

				<div class="table-responsive">
					<table class="table table-striped table-hover table-bordered">
			        <thead class="table_head">
			        	<tr>
				       		<td class="table_cell"><?php echo __('Name'); ?></td>
				            <td class="table_cell"><?php echo __('Result'); ?></td>
				            <td class="table_cell"><?php echo __('Completed'); ?></td>
				        </tr>
			        </thead>
			       
			        <tbody>
			        	<?php foreach($trainingSessionTrainees as $val){ ?>
			            <tr class="table_row">
			            	<td class="table_cell"><?php echo $val['Staff']['first_name'] ?>, <?php echo $val['Staff']['last_name'] ?></td>
			                <td class="table_cell"><?php echo $val['TrainingSessionTrainee']['result']; ?></td>
			                <td class="table_cell">
			                <?php if(!isset($val['TrainingSessionTrainee']['pass'])){
			                		echo '-';
			                 }else if($val['TrainingSessionTrainee']['pass'] == '1'){
			                 		echo __('Passed');
			                 }else if($val['TrainingSessionTrainee']['pass'] == '-1'){
			                 		echo '-';
			                 }else{
			                 		echo __('Failed');
			                 }
			                 ?>
			                </td>
			            </tr>
			           <?php } ?>
			        </tbody>
			    	</table>
			    </div>
			<?php
		}else{
			echo "-";
		} ?>

	</div>
</div>
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
<?php echo $this->element('workflow');?>
<?php $this->end(); ?>
