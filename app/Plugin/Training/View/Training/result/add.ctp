<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>


<div id="training_session" class="content_wrapper edit add" url="Training/ajax_find_session/" >
	<h1>
		<span><?php echo __($subheader); ?></span>
		<?php
		
            echo $this->Html->link(__('Back'), array('action' => 'result'), array('class' => 'divider'));
        
		?>
	</h1>
	
	<?php
	echo $this->Form->create($modelName, array(
		'url' => array('controller' => 'Training', 'action' => 'resultEdit', 'plugin'=>'Training'),
		'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>

	<?php if(!empty($this->data[$modelName]['id'])){ echo $this->Form->input('id', array('type'=> 'hidden')); } ?>
	<?php if(!empty($this->data[$modelName]['training_status_id'])){ echo $this->Form->input('training_status_id', array('type'=> 'hidden')); } ?>
	<div class="row">
		<div class="label"><?php echo __('Course'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('TrainingCourse.training_course_id', array('options' => $trainingCourseOptions, 'disabled' => 'disabled', 'default'=>$this->request->data['TrainingCourse']['id'])); 
		?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Start Date'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('TrainingSession.start_date', array('type' => 'date', 'default'=>$this->request->data['TrainingSession']['start_date'], 'dateFormat' => 'DMY', 'disabled' => 'disabled', 'before' => '<div class="left">', 'after' => '</div>','class'=>false)); 
		?>
        </div>
 	</div>   
    <div class="row">
         <div class="label"><?php echo __('End Date'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('TrainingSession.end_date', array('type' => 'date', 'default'=>$this->request->data['TrainingSession']['end_date'], 'dateFormat' => 'DMY', 'disabled' => 'disabled', 'before' => '<div class="left">', 'after' => '</div>','class'=>false)); 
		?>
        </div>
    </div>
     <div class="row">
        <div class="label"><?php echo __('Location'); ?></div>
        <div class="value">
 			<?php echo $this->Form->input('TrainingSession.location', array('id' => 'searchLocation', 'disabled' => 'disabled', 'class'=>'default location', 'placeholder' => __('Location'), 'value'=>$this->request->data['TrainingSession']['location']));?>
        </div>
    </div>
     <div class="row">
        <div class="label"><?php echo __('Provider'); ?></div>
        <div class="value">
        	<?php echo $this->Form->input('TrainingSession.trainer', array('id' => 'searchTrainer', 'disabled' => 'disabled', 'class'=>'default trainer', 'placeholder' => __('Identification No, First Name or Last Name'), 'value'=>$this->request->data['TrainingSession']['trainer']));?>
        </div>
    </div>
	 <div class="row">
		<div class="label"><?php echo __('Trainees'); ?></div>
		<div class="value">
		<div class="table trainee" style="width:240px;" url="Training/ajax_find_trainee/">
			<div class="delete-trainee" name="data[DeleteTrainee][{index}][id]"></div>
			<div class="table_body">
			<?php 
			if(isset($this->request->data['TrainingSessionTrainee']) && !empty($this->request->data['TrainingSessionTrainee'])){ ?>
				<?php 
				$i = 0;  
				foreach($this->request->data['TrainingSessionTrainee'] as $key=>$val){  ?>
				<div class="table_row " row-id="<?php echo $i;?>">
					<div class="table_cell cell_description">
						<div class="input_wrapper">
					 	<div class="training-course-title-<?php echo $i;?>">
							<?php echo $val['identification_first_name'] . ', ' . $val['identification_last_name'];?>
						</div>
						<?php if(isset($val['id'])){ ?>
						<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.id', array('value'=>$val['id'], 
						'class' => 'control-id')); ?>
						<?php } ?>
						<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.identification_id', array('value'=>$val['identification_id'])); ?>
							<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.identification_first_name', array('value'=>$val['identification_first_name'])); ?>
							<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.identification_last_name', array('value'=>$val['identification_last_name'])); ?>
							<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.identification_table', array('value'=>$val['identification_table'])); ?>
						</div>
				    </div>
				 
					<div class="table_cell cell_delete">
				    	<?php 
						echo $this->Form->input('TrainingSessionTrainee.'. $i .'.result', array('value'=>$val['result'], 'style'=>'width:40px;')); ?>
				    </div>
				    <div class="table_cell cell_delete">
				    	<?php 
				    	pr($val['pass']);
						echo $this->Form->input('TrainingSessionTrainee.'. $i .'.pass', array('default'=>$val['pass'], 'empty'=>array('-1'=>''),
						 'options' => array(1=>__('Pass'), 0 => __('Fail')), 'style'=>'width:60px;')); ?>
				    </div>
				</div>
			<?php 
				$i++;
			} ?>
			<?php } ?>
			</div>
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
</div>