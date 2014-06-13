<tr class="table_row <?php echo ($index+1)%2==0 ? 'li_even' : ''; ?>" row-id="<?php echo $index;?>">
	<td class="table_cell cell_description" style="width:90%">
		<div class="input_wrapper">
	 	<div class="trainer-name-<?php echo $index;?>">
			<?php echo $this->Form->input('TrainingSessionTrainer.' . $index . '.ref_trainer_name', array('type'=>'text', 'id' => 'searchTrainer'.$index, 'div' => false, 'maxlength'=>50, 'label' => false, 'placeholder' => __('Identification No, First Name or Last Name'))); ?>	
		</div>
		<div class="trainer-type-<?php echo $index;?>"></div>
		<?php echo $this->Form->hidden('TrainingSessionTrainer.' . $index . '.ref_trainer_id', array('class' => 'trainer-id-'.$index)); ?>
		<?php echo $this->Form->hidden('TrainingSessionTrainer.' . $index . '.ref_trainer_table', array('class' => 'trainer-table-'.$index)); ?>
		<?php echo $this->Form->hidden('TrainingSessionTrainer.' . $index . '.trainer_validate', array('class' => 'trainer-validate-'.$index . ' validate-trainer')); ?>
		</div>
    </td>
 
	<td class="table_cell cell_delete">
    	<span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="objTrainingSessions.deleteTrainee(this)"></span>
    </td>
</tr >