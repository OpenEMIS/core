<?php
echo $this->Html->css('../js/plugins/fileupload/bootstrap-fileupload', array('inline' => false));
echo $this->Html->script('plugins/fileupload/bootstrap-fileupload', false);
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Training/js/courses', false);
echo $this->Html->script('jquery-ui.min', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');

if(!empty($this->data[$modelName]['id'])){
	echo $this->Html->link(__('Back'), array('action' => 'courseView', $this->data[$modelName]['id']), array('class' => 'divider'));
}else{
	echo $this->Html->link(__('Back'), array('action' => 'course'), array('class' => 'divider'));
}

$this->end();
$this->start('contentBody');
?>

<style>
a.custom_icon_plus {
background: url("<?php echo $this->Html->url('/'); ?>/img/icons/add.png") no-repeat scroll 0 0 transparent;
color: #007CBE;
font-size: 11px;
padding: 3px 0 5px 20px;
}
</style>

<?php
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'courseAdd'), 'file');
echo $this->Form->create($model, array_merge($formOptions, array('deleteUrl'=>$this->params['controller']."/attachmentsCourseDelete/")));
?>
<span id="controller" class="none"><?php echo $this->params['controller']; ?></span>

<?php if(!empty($this->data[$modelName]['id'])){ echo $this->Form->input('id', array('type'=> 'hidden')); } ?>
<?php if(!empty($this->data[$modelName]['training_status_id'])){ echo $this->Form->input('training_status_id', array('type'=> 'hidden')); } ?>

	<?php 
		echo $this->Form->input('code', array('label'=>array('text'=>__('Course Code'), 'class'=>'col-md-3 control-label'))); 
		echo $this->Form->input('title', array('label'=>array('text'=>__('Course Title'), 'class'=>'col-md-3 control-label'))); 
		echo $this->Form->input('description', array('label'=>array('text'=>__('Course Description'), 'class'=>'col-md-3 control-label'), 'type'=>'textarea')); 
		echo $this->Form->input('objective', array('label'=>array('text'=>__('Goal / objective'), 'class'=>'col-md-3 control-label'), 'type'=>'textarea')); 
		echo $this->Form->input('training_field_study_id', array('label'=>array('text'=>__('Category / Field of Study'), 'class'=>'col-md-3 control-label'), 'options'=>$trainingFieldStudyOptions)); 
		echo $this->Form->input('training_course_type_id', array('label'=>array('text'=>__('Course Type'), 'class'=>'col-md-3 control-label'), 'options'=>$trainingCourseTypeOptions)); 
	?>
 <div class="row form-group row_target_population" style="min-height:45px;">
	<label class="col-md-3 control-label"><?php echo __('Target Population'); ?></label>
	<div class="col-md-4">
	<div class="table target_population" url="Training/ajax_find_target_population/">
		<div class="delete-target-population" name="data[DeleteTargetPopulation][{index}][id]"></div>
		<table class="table table-striped table-hover table-bordered table_body">
		<tbody>
		<?php if(isset($this->request->data['TrainingCourseTargetPopulation']) && !empty($this->request->data['TrainingCourseTargetPopulation'])){ ?>
			<?php 
			$i = 0;   
			foreach($this->request->data['TrainingCourseTargetPopulation'] as $val){?>
			<?php if(!empty($val['staff_position_title_id'])){ ?>
			<tr class="table_row " row-id="<?php echo $i;?>">
					<td class="table_cell cell_description" style="width:90%">
						<div class="input_wrapper">
					 	<div class="position-title-name-<?php echo $i;?>">
							<?php echo $staffPositionTitles[$val['staff_position_title_id']];?>
						</div>		
						<?php echo $this->Form->hidden('TrainingCourseTargetPopulation.' . $i . '.staff_position_title_id', array('class' => 'position-title-id-'.$i, 'value'=>$val['staff_position_title_id'])); ?>
						<?php if(isset($val['id'])){ ?>
						<?php echo $this->Form->hidden('TrainingCourseTargetPopulation.' . $i . '.id', array('value'=>$val['id'], 
						'class' => 'control-id')); ?>
						<?php } ?>
						<?php echo $this->Form->hidden('TrainingCourseTargetPopulation.' . $i . '.position_title_validate', array('class' => 'position-title-validate-'. $i .
							' validate-target-population', 
						'value'=> $val['staff_position_title_id'])); ?>
					
						</div>
				    </td>
				 
					<td class="table_cell cell_delete">
				    	<span class="icon_delete" title="Delete" onclick="objTrainingCourses.deleteTargetPopulation(this)"></span>
				    </td>
			</tr>
			<?php } ?>
		<?php 
			$i++;
		} ?>
		<?php } ?>
	</tbody>
		</table>
	</div>
	<div class="row"><a class="void custom_icon_plus" onclick="objTrainingCourses.addTargetPopulation(this)" url="Training/ajax_add_target_population"  href="javascript: void(0)"><?php echo __('Add Target Population');?></a></div>

	</div>
</div>

<?php 
	echo $this->Form->input('credit_hours', array('label'=>array('text'=>__('Credits'), 'class'=>'col-md-3 control-label'), 'options'=>$trainingCreditHourOptions)); 
	echo $this->Form->input('duration', array('label'=>array('text'=>__('Hours'), 'class'=>'col-md-3 control-label'),'min'=>'0', 'step'=>'1', 'pattern'=>'\d+')); 
	echo $this->Form->input('training_mode_delivery_id', array('label'=>array('text'=>__('Mode of Delivery'), 'class'=>'col-md-3 control-label'), 'options'=>$trainingModeDeliveryOptions)); 
?>
<div class="row form-group row_provider" style="min-height:45px;">
    <label class="col-md-3 control-label"><?php echo __('Training Provider'); ?></label>
    <div class="col-md-4">
	<div class="table provider" style="width:247px;">
		<div class="delete-provider" name="data[DeleteProvider][{index}][id]"></div>
		<table class="table table-striped table-hover table-bordered table_body">
		<tbody>
		<?php if(isset($this->request->data['TrainingCourseProvider']) && !empty($this->request->data['TrainingCourseProvider'])){ ?>
			<?php 
			$i = 0;   
			foreach($this->request->data['TrainingCourseProvider'] as $val){ ?>
			<tr class="table_row " row-id="<?php echo $i;?>">
				<td class="table_cell cell_description" style="width:90%">
					<?php echo $this->Form->input('TrainingCourseProvider.' . $i . '.training_provider_id', array(
						'options'=>$trainingProviderOptions,'value'=>$val['training_provider_id'], 'class'=>'form-control validate-provider', 'label'=>false, 'between'=>false, 'div'=>false, 'onchange'=>'objTrainingCourses.validateProvider();')); ?>
					<?php if(isset($val['id'])){ ?>
					<?php echo $this->Form->hidden('TrainingCourseProvider.' . $i . '.id', array('value'=>$val['id'], 
					'class' => 'control-id')); ?>
					<?php } ?>
			    </td>
			 
				<td class="table_cell cell_delete">
			    	<span class="icon_delete" title="Delete" onclick="objTrainingCourses.deleteProvider(this)"></span>
			    </td>
			</tr>
		<?php 
			$i++;
		} ?>
		<?php } ?>
		</tbody>
		</table>
	</div>
	<div class="row"><a class="void custom_icon_plus" onclick="objTrainingCourses.addProvider(this)" url="Training/ajax_add_provider"  href="javascript: void(0)"><?php echo __('Add Provider');?></a></div>
	</div>
</div>

<?php 
	echo $this->Form->input('training_requirement_id', array('label'=>array('text'=>__('Training Requirement'), 'class'=>'col-md-3 control-label'), 'options'=>$trainingRequirementOptions)); 
	echo $this->Form->input('training_level_id', array('label'=>array('text'=>__('Training Level'), 'class'=>'col-md-3 control-label'), 'options'=>$trainingLevelOptions)); 
?>
<div class="row form-group row_course_prerequisite" style="min-height:45px;">
	<label class="col-md-3 control-label"><?php echo __('Course Prerequisite'); ?></label>
	<div class="col-md-4">
	<div class="table course_prerequisite" url="Training/ajax_find_course_prerequisite/">
		<div class="delete-course_prerequisite" name="data[DeleteCoursePrerequisite][{index}][id]"></div>
		<table class="table table-striped table-hover table-bordered table_body">
		<tbody>
		<?php if(isset($this->request->data['TrainingCoursePrerequisite']) && !empty($this->request->data['TrainingCoursePrerequisite'])){ ?>
			<?php 
			$i = 0;   
			foreach($this->request->data['TrainingCoursePrerequisite'] as $val){ ?>
			<?php if(!empty($val['code'])){ ?>
			<tr class="table_row " row-id="<?php echo $i;?>">
				<td class="table_cell cell_description" style="width:90%">
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
			    </td>
			 
				<td class="table_cell cell_delete">
			    	<span class="icon_delete" title="Delete" onclick="objTrainingCourses.deleteCoursePrerequisite(this)"></span>
			    </td>
			</tr>
			<?php } ?>
		<?php 
			$i++;
		} ?>
		<?php } ?>
		</tbody>
		</table>
	</div>
	<div class="row"><a class="void custom_icon_plus" onclick="objTrainingCourses.addCoursePrerequisite(this)" url="Training/ajax_add_course_prerequisite"  href="javascript: void(0)"><?php echo __('Add Course Prerequisite');?></a></div>
	</div>
</div>
<div class="row form-group row_specialisation" style="min-height:45px;">
	<label class="col-md-3 control-label"><?php echo __('Specialisation'); ?></label>
	<div class="col-md-4">
	<div class="table specialisation">
		<div class="delete-specialisation" name="data[DeleteSpecialisation][{index}][id]"></div>
		<table class="table table-striped table-hover table-bordered table_body">
		<tbody>
		<?php if(isset($this->request->data['TrainingCourseSpecialisation']) && !empty($this->request->data['TrainingCourseSpecialisation'])){ ?>
			<?php 
			$i = 0;   
			foreach($this->request->data['TrainingCourseSpecialisation'] as $val){ ?>
			<?php if(!empty($val['qualification_specialisation_id'])){ ?>
			<tr class="table_row " row-id="<?php echo $i;?>">
				<td class="table_cell cell_description" style="width:90%">
					<div class="input_wrapper">
			 		<div class="training-course-specialisation-<?php echo $i;?>">
						<?php echo $qualificationSpecialisationOptions[$val['qualification_specialisation_id']];?>
					</div>		
					<?php echo $this->Form->hidden('TrainingCourseSpecialisation.' . $i . '.qualification_specialisation_id', array('class' => 'training-specialisation-id-'.$i . ' validate-specialisation', 'value'=>$val['qualification_specialisation_id'])); ?>
					<?php if(isset($val['id'])){ ?>
					<?php echo $this->Form->hidden('TrainingCourseSpecialisation.' . $i . '.id', array('value'=>$val['id'], 
					'class' => 'control-id')); ?>
					<?php } ?>
					</div>
			    </td>
				<td class="table_cell cell_delete">
			    	<span class="icon_delete" title="Delete" onclick="objTrainingCourses.deleteSpecialisation(this)"></span>
			    </td>
			</tr>
			<?php } ?>
		<?php 
			$i++;
		} ?>
		<?php } ?>
		</tbody>
		</table>
	</div>
	<div class="row"><a class="void custom_icon_plus" onclick="objTrainingCourses.addSpecialisation(this)" url="Training/ajax_add_specialisation"  href="javascript: void(0)"><?php echo __('Add Specialisation');?></a></div>
	</div>
</div> 

<div class="row form-group row_experience" style="min-height:45px;">
	<label class="col-md-3 control-label"><?php echo __('Experience'); ?></label>
	<div class="col-md-4">
	<div class="table experience">
		<div class="delete-experience" name="data[DeleteExperience][{index}][id]"></div>
		<table class="table table-striped table-hover table-bordered table_body">
		<tbody>
		<?php 
		$i = 0;   
		if(isset($this->request->data['TrainingCourseExperience']) && !empty($this->request->data['TrainingCourseExperience'])){ ?>
			<?php 
			foreach($this->request->data['TrainingCourseExperience'] as $val){ ?>
			<?php if(!empty($val['months'])){ ?>
			<tr class="table_row " row-id="<?php echo $i;?>">
				<td class="table_cell cell_description" style="width:90%">
						<?php 
						$years = floor(intval($val['months'])/12);
						$months = intval($val['months']) - ($years*12);
						?>
						<div class="col-md-6" style="padding:3px;">
							<div class="input_wrapper">
					 		<?php echo $years;?>
					 		<?php echo __('Year(s)');?>
					 		</div>
						</div>
						<div class="col-md-6" style="padding:3px;">
							<div class="input_wrapper">
							<?php echo $months; ?>	
							<?php echo __('Month(s)');?>
							</div>
						</div>
					<?php echo $this->Form->hidden('TrainingCourseExperience.' . $i . '.months', array('class' => 'experience-validate-'.$i . ' validate-experience')); ?>
					<?php if(isset($val['id'])){ ?>
					<?php echo $this->Form->hidden('TrainingCourseExperience.' . $i . '.id', array('value'=>$val['id'], 
					'class' => 'control-id')); ?>
					<?php } ?>
			    </td>
				<td class="table_cell cell_delete">
			    	<span class="icon_delete" title="Delete" onclick="objTrainingCourses.deleteExperience(this)"></span>
			    </td>
			</tr>
			<?php } ?>
		<?php 
			$i++;
		} ?>
		<?php } ?>
		</tbody>
		</table>
	</div>
	<div class="row add_experience<?php echo ($i>=1)? ' hide' : '' ?>"><a class="void custom_icon_plus" onclick="objTrainingCourses.addExperience(this)" url="Training/ajax_add_experience"  href="javascript: void(0)"><?php echo __('Add Experience');?></a></div>
	</div>
</div> 
<div class="row form-group row_result_type" style="min-height:45px;">
	<label class="col-md-3 control-label"><?php echo __('Result Type'); ?></label>
	<div class="col-md-4">
	<div class="table result_type">
		<div class="delete-result-type" name="data[DeleteResultType][{index}][id]"></div>
		<table class="table table-striped table-hover table-bordered table_body">
		<tbody>
		<?php if(isset($this->request->data['TrainingCourseResultType']) && !empty($this->request->data['TrainingCourseResultType'])){ ?>
			<?php 
			$i = 0;   
			foreach($this->request->data['TrainingCourseResultType'] as $val){ ?>
			<?php if(!empty($val['training_result_type_id'])){ ?>
			<tr class="table_row " row-id="<?php echo $i;?>">
				<td class="table_cell cell_description" style="width:90%">
					<div class="input_wrapper">
				 	<div class="training-result-type-<?php echo $i;?>">
						<?php echo $trainingResultTypeOptions[$val['training_result_type_id']];?>
					</div>		
					<?php echo $this->Form->hidden('TrainingCourseResultType.' . $i . '.training_result_type_id', array('class' => 'training-result-type-id-'.$i . ' validate-result-type', 'value'=>$val['training_result_type_id'])); ?>
					<?php if(isset($val['id'])){ ?>
					<?php echo $this->Form->hidden('TrainingCourseResultType.' . $i . '.id', array('value'=>$val['id'], 
					'class' => 'control-id')); ?>
					<?php } ?>
					</div>
			    </td>
			 
				<td class="table_cell cell_delete">
			    	<span class="icon_delete" title="Delete" onclick="objTrainingCourses.deleteResultType(this)"></span>
			    </td>
			</tr>
			<?php } ?>
		<?php 
			$i++;
		} ?>
		<?php } ?>
		</tbody>
		</table>
	</div>
	<div class="row"><a class="void custom_icon_plus" onclick="objTrainingCourses.addResultType(this)" url="Training/ajax_add_result_type"  
		href="javascript: void(0)"><?php echo __('Add Result Type');?></a></div>
	</div>
</div> 
<?php 
$multiple = array('multipleURL' => $this->params['controller']."/trainingCourseAjaxAddField/");
echo $this->Form->hidden('maxFileSize', array('name'=> 'MAX_FILE_SIZE','value'=>(2*1024*1024)));
echo $this->element('templates/file_upload', compact('multiple'));

$tableHeaders = array(__('File(s)'), '&nbsp;');
$tableData = array();
foreach ($attachments as $obj) {
	$row = array();
	$row[] = array($obj['TrainingCourseAttachment']['file_name'], array('file-id' =>$obj['TrainingCourseAttachment']['id']));
	$row[] = '<span class="icon_delete" title="'. $this->Label->get('general.delete').'" onClick="jsForm.deleteFile('.$obj['TrainingCourseAttachment']['id'].')"></span>';
	$tableData[] = $row;
}
echo $this->element('templates/file_list', compact('tableHeaders', 'tableData'));
?>

<div class="controls view_controls">
	<?php if(!isset($this->request->data['TrainingCourse']['training_status_id']) || $this->request->data['TrainingCourse']['training_status_id']==1){ ?>
	<input type="submit" value="<?php echo __("Save"); ?>" name='save' class="btn_save btn_right" onclick="js:if(objTrainingCourses.errorFlag() && Config.checkValidate()){ return true; }else{ return false; }"/>
	<input type="submit" value="<?php echo __("Submit for Approval"); ?>" name='submitForApproval' class="btn_save btn_right" onclick="js:if(objTrainingCourses.errorFlag() && Config.checkValidate()){ return true; }else{ return false; }"/>
	<?php } ?>
	<?php echo $this->Html->link(__('Cancel'), array('action' => 'course'), array('class' => 'btn_cancel btn_left')); ?>
</div>

<?php echo $this->Form->end(); ?>

<?php $this->end(); ?>	