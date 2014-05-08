<?php /*
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
?>
<?php 
$obj = $data['TrainingSessionTrainee']; ?>
<?php echo $this->element('breadcrumb'); ?>

<div id="training_result" class="content_wrapper">
	<h1>
		<span><?php echo __($subheader); ?></span>
		<?php
			echo $this->Html->link(__('List'), array('action' => 'trainingResult' ), array('class' => 'divider'));
			
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
		
		<div class="row">
			<div class="label"><?php echo __('Course Code'); ?></div>
			<div class="value"><?php echo $data['TrainingCourse']['code']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Course Title'); ?></div>
			<div class="value"><?php echo $data['TrainingCourse']['title'];?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Provider'); ?></div>
			<div class="value"><?php echo $data['TrainingProvider']['name']; ?></div>
		</div>
	  	<div class="row">
			<div class="label"><?php echo __('Start Date'); ?></div>
			<div class="value"><?php echo $data['TrainingSession']['start_date']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('End Date'); ?></div>
			<div class="value"><?php echo $data['TrainingSession']['end_date']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Course Description'); ?></div>
			<div class="value"><?php echo $data['TrainingCourse']['description']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Location'); ?></div>
			<div class="value"><?php echo $data['TrainingSession']['location']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Trainer'); ?></div>
			<div class="value"><?php echo $data['TrainingSession']['trainer']; ?></div>
		</div>

        <div class="row">
			<div class="label"><?php echo __('Result'); ?></div>
			<div class="value">
			<?php echo $data['TrainingSessionTrainee']['result']; ?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Completed'); ?></div>
			<div class="value">
				 <?php if(!isset($data['TrainingSessionTrainee']['pass'])){
                		echo '-';
                 }else if($data['TrainingSessionTrainee']['pass'] == '1'){
                 		echo __('Passed');
                 }else if($data['TrainingSessionTrainee']['pass'] == '-1'){
                 		echo '-';
                 }else{
                 		echo __('Failed');
                 }
                 ?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Status'); ?></div>
			<div class="value"><?php echo (isset($workflowStatus)?  $workflowStatus : $this->TrainingUtility->getTrainingStatus($modelName,$obj['id'],$data['TrainingStatus']['name'],$data['TrainingStatus']['id'])); ?></div>
		</div>
        <div class="row">
            <div class="label"><?php echo __('Modified by'); ?></div>
            <div class="value"><?php echo trim($data['ModifiedUser']['first_name'] . ' ' . $data['ModifiedUser']['last_name']); ?></div>
        </div>
        
        <div class="row">
            <div class="label"><?php echo __('Modified on'); ?></div>
            <div class="value"><?php echo $data['TrainingSessionResult']['modified']; ?></div>
        </div>
        
        <div class="row">
            <div class="label"><?php echo __('Created by'); ?></div>
            <div class="value"><?php echo trim($data['CreatedUser']['first_name'] . ' ' . $data['CreatedUser']['last_name']); ?></div>
        </div>
        
        <div class="row">
            <div class="label"><?php echo __('Created on'); ?></div>
            <div class="value"><?php echo $data['TrainingSessionResult']['created']; ?></div>
        </div>
</div>
 * 
 */?>

<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$obj = $data['TrainingSessionTrainee'];

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.list'), array('action' => 'trainingResult'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
?>
<div class="row">
	<div class="col-md-3"><?php echo __('Course Code'); ?></div>
	<div class="value"><?php echo $data['TrainingCourse']['code']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Course Title'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingCourse']['title']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Provider'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingProvider']['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Start Date'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingSession']['start_date']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('End Date'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingSession']['end_date']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Course Description'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingCourse']['description']; ?></div>
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
	<div class="col-md-3"><?php echo __('Result'); ?></div>
	<div class="col-md-6">
		<?php echo $data['TrainingSessionTrainee']['result']; ?>
	</div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Completed'); ?></div>
	<div class="col-md-6">
		<?php
		if (!isset($data['TrainingSessionTrainee']['pass'])) {
			echo '-';
		} else if ($data['TrainingSessionTrainee']['pass'] == '1') {
			echo __('Passed');
		} else if ($data['TrainingSessionTrainee']['pass'] == '-1') {
			echo '-';
		} else {
			echo __('Failed');
		}
		?>
	</div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Status'); ?></div>
	<div class="col-md-6"><?php echo (isset($workflowStatus) ? $workflowStatus : $this->TrainingUtility->getTrainingStatus($modelName, $obj['id'], $data['TrainingStatus']['name'], $data['TrainingStatus']['id'])); ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Modified by'); ?></div>
	<div class="col-md-6"><?php echo trim($data['ModifiedUser']['first_name'] . ' ' . $data['ModifiedUser']['last_name']); ?></div>
</div>

<div class="row">
	<div class="col-md-3"><?php echo __('Modified on'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingSessionResult']['modified']; ?></div>
</div>

<div class="row">
	<div class="col-md-3"><?php echo __('Created by'); ?></div>
	<div class="col-md-6"><?php echo trim($data['CreatedUser']['first_name'] . ' ' . $data['CreatedUser']['last_name']); ?></div>
</div>

<div class="row">
	<div class="col-md-3"><?php echo __('Created on'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingSessionResult']['created']; ?></div>
</div>
<?php $this->end(); ?>