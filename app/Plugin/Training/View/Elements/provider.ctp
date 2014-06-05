
<div class="table_row <?php echo ($index+1)%2==0 ? 'li_even' : ''; ?>" row-id="<?php echo $index;?>">
	<div class="table_cell cell_description" style="width:90%">
		<div class="input_wrapper">
	 	<?php 
			echo $this->Form->input('TrainingCourseProvider.' . $index . '.training_provider_id', array('options'=>$trainingProviderOptions, 'label'=>false, 'div'=>false)); 
		?>
		</div>
    </div>
 
	<div class="table_cell cell_delete">
    	<span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="objTrainingCourses.deleteProvider(this)"></span>
    </div>
</div >