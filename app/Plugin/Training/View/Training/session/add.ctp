<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Training/js/sessions', false);
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);
?>
<?php echo $this->element('breadcrumb'); ?>

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

	<?php if(!empty($this->data[$modelName]['id'])){ echo $this->Form->input('id', array('type'=> 'hidden')); } ?>
	<?php if(!empty($this->data[$modelName]['training_status_id'])){ echo $this->Form->input('training_status_id', array('type'=> 'hidden')); } ?>

	<?php echo $this->Form->input('course_id', array('type'=> 'hidden', 'default'=>$course, 'class'=>'course')); ?>
	<?php echo $this->Form->input('provider_id', array('type'=> 'hidden', 'default'=>$provider, 'class'=>'provider')); ?>
	<div class="row">
		<div class="label"><?php echo __('Course'); ?></div>
        <div class="value">
		<?php 
            echo $this->Form->input('training_course_id', array(
                'options' => $trainingCourseOptions,
                'label' => false,
                'empty' => __('--Select--'),
                'class' => 'default training_course',
                'url' => sprintf('%s/%s', $this->params['controller'], $this->params['action']),
                'onchange' => 'objTrainingSessions.getDetailsAfterChangeCourse(this)'
            ));
		?>
        </div>
    </div>
    <div class="row">
		<div class="label"><?php echo __('Provider'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('training_provider_id', array('options' => array(),
				'class'=>'default training_provider')); 
		?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Start Date'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('start_date', array('type' => 'date', 'dateFormat' => 'DMY', 'before' => '<div class="left">', 'after' => '</div>','class'=>false)); 
		?>
        </div>
 	</div>   
    <div class="row">
         <div class="label"><?php echo __('End Date'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('end_date', array('type' => 'date', 'dateFormat' => 'DMY', 'before' => '<div class="left">', 'after' => '</div>','class'=>false)); 
		?>
        </div>
    </div>
 	<div class="row">
        <div class="label"><?php echo __('Location'); ?></div>
        <div class="value">
 			<?php echo $this->Form->input('location', array('id' => 'searchLocation', 'class'=>'default location', 'placeholder' => __('Location')));?>
        </div>
    </div>
     <div class="row">
        <div class="label"><?php echo __('Trainer'); ?></div>
        <div class="value">
        	<?php echo $this->Form->input('trainer', array('id' => 'searchTrainer', 'class'=>'default trainer', 'placeholder' => __('Identification No, First Name or Last Name')));?>
        </div>
    </div>
	 <div class="row" style="min-height:45px;">
		<div class="label"><?php echo __('Trainees'); ?></div>
		<div class="value">
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
</div>