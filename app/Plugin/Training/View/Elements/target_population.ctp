
<tr class="table_row <?php echo ($index+1)%2==0 ? 'li_even' : ''; ?>" row-id="<?php echo $index;?>">
	<td class="table_cell cell_description" style="width:90%">
		<div class="input_wrapper">
	 	<div class="position-title-name-<?php echo $index;?>">
			<?php echo $this->Form->input('TrainingCourseTargetPopulation.' . $index . '.position_title_name', array('id' => 'searchTargetPopulation'.$index, 'div' => false, 'maxlength'=>50, 'label' => false, 'placeholder' => __('Position Title'))); ?>	
		</div>
		<?php echo $this->Form->hidden('TrainingCourseTargetPopulation.' . $index . '.position_title_id', array('class' => 'position-title-id-'.$index)); ?>
		<?php echo $this->Form->hidden('TrainingCourseTargetPopulation.' . $index . '.position_title_table', array('class' => 'position-title-table-'.$index)); ?>
		<?php echo $this->Form->hidden('TrainingCourseTargetPopulation.' . $index . '.position_title_validate', array('class' => 'position-title-validate-'.$index . ' validate-target-population')); ?>
		</div>
    </td>
 
	<td class="table_cell cell_delete">
    	<span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="objTrainingCourses.deleteTargetPopulation(this)"></span>
    </td>
</tr>