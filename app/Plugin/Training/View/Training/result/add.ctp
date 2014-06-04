<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'result'), array('class' => 'divider'));
if($_edit) {
	echo $this->Html->link(__('Download Template'), array('action' => 'resultDownloadTemplate'), array('class' => 'divider'));
  	echo $this->Html->link(__('Upload Results'), array('action' => 'resultUpload'), array('class' => 'divider'));
}
$this->end();
$this->start('contentBody');
?>
<?php
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'resultEdit'), 'file');
echo $this->Form->create($model, $formOptions);
?>

<?php if(!empty($this->data[$modelName]['id'])){ echo $this->Form->input('id', array('type'=> 'hidden')); } ?>
<?php if(!empty($this->data[$modelName]['training_status_id'])){ echo $this->Form->input('training_status_id', array('type'=> 'hidden')); } ?>

<?php 
	echo $this->Form->input('TrainingCourse.training_course_id', array('label'=>array('text'=>__('Course'), 'class'=>'col-md-3 control-label'), 'options' => $trainingCourseOptions, 'disabled' => 'disabled', 'default'=>$this->request->data['TrainingCourse']['id'])); 
	echo $this->Form->input('TrainingSession.training_provider_id', array('label'=>array('text'=>__('Provider'), 'class'=>'col-md-3 control-label'), 'options' => $trainingProviderOptions, 'disabled' => 'disabled', 'default'=>$this->request->data['TrainingSession']['training_provider_id']));
	echo $this->FormUtility->datepicker('start_date', array('id' => 'StartDate', 'readonly' => 'readonly', 'data-date' => $this->request->data['TrainingSession']['start_date']));
	echo $this->FormUtility->datepicker('end_date', array('id' => 'EndDate', 'readonly' => 'readonly', 'data-date' => $this->request->data['TrainingSession']['end_date']));
	echo $this->Form->input('TrainingSession.location', array('label'=>array('text'=>__('Location'), 'class'=>'col-md-3 control-label'), 'id' => 'searchLocation', 'disabled' => 'disabled', 'class'=>'form-control location', 'placeholder' => __('Location'), 'value'=>$this->request->data['TrainingSession']['location']));
	echo $this->Form->input('TrainingSession.trainer', array('label'=>array('text'=>__('Trainer'), 'class'=>'col-md-3 control-label'), 'id' => 'searchTrainer', 'disabled' => 'disabled', 'class'=>'form-control trainer', 'placeholder' => __('Identification No, First Name or Last Name'), 'value'=>$this->request->data['TrainingSession']['trainer']));

	?>
 <div class="form-group">
	<label class="col-md-3 control-label"><?php echo __('Trainees'); ?></label>
	<div class="col-md-6">
	<div class="table trainee"  url="Training/ajax_find_trainee/">
		<div class="delete-trainee" name="data[DeleteTrainee][{index}][id]"></div>
		<table class="table_body" style="display:table;">
	 	<thead class="table_head">
        	<tr>
	       		<td class="table_cell"><?php echo __('Name'); ?></td>
	            <td class="table_cell"><?php echo __('Result'); ?></td>
	            <td class="table_cell"><?php echo __('Completed'); ?></td>
	        </tr>
        </thead>
		<?php 
		if(isset($this->request->data['TrainingSessionTrainee']) && !empty($this->request->data['TrainingSessionTrainee'])){ ?>
			<?php 
			$i = 0;  
			foreach($this->request->data['TrainingSessionTrainee'] as $key=>$val){  ?>
			<tr class="table_row " row-id="<?php echo $i;?>">
				<td class="table_cell cell_description">
					<div class="input_wrapper">
				 	<div class="training-course-title-<?php echo $i;?>">
						<?php echo $val['first_name'] . ', ' . $val['last_name'];?>
					</div>
					<?php if(isset($val['id'])){ ?>
					<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.id', array('value'=>$val['id'], 
					'class' => 'control-id')); ?>
					<?php } ?>
					<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.staff_id', array('value'=>$val['staff_id'])); ?>
						<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.first_name', array('value'=>$val['first_name'])); ?>
						<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.last_name', array('value'=>$val['last_name'])); ?>
					</div>
			    </td>
			 
				<td class="table_cell">
			    	<?php 
					echo $this->Form->input('TrainingSessionTrainee.'. $i .'.result', array('label'=>false,'value'=>$val['result'], 'style'=>'width:50px;')); ?>
			    </td>
			    <td class="table_cell" style="padding:5px;">
			    	<?php 
					echo $this->Form->input('TrainingSessionTrainee.'. $i .'.pass', array('label'=>false,'default'=>$val['pass'], 'empty'=>array('-1'=>''),
					 'options' => array(1=>__('Passed'), 0 => __('Failed')), 'style'=>'width:70px;padding:0')); ?>
			    </td>
			</tr>
		<?php 
			$i++;
		} ?>
		<?php } ?>
		</table>
	</div>
	</div>
</div>
<div class="controls view_controls">
	<?php if(!isset($this->request->data['TrainingSessionResult']['training_status_id'])|| $this->request->data['TrainingSessionResult']['training_status_id']==1){ ?>
	<input type="submit" value="<?php echo __("Save"); ?>" name='save' class="btn_save btn_right" onclick="return Config.checkValidate();"/>
	<input type="submit" value="<?php echo __("Submit for Approval"); ?>" name='submitForApproval' class="btn_save btn_right" onclick="return Config.checkValidate();"/>
	<?php } ?>
	<?php echo $this->Html->link(__('Cancel'), array('action' => 'result'), array('class' => 'btn_cancel btn_left')); ?>
</div>
<?php echo $this->Form->end(); ?>

<?php $this->end(); ?>	
