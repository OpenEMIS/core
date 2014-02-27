<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('attachments', 'stylesheet', array('inline' => false));
echo $this->Html->script('attachments', false);
echo $this->Html->script('/Training/js/courses', false);
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);

?>

<?php echo $this->element('breadcrumb'); ?>
<style>
a.custom_icon_plus {
background: url("<?php echo $this->Html->url('/'); ?>/img/icons/add.png") no-repeat scroll 0 0 transparent;
color: #007CBE;
font-size: 11px;
padding: 3px 0 5px 20px;
}
</style>

<div id="training_course" class="content_wrapper edit add">
	<h1>
		<span><?php echo __($subheader); ?></span>
		<?php
            echo $this->Html->link(__('Back'), array('action' => 'course'), array('class' => 'divider'));
        
		?>
	</h1>
	
	<?php
	echo $this->Form->create($modelName, array(
		'url' => array('controller' => 'Training', 'action' => 'courseAdd', 'plugin'=>'Training'),
		'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>
	
	<span id="controller" class="none"><?php echo $this->params['controller']; ?></span>
	<?php if(!empty($this->data[$modelName]['id'])){ echo $this->Form->input('id', array('type'=> 'hidden')); } ?>
	<?php if(!empty($this->data[$modelName]['training_status_id'])){ echo $this->Form->input('training_status_id', array('type'=> 'hidden')); } ?>
	<div class="row">
        <div class="label"><?php echo __('Course Code'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('code'); 
		?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Course Title'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('title'); 
		?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Course Description'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('description', array('type'=>'textarea')); 
		?>
        </div>
    </div>
 	<div class="row">
        <div class="label"><?php echo __('Goal / Objectives'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('objective', array('type'=>'textarea')); 
		?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Category / Field of Study'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('training_field_study_id', array('options'=>$trainingFieldStudyOptions)); 
		?>
        </div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Course Type'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('training_course_type_id', array('options'=>$trainingCourseTypeOptions)); 
		?>
        </div>
    </div>
	 <div class="row row_target_population" style="min-height:45px;">
		<div class="label"><?php echo __('Target Population'); ?></div>
		<div class="value">
		<div class="table target_population" style="width:240px;" url="Training/ajax_find_target_population/">
			<div class="delete-target-population" name="data[DeleteTargetPopulation][{index}][id]"></div>
			<div class="table_body">
			<?php if(isset($this->request->data['TrainingCourseTargetPopulation']) && !empty($this->request->data['TrainingCourseTargetPopulation'])){ ?>
				<?php 
				$i = 0;   
				foreach($this->request->data['TrainingCourseTargetPopulation'] as $val){?>
				<?php if(!empty($val['position_title_table'])){ ?>
				<div class="table_row " row-id="<?php echo $i;?>">
					<div class="table_cell cell_description">
						<div class="input_wrapper">
					 	<div class="position-title-name-<?php echo $i;?>">
							<?php 
							if($val['position_title_table']=='teacher_position_titles'){
								echo $teacherPositionTitles[$val['position_title_id']];
							}else{
								echo $staffPositionTitles[$val['position_title_id']];
							}?>
						</div>		
						<?php echo $this->Form->hidden('TrainingCourseTargetPopulation.' . $i . '.position_title_id', array('class' => 'position-title-id-'.$i . 
						' validate-target-population', 'value'=>$val['position_title_id'])); ?>
						<?php if(isset($val['id'])){ ?>
						<?php echo $this->Form->hidden('TrainingCourseTargetPopulation.' . $i . '.id', array('value'=>$val['id'], 
						'class' => 'control-id')); ?>
						<?php } ?>
						<?php echo $this->Form->hidden('TrainingCourseTargetPopulation.' . $i . '.position_title_table', array('class' => 'position-title-table-'.$i, 
						'value'=>$val['position_title_table'])); ?>
						<?php echo $this->Form->hidden('TrainingCourseTargetPopulation.' . $i . '.position_title_validate', array('class' => 'position-title-validate-'. 
							' validate-target-population', 
						'value'=>$val['position_title_table'] . '_' . $val['position_title_id'])); ?>
					
						</div>
				    </div>
				 
					<div class="table_cell cell_delete">
				    	<span class="icon_delete" title="Delete" onclick="objTrainingCourses.deleteTargetPopulation(this)"></span>
				    </div>
				</div>
				<?php } ?>
			<?php 
				$i++;
			} ?>
			<?php } ?>
			</div>
		</div>
		<div class="row"><a class="void custom_icon_plus" onclick="objTrainingCourses.addTargetPopulation(this)" url="Training/ajax_add_target_population"  href="javascript: void(0)"><?php echo __('Add Target Population');?></a></div>
	
		</div>
	</div>
    <div class="row">
        <div class="label"><?php echo __('Credit Hours'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('credit_hours', array('options'=>$trainingCreditHourOptions)); 
		?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Duration'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('duration'); 
		?>
        </div>
    </div>
     <div class="row">
        <div class="label"><?php echo __('Mode of Delivery'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('training_mode_delivery_id', array('options'=>$trainingModeDeliveryOptions)); 
		?>
        </div>
    </div>
    <div class="row row_provider" style="min-height:45px;">
        <div class="label"><?php echo __('Training Provider'); ?></div>
        <div class="value">
		<div class="table provider" style="width:240px;" url="Training/ajax_find_prerequisite/">
			<div class="delete-provider" name="data[DeleteProvider][{index}][id]"></div>
			<div class="table_body">
			<?php if(isset($this->request->data['TrainingCourseProvider']) && !empty($this->request->data['TrainingCourseProvider'])){ ?>
				<?php 
				$i = 0;   
				foreach($this->request->data['TrainingCourseProvider'] as $val){ ?>
				<div class="table_row " row-id="<?php echo $i;?>">
					<div class="table_cell cell_description">
						<div class="input_wrapper">	
						<?php echo $this->Form->input('TrainingCourseProvider.' . $i . '.training_provider_id', array(
							'options'=>$trainingProviderOptions,'value'=>$val['training_provider_id'], 'label'=>false, 'div'=>false, 'class'=>false)); ?>
						<?php if(isset($val['id'])){ ?>
						<?php echo $this->Form->hidden('TrainingCourseProvider.' . $i . '.id', array('value'=>$val['id'], 
						'class' => 'control-id')); ?>
						<?php } ?>
						</div>
				    </div>
				 
					<div class="table_cell cell_delete">
				    	<span class="icon_delete" title="Delete" onclick="objTrainingCourses.deleteProvider(this)"></span>
				    </div>
				</div>
			<?php 
				$i++;
			} ?>
			<?php } ?>
			</div>
		</div>
		<div class="row"><a class="void custom_icon_plus" onclick="objTrainingCourses.addProvider(this)" url="Training/ajax_add_provider"  href="javascript: void(0)"><?php echo __('Add Provider');?></a></div>
		</div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Training Requirement'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('training_requirement_id', array('options'=>$trainingRequirementOptions)); 
		?>
        </div>
    </div>
   	<div class="row">
        <div class="label"><?php echo __('Training Level'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('training_level_id', array('options'=>$trainingLevelOptions)); 
		?>
        </div>
    </div>
    <div class="row row_prerequisite" style="min-height:45px;">
		<div class="label"><?php echo __('Prerequisite'); ?></div>
		<div class="value">
		<div class="table prerequisite" style="width:240px;" url="Training/ajax_find_prerequisite/">
			<div class="delete-prerequisite" name="data[DeletePrerequisite][{index}][id]"></div>
			<div class="table_body">
			<?php if(isset($this->request->data['TrainingCoursePrerequisite']) && !empty($this->request->data['TrainingCoursePrerequisite'])){ ?>
				<?php 
				$i = 0;   
				foreach($this->request->data['TrainingCoursePrerequisite'] as $val){ ?>
				<?php if(!empty($val['code'])){ ?>
				<div class="table_row " row-id="<?php echo $i;?>">
					<div class="table_cell cell_description">
						<div class="input_wrapper">
					 	<div class="training-course-title-<?php echo $i;?>">
							<?php echo $val['code'] . ' - ' . $val['title'];?>
						</div>		
						<?php echo $this->Form->hidden('TrainingCoursePrerequisite.' . $i . '.training_prerequisite_course_id', array('class' => 'training-course-id-'.$i . ' validate-prerequisite', 'value'=>$val['training_prerequisite_course_id'])); ?>
						<?php echo $this->Form->hidden('TrainingCoursePrerequisite.' . $i . '.code', array('value'=>$val['code'])); ?>
						<?php echo $this->Form->hidden('TrainingCoursePrerequisite.' . $i . '.title', array('value'=>$val['title'])); ?>

						<?php if(isset($val['id'])){ ?>
						<?php echo $this->Form->hidden('TrainingCoursePrerequisite.' . $i . '.id', array('value'=>$val['id'], 
						'class' => 'control-id')); ?>
						<?php } ?>
						</div>
				    </div>
				 
					<div class="table_cell cell_delete">
				    	<span class="icon_delete" title="Delete" onclick="objTrainingCourses.deletePrerequisite(this)"></span>
				    </div>
				</div>
				<?php } ?>
			<?php 
				$i++;
			} ?>
			<?php } ?>
			</div>
		</div>
		<div class="row"><a class="void custom_icon_plus" onclick="objTrainingCourses.addPrerequisite(this)" url="Training/ajax_add_prerequisite"  href="javascript: void(0)"><?php echo __('Add Prerequisite');?></a></div>
		</div>
	</div>
    <div class="row">
        <div class="label"><?php echo __('Pass Result'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('pass_result'); 
		?>
        </div>
    </div>
    <span id="controller" class="none"><?php echo $this->params['controller']; ?></span>

	<div class="row">
		<div class="label"><?php echo __('Attachments'); ?></div>
		<div class="value">
		<div class="table file_upload" style="width:240px;">
			<div class="table_body">
				<?php
				if(isset($attachments) && !empty($attachments)){
				foreach($attachments as $index => $value){
					$obj = $value[$_model];
					$fileext = strtolower(pathinfo($obj['file_name'], PATHINFO_EXTENSION));
					$fieldName = sprintf('data[%s][%s][%%s]', $_model, $index);
				?>
				
				<div class="table_row" file-id="<?php echo $obj['id']; ?>">
					<?php echo $this->Utility->getIdInput($this->Form, $fieldName, $obj['id']); ?>
					<?php echo $this->Form->input('name', array('type'=>'hidden','name' => sprintf($fieldName, 'name'), 'value' => $obj['name'])); ?>
					<?php
					echo $this->Form->input('description', array('type'=>'hidden',
						'name' => sprintf($fieldName, 'description'),
						'value' => $obj['description']
					));
					?>
					<div class="table_cell center"><?php echo $this->Html->link($obj['name'], array('action' => 'attachmentsCourseDownload', $obj['id']));?></div>
					<?php if($_delete) { ?>
					<div class="table_cell cell_delete">
						<span class="icon_delete" title="<?php echo __("Delete"); ?>" onClick="objTrainingCourses.deleteFile(<?php echo $obj['id']; ?>)"></span>
					</div>
					<?php } ?>
				</div>
				<?php 	
					}
				}	
				?>
			</div>
		</div>
		 <div style="color:#666666;font-size:10px;"><?php echo __('Note: Max upload file size is 2MB.'); ?></div> 
		<?php echo $this->Utility->getAddRow('Attachment'); ?>
		</div>
	</div>
	<div class="controls view_controls">
		<?php if(!isset($this->request->data['TrainingCourse']['training_status_id']) || $this->request->data['TrainingCourse']['training_status_id']==1){ ?>
		<input type="submit" value="<?php echo __("Save"); ?>" name='save' class="btn_save btn_right" onclick="js:if(objTrainingCourses.errorFlag() && Config.checkValidate()){ return true; }else{ return false; }"/>
		<input type="submit" value="<?php echo __("Submit for Approval"); ?>" name='submitForApproval' class="btn_save btn_right" onclick="js:if(objTrainingCourses.errorFlag() && Config.checkValidate()){ return true; }else{ return false; }"/>
		<?php } ?>
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'course'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>