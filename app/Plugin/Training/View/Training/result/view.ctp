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
			echo $this->Html->link(__('List'), array('action' => 'result' ), array('class' => 'divider'));
			if($_edit) {
				if($obj['training_status_id'] == 1){
					echo $this->Html->link(__('Edit'), array('action' => 'resultEdit',$obj['id'] ), array('class' => 'divider'));
				}
			}
			if($_execute) {
				if($obj['training_status_id'] == 2 || $obj['training_status_id']==3){
					if($obj['training_status_id'] == 2){
						echo $this->Html->link(__('Activate'), array('action' => 'resultActivate'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmActivate(this)'));
					}
					echo $this->Html->link(__('Inactivate'), array('action' => 'resultInactivate'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmInactivate(this)'));
				}
			}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
		
		<div class="row">
			<div class="label"><?php echo __('Course Code'); ?></div>
			<div class="value"><?php echo $trainingCourses['TrainingCourse']['code']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Course Title'); ?></div>
			<div class="value"><?php echo $trainingCourses['TrainingCourse']['title']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Start Date'); ?></div>
			<div class="value"><?php echo $data['TrainingSession']['start_date'];?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('End_date'); ?></div>
			<div class="value"><?php echo $data['TrainingSession']['end_date']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Status'); ?></div>
			<div class="value"><?php echo $data['TrainingStatus']['name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Location'); ?></div>
			<div class="value"><?php echo $data['TrainingSession']['location']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Trainer'); ?></div>
			<div class="value"><?php echo $data['TrainingSession']['trainer']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Trainee'); ?></div>
			<div class="value">
				<?php 
				if (!empty($trainingSessionTrainees)){ ?>

						<div class="table">
					        <div class="table_head">
					       		<div class="table_cell"><?php echo __('Name'); ?></div>
					            <div class="table_cell"><?php echo __('Result'); ?></div>
					            <div class="table_cell"><?php echo __('Pass'); ?></div>
					        </div>
					       
					        <div class="table_body">
					        	<?php foreach($trainingSessionTrainees as $val){ ?>
					            <div class="table_row">
					            	<div class="table_cell"><?php echo $val['TrainingSessionTrainee']['identification_first_name'] ?>, <?php echo $val['TrainingSessionTrainee']['identification_last_name'] ?></div>
					                <div class="table_cell"><?php echo $val['TrainingSessionTrainee']['result']; ?></div>
					                <div class="table_cell">
					                <?php if(!isset($val['TrainingSessionTrainee']['pass'])){
					                		echo '-';
					                 }else if($val['TrainingSessionTrainee']['pass'] == '1'){
					                 		echo __('Yes');
					                 }else if($val['TrainingSessionTrainee']['pass'] == '-1'){
					                 		echo '-';
					                 }else{
					                 		echo __('No');
					                 }
					                 ?>
					                </div>
					            </div>
					           <?php } ?>
					        </div>
					    </div>
					<?php
				}else{
					echo "-";
				} ?>

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
</div>
