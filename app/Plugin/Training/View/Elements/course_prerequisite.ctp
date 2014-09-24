<tr class="table_row <?php echo ($index+1)%2==0 ? 'li_even' : ''; ?>" row-id="<?php echo $index;?>">
	<td class="table_cell cell_description" style="width:90%">
		<div class="input_wrapper">
	 	<div class="training-course-title-<?php echo $index;?>">
			<?php echo $this->Form->input('TrainingCoursePrerequisite.' . $index . '.training_course_title', array('id' => 'searchCoursePrerequisite'.$index, 'div' => false, 'maxlength'=>50, 'label' => false, 'placeholder' => __('Course Code') . ', ' . __('Course Title'))); ?>	
		</div>
		<?php echo $this->Form->hidden('TrainingCoursePrerequisite.' . $index . '.training_prerequisite_course_id', array('class' => 'training-course-id-'.$index . ' validate-course-prerequisite')); ?>
		<?php echo $this->Form->hidden('TrainingCoursePrerequisite.' . $index . '.code', array('class' => 'course-code-'.$index)); ?>
		<?php echo $this->Form->hidden('TrainingCoursePrerequisite.' . $index . '.title', array('class' => 'course-title-'.$index)); ?>
		
		</div>
    </td>
 
	<td class="table_cell cell_delete">
    	<span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="objTrainingCourses.deleteCoursePrerequisite(this)"></span>
    </td>
</tr >