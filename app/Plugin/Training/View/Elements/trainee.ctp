<tr class="table_row <?php echo ($index+1)%2==0 ? 'li_even' : ''; ?>" row-id="<?php echo $index;?>">
	<td class="table_cell cell_description" style="width:90%">
		<div class="input_wrapper">
	 	<div class="trainee-name-<?php echo $index;?>">
			<?php echo $this->Form->input('TrainingSessionTrainee.' . $index . '.trainee_name', array('type'=>'text', 'id' => 'searchTrainee'.$index, 'div' => false, 'maxlength'=>50, 'label' => false, 'placeholder' => __('Identification No, First Name or Last Name'))); ?>	
		</div>
		<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $index . '.staff_id', array('class' => 'trainee-id-'.$index)); ?>
		<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $index . '.first_name', array('class' => 'trainee-first-name-'.$index)); ?>
		<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $index . '.last_name', array('class' => 'trainee-last-name-'.$index)); ?>
		<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $index . '.identification_validate', array('class' => 'trainee-validate-'.$index . ' validate-trainee')); ?>
		</div>
    </td>
 
	<td class="table_cell cell_delete">
    	<span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="objTrainingSessions.deleteTrainee(this)"></span>
    </td>
</tr >