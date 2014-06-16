
<tr class="table_row <?php echo ($index+1)%2==0 ? 'li_even' : ''; ?>" row-id="<?php echo $index;?>">
	<td class="table_cell cell_description" style="width:90%">
		<?php echo $this->Form->input('TrainingCourseResultType.' . $index . '.training_result_type_id', array('id' => 'searchResultType'.$index, 'div' => false, 
		'label' => false, 'options' => $trainingResultTypeOptions, 'empty'=>__('--Select--'), 'class' => 'form-control result-type-validate-'.$index . ' validate-result-type')); ?>	
    </td>
 
	<td class="table_cell cell_delete">
    	<span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="objTrainingCourses.deleteResultType(this)"></span>
    </td>
</tr>