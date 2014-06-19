<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Courses'));
$obj = $data[$modelName];
$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.list'), array('action' => 'course'), array('class' => 'divider'));
if($_edit) {
	if($obj['training_status_id'] == 1){
	    echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'courseEdit', $obj['id']), array('class' => 'divider'));
	}
}
if($_delete) {
	if($obj['training_status_id'] == 1){
    	echo $this->Html->link($this->Label->get('general.delete'), array('action' => 'courseDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
	}
}
if($_execute) {
	if($obj['training_status_id']==3){
		echo $this->Html->link($this->Label->get('general.inactivate'), array('action' => 'courseInactivate'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmInactivate(this)'));
	}
}
$this->end();

$this->start('contentBody'); ?>
<?php echo $this->element('alert'); ?>
<div class="row">
	<div class="col-md-3"><?php echo __('Course Code'); ?></div>
	<div class="col-md-6"><?php echo $obj['code']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Course Title'); ?></div>
	<div class="col-md-6"><?php echo $obj['title'];?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Status'); ?></div>
	<div class="col-md-6"><?php echo (isset($workflowStatus)?  $workflowStatus : $this->TrainingUtility->getTrainingStatus($model,$obj['id'],$data['TrainingStatus']['name'],$data['TrainingStatus']['id'])); ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Course Description'); ?></div>
	<div class="col-md-6"><?php echo $obj['description']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Goal / Objectives'); ?></div>
	<div class="col-md-6"><?php echo $obj['objective']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Category / Field of Study'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingFieldStudy']['name']; ?></div>
</div>
 <div class="row">
	<div class="col-md-3"><?php echo __('Course Type'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingCourseType']['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Target Population'); ?></div>
	<div class="col-md-6">
		<?php 
		if (!empty($trainingCourseTargetPopulations)){ 
			foreach($trainingCourseTargetPopulations as $val){
				echo $staffPositionTitles[$val['TrainingCourseTargetPopulation']['staff_position_title_id']] . '<br />';
			}
		}else{
			echo "-";
		} ?>
	</div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Credits'); ?></div>
	<div class="col-md-6"><?php echo $obj['credit_hours']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Hours'); ?></div>
	<div class="col-md-6"><?php echo $obj['duration']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Mode of Delivery'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingModeDelivery']['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Training Provider'); ?></div>
	<div class="col-md-6">
		<?php if (!empty($trainingCourseProviders)){ 
			foreach($trainingCourseProviders as $val){
				echo $trainingProviders[$val['TrainingCourseProvider']['training_provider_id']] . '<br />';
			}
		}else{
			echo "-";
		} ?>
	</div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Training Requirement'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingRequirement']['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Training Level'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingLevel']['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Course Prerequisite'); ?></div>
	<div class="col-md-6">
		<?php if (!empty($trainingCoursePrerequisites)){ 
			foreach($trainingCoursePrerequisites as $val){
				echo $val['TrainingPrerequisiteCourse']['code'] . ' - ' . $val['TrainingPrerequisiteCourse']['title']  . '<br />';
			}
		}else{
			echo "-";
		} ?>
	</div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Specialisation'); ?></div>
	<div class="col-md-6">
		<?php if (!empty($trainingCourseSpecialisations)){ 
			foreach($trainingCourseSpecialisations as $val){
				echo $val['QualificationSpecialisation']['name']  . '<br />';
			}
		}else{
			echo "-";
		} ?>
	</div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Experience'); ?></div>
	<div class="col-md-6">
		<?php 
		if (!empty($trainingCourseExperiences)){ 
			foreach($trainingCourseExperiences as $val){
				$years = floor(intval($val['TrainingCourseExperience']['months'])/12);
				$months = intval($val['TrainingCourseExperience']['months']) - ($years*12);

				echo $years . ' ' . __('Year(s)') . ', ' . $months . ' ' . __('Month(s)') . '<br />';
			}
		}else{
			echo "-";
		} ?>
	</div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Result Type'); ?></div>
	<div class="col-md-6">
		<?php if (!empty($trainingCourseResultTypes)){ 
			foreach($trainingCourseResultTypes as $val){
				echo $val['TrainingResultType']['name']  . '<br />';
			}
		}else{
			echo "-";
		} ?>
	</div>
</div>
<div class="row">
    <div class="col-md-3"><?php echo __('Attachments'); ?></div>
    <div class="col-md-6">
	<?php if(!empty($attachments)){?>
    <?php foreach($attachments as $key=>$value){ 
        $obj = $value[$_model];
		$link = $this->Html->link($obj['name'], array('action' => 'attachmentsCourseDownload', $obj['id']));
        echo $link . '<br />'; 
    } ?>
	<?php }?>
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