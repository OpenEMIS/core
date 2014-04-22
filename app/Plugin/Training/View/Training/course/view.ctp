<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
?>
<?php $obj = $data[$modelName]; ?>
<?php echo $this->element('breadcrumb'); ?>

<div id="training_course" class="content_wrapper">
	<h1>
		<span><?php echo __($subheader); ?></span>
		<?php
			echo $this->Html->link(__('List'), array('action' => 'course' ), array('class' => 'divider'));
			if($_edit) {
				if($obj['training_status_id'] == 1){
					echo $this->Html->link(__('Edit'), array('action' => 'courseEdit',$obj['id'] ), array('class' => 'divider'));
				}
			}
			if($_delete) {
				if($obj['training_status_id'] == 1){
					echo $this->Html->link(__('Delete'), array('action' => 'courseDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
				}
			}
			if($_execute) {
				if($obj['training_status_id']==3){
					echo $this->Html->link(__('Inactivate'), array('action' => 'courseInactivate'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmInactivate(this)'));
				}
			}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
		
		<div class="row">
			<div class="label"><?php echo __('Course Code'); ?></div>
			<div class="value"><?php echo $obj['code']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Course Title'); ?></div>
			<div class="value"><?php echo $obj['title'];?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Status'); ?></div>
			<div class="value"><?php echo (isset($workflowStatus)?  $workflowStatus : $this->TrainingUtility->getTrainingStatus($modelName,$obj['id'],$data['TrainingStatus']['name'],$data['TrainingStatus']['id'])); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Course Description'); ?></div>
			<div class="value"><?php echo $obj['description']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Goal / Objectives'); ?></div>
			<div class="value"><?php echo $obj['objective']; ?></div>
		</div>
        <div class="row">
			<div class="label"><?php echo __('Category / Field of Study'); ?></div>
			<div class="value"><?php echo $data['TrainingFieldStudy']['name']; ?></div>
		</div>
		 <div class="row">
			<div class="label"><?php echo __('Course Type'); ?></div>
			<div class="value"><?php echo $data['TrainingCourseType']['name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Target Population'); ?></div>
			<div class="value">
				<?php 
				if (!empty($trainingCourseTargetPopulations)){ 
					foreach($trainingCourseTargetPopulations as $val){
						if($val['TrainingCourseTargetPopulation']['position_title_table']=='teacher_position_titles'){
							echo $teacherPositionTitles[$val['TrainingCourseTargetPopulation']['position_title_id']] . '<br />';
						}else{
							echo $staffPositionTitles[$val['TrainingCourseTargetPopulation']['position_title_id']] . '<br />';
						}
					}
				}else{
					echo "-";
				} ?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Credits'); ?></div>
			<div class="value"><?php echo $obj['credit_hours']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Hours'); ?></div>
			<div class="value"><?php echo $obj['duration']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Mode of Delivery'); ?></div>
			<div class="value"><?php echo $data['TrainingModeDelivery']['name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Training Provider'); ?></div>
			<div class="value">
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
			<div class="label"><?php echo __('Training Requirement'); ?></div>
			<div class="value"><?php echo $data['TrainingRequirement']['name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Training Level'); ?></div>
			<div class="value"><?php echo $obj['training_level_id']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Prerequisite'); ?></div>
			<div class="value">
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
			<div class="label"><?php echo __('Pass Result'); ?></div>
			<div class="value"><?php echo $obj['pass_result']; ?></div>
		</div>
		<div class="row">
	        <div class="label"><?php echo __('Attachments'); ?></div>
	        <div class="value">
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
            <div class="label"><?php echo __('Modified by'); ?></div>
            <div class="value"><?php echo trim($data['ModifiedUser']['first_name'] . ' ' . $data['ModifiedUser']['last_name']); ?></div>
        </div>
        
        <div class="row">
            <div class="label"><?php echo __('Modified on'); ?></div>
            <div class="value"><?php echo $obj['modified']; ?></div>
        </div>
        
        <div class="row">
            <div class="label"><?php echo __('Created by'); ?></div>
            <div class="value"><?php echo trim($data['CreatedUser']['first_name'] . ' ' . $data['CreatedUser']['last_name']); ?></div>
        </div>
        
        <div class="row">
            <div class="label"><?php echo __('Created on'); ?></div>
            <div class="value"><?php echo $obj['created']; ?></div>
        </div>
		<?php echo $this->element('workflow');?>
		
</div>
