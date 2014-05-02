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

<<<<<<< HEAD
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'],'action' => 'sessionAdd'), 'file');
echo $this->Form->create($model, $formOptions);
=======
<div id="training_session" class="content_wrapper edit add" url="Training/ajax_find_session/" >
	<h1>
		<span><?php echo __($subheader); ?></span>
		<?php
		
            echo $this->Html->link(__('Back'), array('action' => 'session'), array('class' => 'divider'));
		?>
	</h1>
	
	<?php
	echo $this->Form->create($modelName, array(
		'url' => array('controller' => 'Training', 'action' => 'sessionAdd', 'plugin'=>'Training'),
		'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>
>>>>>>> 38e03e699fdf3d4d1f0eab27f2b18acf10efbe9b

?>

<?php if(!empty($this->data[$modelName]['id'])){ echo $this->Form->input('id', array('type'=> 'hidden')); } ?>
<?php if(!empty($this->data[$modelName]['training_status_id'])){ echo $this->Form->input('training_status_id', array('type'=> 'hidden')); } ?>
<?php  echo $this->Form->input('provider', array('type'=> 'hidden', 'class'=>'provider', 'default'=>$provider)); ?>
<?php

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
            'class' => 'form-control training_course',
            'url' => sprintf('%s/%s', $this->params['controller'], $this->params['action']),
            'onchange' => 'objTrainingSessions.getDetailsAfterChangeCourse(this);objTrainingSessions.clearTrainee();'
        ));
		echo $this->Form->input('training_provider_id', array(
			'options' => array(),
			'label'=>array('text'=>__('Provider'), 'class'=>'col-md-3 control-label'),
			'onchange' => 'objTrainingSessions.selectProvider(this)',
			'class'=>'form-control training_provider')); 
	 

		echo $this->FormUtility->datepicker('start_date', array('id' => 'StartDate', 'data-date' => $startDate));
		echo $this->FormUtility->datepicker('end_date', array('id' => 'EndDate', 'data-date' => $endDate));

	 	echo $this->Form->input('location', array('label'=>array('text'=>__('Location'), 'class'=>'col-md-3 control-label'), 'id' => 'searchLocation', 'class'=>'form-control location', 'placeholder' => __('Location')));
    	echo $this->Form->input('comments', array('label'=>array('text'=>__('Comments'), 'class'=>'col-md-3 control-label'),'type'=>'textarea'));
    	echo $this->Form->input('trainer', array('label'=>array('text'=>__('Trainer'), 'class'=>'col-md-3 control-label'), 'id' => 'searchTrainer', 'class'=>'form-control trainer', 'placeholder' => __('Identification No, First Name or Last Name')));
    ?>


	 <div class="row form-group" style="min-height:45px;">
		<label class="col-md-3 control-label"><?php echo __('Trainees'); ?></label>
		<div class="col-md-4">
		<div class="table trainee" style="width:247px;" url="Training/ajax_find_trainee/">
			<div class="delete-trainee" name="data[DeleteTrainee][{index}][id]"></div>
			<div class="table_body" style="display:table;">
			<?php 
			if(isset($this->request->data['TrainingSessionTrainee']) && !empty($this->request->data['TrainingSessionTrainee'])){ ?>
				<?php 
				$i = 0;  
				foreach($this->request->data['TrainingSessionTrainee'] as $key=>$val){?>
				<?php if(!empty($val['identification_id'])){ ?>
				<div class="table_row " row-id="<?php echo $i;?>">
					<div class="table_cell cell_description" style="width:90%">
						<div class="input_wrapper">
					 	<div class="training-course-title-<?php echo $i;?>">
							<?php echo $val['identification_first_name'] . ', ' . $val['identification_last_name'];?>
						</div>
						<?php if(isset($val['id'])){ ?>
						<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.id', array('value'=>$val['id'], 
						'class' => 'control-id')); ?>
						<?php } ?>
						<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.identification_id', array('class' => 'trainee-id-'.$i,
							'value'=>$val['identification_id'])); ?>
							<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.identification_first_name', array('value'=>$val['identification_first_name'])); ?>
							<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.identification_last_name', array('value'=>$val['identification_last_name'])); ?>
							<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.identification_table', array('value'=>$val['identification_table'])); ?>
							<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.identification_validate', array('class' => 'trainee-validate-'.$i . ' validate-trainee', 'value'=>$val['identification_table'] . '_' . $val['identification_id'])); ?>
						</div>
				    </div>
				 
					<div class="table_cell cell_delete">
				    	<span class="icon_delete" title="Delete" onclick="objTrainingSessions.deleteTrainee(this)"></span>
				    </div>
				</div>
				<?php } ?>
		<?php 
			$i++;
		} ?>
		<?php } ?>
		</div>
	</div>

	<div class="row"><a class="void icon_plus" onclick="objTrainingSessions.addTrainee(this)" url="Training/ajax_add_trainee"  href="javascript: void(0)"><?php echo __('Add Trainee');?></a></div>

	</div>
</div>
<div class="controls view_controls">
	<?php if(!isset($this->request->data['TrainingSession']['training_status_id'])|| $this->request->data['TrainingSession']['training_status_id']==1){ ?>
	<input type="submit" value="<?php echo __("Save"); ?>" name='save' class="btn_save btn_right" onclick="js:if(objTrainingSessions.errorFlag() && Config.checkValidate()){ return true; }else{ return false; }"/>
	<input type="submit" value="<?php echo __("Submit for Approval"); ?>" name='submitForApproval' class="btn_save btn_right" onclick="js:if(objTrainingSessions.errorFlag() && Config.checkValidate()){ return true; }else{ return false; }"/>
	<?php } ?>
	<?php echo $this->Html->link(__('Cancel'), array('action' => 'session'), array('class' => 'btn_cancel btn_left')); ?>
</div>
	
<?php echo $this->Form->end(); ?>

<?php $this->end(); ?>	
