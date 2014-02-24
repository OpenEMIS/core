
<div class="table_row <?php echo ($index+1)%2==0 ? 'li_even' : ''; ?>" row-id="<?php echo $index;?>">
	<div class="table_cell cell_description">
		<div class="input_wrapper">
	 	<div class="trainee-name-<?php echo $index;?>">
			<?php echo $this->Form->input('TrainingSessionTrainee.' . $index . '.trainee_name', array('type'=>'text', 'id' => 'searchTrainee'.$index, 'div' => false, 'maxlength'=>50, 'label' => false, 'placeholder' => __('Identification No, First Name or Last Name'))); ?>	
		</div>
		<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $index . '.identification_id', array('class' => 'trainee-id-'.$index . ' validate-trainee')); ?>
		<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $index . '.identification_first_name', array('class' => 'trainee-first-name-'.$index)); ?>
		<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $index . '.identification_last_name', array('class' => 'trainee-last-name-'.$index)); ?>
		<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $index . '.identification_table', array('class' => 'trainee-table-'.$index)); ?>
		</div>
    </div>
 
	<div class="table_cell cell_delete">
    	<span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="objTrainingSessions.deleteTrainee(this)"></span>
    </div>
</div >