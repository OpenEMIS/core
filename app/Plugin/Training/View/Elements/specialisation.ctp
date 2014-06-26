
<tr class="table_row <?php echo ($index+1)%2==0 ? 'li_even' : ''; ?>" row-id="<?php echo $index;?>">
	<td class="table_cell cell_description" style="width:90%">
	 	<?php 
			echo $this->Form->input('TrainingCourseSpecialisation.' . $index . '.qualification_specialisation_id', array('options'=>$qualificationSpecialisationOptions, 'label'=>false, 'between'=>false, 'div'=>false, 'class'=>'form-control validate-specialisation', 'onchange'=>'objTrainingCourses.validateSpecialisation();')); 
		?>
    </td>
 
	<td class="table_cell cell_delete">
    	<span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="objTrainingCourses.deleteSpecialisation(this)"></span>
    </td>
</tr >