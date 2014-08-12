<?php 
if(isset($this->request->data['TrainingSessionTrainee']) && !empty($this->request->data['TrainingSessionTrainee'])){ ?>
	<?php 
	$i = 0;  
	if(isset($index)){
		$i = $index;  
	}
	foreach($this->request->data['TrainingSessionTrainee'] as $key=>$val){?>
	<?php if(!empty($val['staff_id'])){ ?>
<tr class="table_row " row-id="<?php echo $i;?>">
	<td class="table_cell cell_description" style="width:90%">
		<div class="input_wrapper">
	 	<div class="training-course-title-<?php echo $i;?>">
			<?php echo $val['first_name'] . ', ' . $val['last_name'];?>
		</div>
		<?php if(isset($val['id'])){ ?>
		<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.id', array('value'=>$val['id'], 
		'class' => 'control-id')); ?>
		<?php } ?>
		<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.staff_id', array('class' => 'trainee-id-'.$i,
			'value'=>$val['staff_id'])); ?>
			<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.first_name', array('value'=>$val['first_name'])); ?>
			<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.last_name', array('value'=>$val['last_name'])); ?>
			<?php echo $this->Form->hidden('TrainingSessionTrainee.' . $i . '.identification_validate', array('class' => 'trainee-validate-'.$i . ' validate-trainee', 'value'=>$val['staff_id'])); ?>
		</div>
    </td>		 
	<td class="table_cell cell_delete">
    	<span class="icon_delete" title="Delete" onclick="objTrainingSessions.deleteTrainee(this)"></span>
    </td>
</tr>
<?php } ?>
<?php 
$i++;
} ?>
<?php } ?>