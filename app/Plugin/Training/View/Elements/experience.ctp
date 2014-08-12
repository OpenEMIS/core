
<tr class="table_row <?php echo ($index+1)%2==0 ? 'li_even' : ''; ?>" row-id="<?php echo $index;?>">
	<td class="table_cell cell_description" style="width:90%">
		<div class="col-md-6" style="padding:3px;">
	 	<?php 
			echo $this->Form->input('TrainingCourseExperience.' . $index . '.number_years', array('id' => 'searchExperienceYear'.$index, 'options'=>$yearOptions, 'label'=>false, 'between'=>false, 'div'=>false, 'class'=>'form-control months-selection-'.$index));
		?>
		<?php echo __('Year(s)');?>
		</div>
		<div class="col-md-6" style="padding:3px;">
			<?php 
				echo $this->Form->input('TrainingCourseExperience.' . $index . '.number_months', array('id' => 'searchExperienceMonth'.$index, 'options'=>$monthOptions, 'label'=>false, 'between'=>false, 'div'=>false, 'class'=>'form-control months-selection-'.$index)); 
			?>	
			<?php echo __('Month(s)');?>
		</div>
		<?php echo $this->Form->hidden('TrainingCourseExperience.' . $index . '.months', array('class' => 'experience-validate-'.$index . ' validate-experience')); ?>
    </td>
 
	<td class="table_cell cell_delete">
    	<span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="objTrainingCourses.deleteExperience(this)"></span>
    </td>
</tr >