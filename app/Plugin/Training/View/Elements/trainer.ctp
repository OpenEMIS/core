<tr class="table_row <?php echo ($index+1)%2==0 ? 'li_even' : ''; ?>" row-id="<?php echo $index;?>">
	<td class="table_cell cell_description" style="width:55%">
		<div class="input_wrapper">
	 	<div class="trainer-name-<?php echo $index;?>">
			<?php 
			if($trainerType==__('Internal')){
				echo $this->Form->input('TrainingSessionTrainer.' . $index . '.trainer_name', array('type'=>'text', 'id' => 'searchTrainer'.$index, 'div' => false, 'maxlength'=>50, 'label' => false, 'placeholder' => __('Identification No, First Name or Last Name'))); 
			}else{
				echo $this->Form->input('TrainingSessionTrainer.' . $index . '.ref_trainer_name', array('type'=>'text', 'id' => 'searchTrainer'.$index, 'div' => false, 'maxlength'=>50, 'label' => false, 'class' => 'trainer-full-name-'.$index)); 
			}
			?>	
		</div>
		<?php if($trainerType==__('Internal')){
			echo $this->Form->hidden('TrainingSessionTrainer.' . $index . '.ref_trainer_name', array('class' => 'trainer-full-name-'.$index));
		} ?>
		<?php echo $this->Form->hidden('TrainingSessionTrainer.' . $index . '.ref_trainer_id', array('class' => 'trainer-id-'.$index)); ?>
		<?php echo $this->Form->hidden('TrainingSessionTrainer.' . $index . '.ref_trainer_table', array('class' => 'trainer-table-'.$index)); ?>
		<?php echo $this->Form->hidden('TrainingSessionTrainer.' . $index . '.trainer_validate', array('class' => 'trainer-validate-'.$index . ' validate-trainer')); ?>
		</div>
    </td>
 	<td class="table_cell cell_description">
 		<?php echo $this->Form->input('TrainingSessionTrainer.' . $index . '.trainer_type', array('class' => 'form-control', 'value'=>$trainerType, 'readonly'=>'readonly', 'label'=>false, 'div'=>false));?>
 	</td>
	<td class="table_cell cell_delete">
    	<span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="objTrainingSessions.deleteTrainer(this)"></span>
    </td>
</tr >