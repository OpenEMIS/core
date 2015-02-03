<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'resultView', $this->data[$modelName]['id']), array('class' => 'divider'));

echo $this->Html->link(__('Download Template'), array('action' => 'resultDownloadTemplate'), array('class' => 'divider'));
echo $this->Html->link(__('Upload Results'), array('action' => 'resultUpload'), array('class' => 'divider'));

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
	echo $this->Form->input('TrainingSession.start_date', array('type'=>'text', 'readonly' => 'readonly'));
	echo $this->Form->input('TrainingSession.end_date', array('type'=>'text', 'readonly' => 'readonly'));
	echo $this->Form->input('TrainingSession.location', array('label'=>array('text'=>__('Location'), 'class'=>'col-md-3 control-label'), 'id' => 'searchLocation', 'disabled' => 'disabled', 'class'=>'form-control location', 'placeholder' => __('Location'), 'value'=>$this->request->data['TrainingSession']['location']));
	?>
 <div class="form-group">
	<label class="col-md-3 control-label"><?php echo __('Trainers'); ?></label>
	<div class="col-md-6">
	<div class="table trainer">
		<table class="table table-striped table-hover table-bordered">
	 	<thead class="table_head">
        	<tr>
	       		<td class="table_cell"><?php echo __('Name'); ?></td>
	            <td class="table_cell"><?php echo __('Type'); ?></td>
	        </tr>
        </thead>
		<?php 
		if(isset($this->request->data['TrainingSessionTrainer']) && !empty($this->request->data['TrainingSessionTrainer'])){ ?>
			<?php 
			$i = 0;  
			foreach($this->request->data['TrainingSessionTrainer'] as $key=>$val){  ?>
			<tr class="table_row " row-id="<?php echo $i;?>">
				<td class="table_cell cell_description">
					<?php echo $val['ref_trainer_name'];?>
			    </td>
			    <td class="table_cell cell_description">
			    	<?php if($val['ref_trainer_table']=='Staff'){
		 			 	echo __('Internal');
			 		}else{
			 			echo __('External');
			 		}
			 		?>
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
 <div class="form-group">
	<label class="col-md-3 control-label"><?php echo __('Trainees'); ?></label>
	<div class="col-md-6">
	<div class="table trainee"  url="Training/ajax_find_trainee/" style="min-width:500px; overflow-x: scroll; white-space: nowrap;">
		<div class="delete-trainee" name="data[DeleteTrainee][{index}][id]"></div>
		<table class="table_body table-striped table-hover table-bordered" style="width:100%;">
	 	<thead class="table_head">
        	<tr>
	       		<td class="table_cell"><?php echo __('Name'); ?></td>
            	<?php foreach($trainingCourseResultTypes as $key=>$val){
            		echo '<td class="table_cell">'. $val['TrainingResultType']['name']. '<br/>('. __('Result').')</td>';
            		echo '<td class="table_cell">'. $val['TrainingResultType']['name']. '<br/>('. __('Completed').')</td>';
            	}?>

	            <td class="table_cell"><?php echo __('Overall Result'); ?></td>
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
						<?php echo $this->Model->getName($val);?>
					</div>
					<?php if(isset($val['id'])){ ?>
					<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.id', array('value'=>$val['id'], 
					'class' => 'control-id')); ?>
					<?php } ?>
					<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.staff_id', array('value'=>$val['staff_id'])); ?>
						<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.first_name', array('value'=>$val['first_name'])); ?>
						<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.middle_name', array('value'=>$val['middle_name'])); ?>
						<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.third_name', array('value'=>$val['third_name'])); ?>
						<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.last_name', array('value'=>$val['last_name'])); ?>
					</div>
			    </td>
			 	<?php if(isset($this->request->data['TrainingSessionTraineeResult']) && !empty($this->request->data['TrainingSessionTraineeResult'])){ 
			 		$r = 0;
			 		foreach($this->request->data['TrainingSessionTraineeResult'] as $key2=>$val2){ 
			 			if($val2['training_session_trainee_id']==$val['id']){ 
			 		 ?>
			 		<td class="table_cell">
			 			<?php 
			 			echo $this->Form->hidden('TrainingSessionTrainee.'.$i.'.TrainingSessionTraineeResult.'.$r.'.id', array('value'=>$val2['id'])); 
			 			echo $this->Form->hidden('TrainingSessionTrainee.'.$i.'.TrainingSessionTraineeResult.'.$r.'.training_session_trainee_id', array('value'=>$val2['training_session_trainee_id'])); 
						echo $this->Form->hidden('TrainingSessionTrainee.'.$i.'.TrainingSessionTraineeResult.'.$r.'.training_result_type_id', array('value'=>$val2['training_result_type_id']));
			 			?>
				    	<?php 
						echo $this->Form->input('TrainingSessionTrainee.'.$i.'.TrainingSessionTraineeResult.'.$r.'.result', array('label'=>false,'default'=>$val2['result'], 'style'=>'width:50px;')); ?>
				    </td>
				    <td class="table_cell" style="padding:5px;">
				    	<?php 
						echo $this->Form->input('TrainingSessionTrainee.'.$i.'.TrainingSessionTraineeResult.'.$r.'.pass', array('label'=>false,'default'=>$val2['pass'], 'empty'=>array('-1'=>''),
						 'options' => array(1=>__('Passed'), 0 => __('Failed')), 'style'=>'width:70px;padding:0')); ?>
				    </td>
			 	<?php 
			 			$r++;
			 				 }
			 			} 
			 		}
			 	?>
				<td class="table_cell">
			    	<?php 
					echo $this->Form->input('TrainingSessionTrainee.'. $i .'.result', array('label'=>false,'default'=>$val['result'], 'style'=>'width:50px;')); ?>
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
