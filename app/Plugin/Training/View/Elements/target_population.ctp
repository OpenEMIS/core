
<div class="table_row <?php echo ($index+1)%2==0 ? 'li_even' : ''; ?>" row-id="<?php echo $index;?>">
	<div class="table_cell cell_description">
		<div class="input_wrapper">
	 	<div class="teacher-position-title-name-<?php echo $index;?>">
			<?php echo $this->Form->input('TrainingCourseTargetPopulation.' . $index . '.teacher_position_title_name', array('id' => 'searchTargetPopulation'.$index, 'div' => false, 'maxlength'=>50, 'label' => false, 'placeholder' => __('Position Title'))); ?>	
		</div>
		<?php echo $this->Form->hidden('TrainingCourseTargetPopulation.' . $index . '.teacher_position_title_id', array('class' => 'teacher-position-title-id-'.$index)); ?>
		</div>
    </div>
 
	<div class="table_cell cell_delete">
    	<span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="objTrainingCourses.deleteTargetPopulation(this)"></span>
    </div>
</div >