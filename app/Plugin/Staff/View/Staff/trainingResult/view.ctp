<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$obj = $data['TrainingSessionTrainee'];

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.list'), array('action' => 'trainingResult'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
?>
<div class="row">
	<div class="col-md-3"><?php echo __('Course Code'); ?></div>
	<div class="value"><?php echo $data['TrainingCourse']['code']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Course Title'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingCourse']['title']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Provider'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingProvider']['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Start Date'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingSession']['start_date']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('End Date'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingSession']['end_date']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Course Description'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingCourse']['description']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Location'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingSession']['location']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Trainer'); ?></div>
	<div class="col-md-6">
		<?php 
		if (!empty($trainingSessionTrainers)){ ?>

				<div class="table-responsive">
					<table class="table table-striped table-hover table-bordered">
			        <thead class="table_head">
			        	<tr>
				       		<td class="table_cell"><?php echo __('Name'); ?></td>
				            <td class="table_cell"><?php echo __('Type'); ?></td>
				        </tr>
			        </thead>
			       
			        <tbody>
			        	<?php foreach($trainingSessionTrainers as $val){ ?>
			            <tr class="table_row">
			            	<td class="table_cell"><?php echo $val['TrainingSessionTrainer']['ref_trainer_name'] ?></td>
			                <td class="table_cell">
			                <?php if($val['TrainingSessionTrainer']['ref_trainer_table']=='Staff'){
			                		echo __('Internal');
			                 }else{
			                 		echo __('Exernal');
			                 }
			                 ?>
			                </td>
			            </tr>
			           <?php } ?>
			        </tbody>
			    	</table>
			    </div>
			<?php
		}else{
			echo "-";
		} ?>
	</div>
</div>

<div class="row">
	<div class="col-md-3"><?php echo __('Result'); ?></div>
	<div class="col-md-6">
		<?php 
		if (!empty($trainingSessionTrainees)){ ?>

				<div class="table-responsive">
					<table class="table table-striped table-hover table-bordered">
			        <thead class="table_head">
			        	<tr>
				            <?php foreach($trainingCourseResultTypes as $key=>$val){
			            		echo '<td class="table_cell">'. $val['TrainingResultType']['name']. '<br/>('. __('Result').')</td>';
			            		echo '<td class="table_cell">'. $val['TrainingResultType']['name']. '<br/>('. __('Completed').')</td>';
			            	}?>

				            <td class="table_cell"><?php echo __('Overall Result'); ?></td>
				            <td class="table_cell"><?php echo __('Completed'); ?></td>
				        </tr>
			        </thead>
			       
			        <tbody>
			        	<?php 
			        	foreach($trainingSessionTrainees as $val){ ?>
			            	<tr class="table_row">
				              	<?php 
			        			if(!empty($val['TrainingSessionTraineeResult'])){ 
						 		foreach($val['TrainingSessionTraineeResult'] as $key2=>$val2){ 
						 			if($val2['training_session_trainee_id']==$val['TrainingSessionTrainee']['id']){ 
						 		 ?>
						 		<td class="table_cell">
							    	<?php echo $val2['result']; ?>
							    </td>
							    <td class="table_cell" style="padding:5px;">
							    	<?php if(!isset($val2['pass'])){
					                		echo '-';
					                 }else if($val2['pass'] == '1'){
					                 		echo __('Passed');
					                 }else if($val2['pass'] == '-1'){
					                 		echo '-';
					                 }else{
					                 		echo __('Failed');
					                 }
					                 ?>
							    </td>
						 	<?php 
				 					}

					 			} 
					 		}
						 	?>
			                	<td class="table_cell"><?php echo $val['TrainingSessionTrainee']['result']; ?></td>
				                <td class="table_cell">
				                <?php if(!isset($val['TrainingSessionTrainee']['pass'])){
				                		echo '-';
				                 }else if($val['TrainingSessionTrainee']['pass'] == '1'){
				                 		echo __('Passed');
				                 }else if($val['TrainingSessionTrainee']['pass'] == '-1'){
				                 		echo '-';
				                 }else{
				                 		echo __('Failed');
				                 }
				                 ?>
				                </td>
			            	</tr>
			           <?php } ?>
			        </tbody>
			    	</table>
			    </div>
			<?php
		}else{
			echo "-";
		} ?>
	</div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Status'); ?></div>
	<div class="col-md-6"><?php echo (isset($workflowStatus) ? $workflowStatus : $this->TrainingUtility->getTrainingStatus($modelName, $obj['id'], $data['TrainingSessionStatus']['name'], $data['TrainingSessionStatus']['id'])); ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Modified by'); ?></div>
	<div class="col-md-6"><?php echo trim($data['ModifiedUser']['first_name'] . ' ' . $data['ModifiedUser']['last_name']); ?></div>
</div>

<div class="row">
	<div class="col-md-3"><?php echo __('Modified on'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingSessionResult']['modified']; ?></div>
</div>

<div class="row">
	<div class="col-md-3"><?php echo __('Created by'); ?></div>
	<div class="col-md-6"><?php echo trim($data['CreatedUser']['first_name'] . ' ' . $data['CreatedUser']['last_name']); ?></div>
</div>

<div class="row">
	<div class="col-md-3"><?php echo __('Created on'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingSessionResult']['created']; ?></div>
</div>
<?php $this->end(); ?>