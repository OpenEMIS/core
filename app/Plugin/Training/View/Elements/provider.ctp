
<tr class="table_row <?php echo ($index+1)%2==0 ? 'li_even' : ''; ?>" row-id="<?php echo $index;?>">
	<td class="table_cell cell_description" style="width:90%">
	 	<?php 
			echo $this->Form->input('TrainingCourseProvider.' . $index . '.training_provider_id', array('options'=>$trainingProviderOptions, 'label'=>false, 'between'=>false, 'div'=>false, 'class'=>'form-control validate-provider', 'onchange'=>'objTrainingCourses.validateProvider();')); 
		?>
    </td>
 
	<td class="table_cell cell_delete">
    	<span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="objTrainingCourses.deleteProvider(this)"></span>
    </td>
</tr >