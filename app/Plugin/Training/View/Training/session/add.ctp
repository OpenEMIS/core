<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Training/js/sessions', false);
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');
if($_edit) {
	echo $this->Html->link(__('Back'), array('action' => 'session'), array('class' => 'divider', 'id'=>'back'));
}
$this->end();
$this->start('contentBody');
?>
<?php

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'],'action' => 'sessionAdd'), 'file');
echo $this->Form->create($model, $formOptions);
?>

<?php if(!empty($this->data[$modelName]['id'])){ echo $this->Form->input('id', array('type'=> 'hidden')); } ?>
<?php if(!empty($this->data[$modelName]['training_status_id'])){ echo $this->Form->input('training_status_id', array('type'=> 'hidden')); } ?>

<?php  echo $this->Form->input('provider', array('type'=> 'hidden', 'class'=>'provider', 'default'=>$provider)); ?>
<?php  echo $this->Form->input('sessionEditable', array('type'=> 'hidden', 'default'=>$this->request->data['TrainingSession']['sessionEditable'])); ?>
<?php 
$readonly = array();;
if($this->request->data['TrainingSession']['sessionEditable']=='2'){
	$readonly['readonly'] = 'readonly';
}

$startDate = date('Y-m-d');
$endDate = date('Y-m-d');
if(isset($this->data[$model]['start_date'])){
	$startDate = $this->data[$model]['start_date'];
}
if(isset($this->data[$model]['end_date'])){
	$endDate = $this->data[$model]['end_date'];
}
?>
	<?php 
        echo $this->Form->input('training_course_id', array(
            'options' => $trainingCourseOptions,
            'label' => array('text'=>__('Course'), 'class'=>'col-md-3 control-label'),
            'empty' => __('--Select--'),
            $readonly,
            'class' => 'form-control training_course',
            'url' => sprintf('%s/%s', $this->params['controller'], $this->params['action']),
            'onchange' => 'objTrainingSessions.getDetailsAfterChangeCourse(this);objTrainingSessions.clearTrainee();'
        ));
		echo $this->Form->input('training_provider_id', array(
			'options' => array(),
			'label'=>array('text'=>__('Provider'), 'class'=>'col-md-3 control-label'),
			'onchange' => 'objTrainingSessions.selectProvider(this)',
			 $readonly,
			'class'=>'form-control training_provider')); 
	 
		if($this->request->data['TrainingSession']['sessionEditable']!='2'){
			echo $this->FormUtility->datepicker('start_date', array('id' => 'StartDate', 'data-date' => $startDate));
			echo $this->FormUtility->datepicker('end_date', array('id' => 'EndDate', 'data-date' => $endDate));
		}else{
			echo $this->Form->input('start_date', array('type'=>'text', $readonly));
			echo $this->Form->input('end_date', array('type'=>'text', $readonly));
		}
		echo $this->Form->input('area_id', array('options'=>$areaOptions, 'empty'=>__('--Select--'), $readonly));
	 	echo $this->Form->input('location', array('label'=>array('text'=>__('Location'), 'class'=>'col-md-3 control-label'), 'id' => 'searchLocation', 'class'=>'form-control location', 'url'=>'Training/ajax_find_location/', 'placeholder' => __('Location'), $readonly));
    	echo $this->Form->input('comments', array('label'=>array('text'=>__('Comments'), 'class'=>'col-md-3 control-label'),'type'=>'textarea', $readonly));
    ?>

	 <div class="row form-group" style="min-height:45px;">
		<label class="col-md-3 control-label"><?php echo __('Trainer'); ?></label>
		<div class="col-md-5">
		<div class="table trainer" url="Training/ajax_find_trainer/">
			<div class="delete-trainer" name="data[DeleteTrainer][{index}][id]"></div>
			<table class="table_body table-striped table-hover table-bordered">
				<?php  
				if(isset($this->request->data['TrainingSessionTrainer']) && !empty($this->request->data['TrainingSessionTrainer'])){ ?>
					<?php   
					$i = 0; 
					foreach($this->request->data['TrainingSessionTrainer'] as $key=>$val){?>
					<?php if((!empty($val['ref_trainer_id']) && $val['ref_trainer_table']=='Staff') || 
					(!empty($val['ref_trainer_name']) && $val['ref_trainer_table']!='Staff')){ ?>
					<tr class="table_row " row-id="<?php echo $i;?>">
						<td class="table_cell cell_description" style="width:55%">
							<div class="input_wrapper">
						 	<div class="trainer-name-<?php echo $i;?>">
								<?php echo $val['ref_trainer_name'];?>
							</div>
							<?php if(isset($val['id'])){ ?>
							<?php echo $this->Form->hidden('TrainingSessionTrainer.' . $i . '.id', array('value'=>$val['id'], 
							'class' => 'control-id')); ?>
							<?php } ?>
							<?php echo $this->Form->hidden('TrainingSessionTrainer.' . $i . '.ref_trainer_id', array('class' => 'trainer-id-'.$i,
								'value'=>$val['ref_trainer_id'])); ?>
								<?php echo $this->Form->hidden('TrainingSessionTrainer.' . $i . '.ref_trainer_name', array('value'=>$val['ref_trainer_name'])); ?>
								<?php echo $this->Form->hidden('TrainingSessionTrainer.' . $i . '.ref_trainer_table', array('value'=>$val['ref_trainer_table'])); ?>
								<?php if($val['ref_trainer_table']=='Staff'){ ?>
									<?php echo $this->Form->hidden('TrainingSessionTrainer.' . $i . '.trainer_validate', array('class' => 'trainer-validate-'.$i . ' validate-trainer', 'value'=>$val['ref_trainer_table'].'_'.$val['ref_trainer_id'])); ?>
								<?php }else{ ?>
									<?php echo $this->Form->hidden('TrainingSessionTrainer.' . $i . '.trainer_validate', array('class' => 'trainer-validate-'.$i . ' validate-trainer', 'value'=>$val['ref_trainer_table'].'_'.$val['ref_trainer_name'])); ?>
								<?php } ?>
							</div>
					    </td>
					 	<td class="table_cell cell_description">
					 		<?php if($val['ref_trainer_table']=='Staff'){
				 			 	echo '<div class="input_wrapper">'.__('Internal').'</div>';
					 		}else{
					 			echo '<div class="input_wrapper">'.__('External').'</div>';
					 		}
					 		?>
					 	</td>
						<td class="table_cell cell_delete">
							<?php if($this->request->data['TrainingSession']['sessionEditable']!='2'){ ?>
					    	<span class="icon_delete" title="Delete" onclick="objTrainingSessions.deleteTrainer(this)"></span>
					    	<?php } ?>
					    </td>
					</tr>
					<?php } ?>
			<?php 
				$i++;
			} ?>
			<?php } ?>
		</table>
	  	<?php if($this->request->data['TrainingSession']['sessionEditable']!='2'){ ?>
		<div class="row" style="padding-top:5px;">
			<div class="col-md-6" style="padding-left:10px">
			<?php echo $this->Form->input('trainer_type', array(
				'options' => $trainerTypeOptions,
				'class' => 'trainer_type form-control',
				'label' => false,
				'between' => false,
				'after'=>false,
				'div' => false
			));?>
			</div>
			<div class="col-md-6" style="min-height:20px;padding-top:5px;padding-left:0px;">
				<a class="void icon_plus" onclick="objTrainingSessions.addTrainer(this)" url="Training/ajax_add_trainer"  href="javascript: void(0)"><?php echo __('Add Trainer');?></a>
			</div>
		</div>
		<?php } ?>
	</div>

		
	</div>
	</div>
    <?php if($this->request->data['TrainingSession']['sessionEditable']!='0'){ ?>
	 <div class="row form-group" style="min-height:45px;">
		<label class="col-md-3 control-label"><?php echo __('Trainees'); ?></label>
		<div class="col-md-5">
		<div class="table trainee"  url="Training/ajax_find_trainee/">
			<div class="delete-trainee" name="data[DeleteTrainee][{index}][id]"></div>
			<table class="table_body table-striped table-hover table-bordered">
				<?php 
				if(isset($this->request->data['TrainingSessionTrainee']) && !empty($this->request->data['TrainingSessionTrainee'])){ ?>
					<?php 
					$i = 0;  
					foreach($this->request->data['TrainingSessionTrainee'] as $key=>$val){?>
					<?php if(!empty($val['staff_id'])){ ?>
					<tr class="table_row " row-id="<?php echo $i;?>">
						<td class="table_cell cell_description" style="width:90%">
							<div class="input_wrapper">
						 	<div class="training-course-title-<?php echo $i;?>">
								<?php echo $val['first_name'] . ', ' . $val['last_name'];?>
							</div>
							<?php if(isset($val['id'])){ ?>
							<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.id', array('value'=>$val['id'], 
							'class' => 'control-id')); ?>
							<?php } ?>
							<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.staff_id', array('class' => 'trainee-id-'.$i,
								'value'=>$val['staff_id'])); ?>
								<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.first_name', array('value'=>$val['first_name'])); ?>
								<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.last_name', array('value'=>$val['last_name'])); ?>
								<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.identification_validate', array('class' => 'trainee-validate-'.$i . ' validate-trainee', 'value'=>$val['staff_id'])); ?>
							</div>
					    </td>
					 
						<td class="table_cell cell_delete">
					    	<span class="icon_delete" title="Delete" onclick="objTrainingSessions.deleteTrainee(this)"></span>
					    </td>
					</tr>
					<?php } ?>
			<?php 
				$i++;
			} ?>
			<?php } ?>
		</table>
	</div>

	<div class="row"><a class="void icon_plus" onclick="objTrainingSessions.addTrainee(this)" url="Training/ajax_add_trainee"  href="javascript: void(0)"><?php echo __('Add Trainee');?></a></div>

	</div>
	<?php } ?>
</div>
<div class="controls view_controls">
	<?php if($this->request->data['TrainingSession']['sessionEditable']!='0'){ ?>
	<input type="submit" value="<?php echo __("Save"); ?>" name='save' class="btn_save btn_right" onclick="js:if(objTrainingSessions.errorFlag() && Config.checkValidate()){ return true; }else{ return false; }"/>
	<?php if($this->request->data['TrainingSession']['sessionEditable']=='1'){ ?>
	<input type="submit" value="<?php echo __("Submit for Approval"); ?>" name='submitForApproval' class="btn_save btn_right" onclick="js:if(objTrainingSessions.errorFlag() && Config.checkValidate()){ return true; }else{ return false; }"/>
	<?php } ?>
	<?php } ?>
	<?php echo $this->Html->link(__('Cancel'), array('action' => 'session'), array('class' => 'btn_cancel btn_left')); ?>
</div>
	
<?php echo $this->Form->end(); ?>

<?php $this->end(); ?>	
