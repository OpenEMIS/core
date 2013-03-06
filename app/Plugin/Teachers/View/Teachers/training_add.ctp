<?php
$index = $order;
// $fieldName = sprintf('data[Training][%s][%%s]', $index);
?>

<!-- <div class="table_row"> -->
<div data-id="<?php echo $index; ?>" class="table_row new_row <?php echo $order%2==0 ? 'even' : ''; ?>">
	<input type="hidden" value="0" name="data[TeacherTraining][<?php echo $index;?>][id]" />
    <div class="table_cell">
        <?php 
            echo $this->Utility->getDatePicker($this->Form, 'completed_date', array('name'=> 'data[TeacherTraining]['.$index.'][completed_date]','desc' => true)); 
        ?>
        <?php //echo $this->Utility->getDatePicker($this->Form, 'completed_date',array('name'=> 'data[TeacherTraining]['.$index.'][completed_date]', 'desc' => true)); ?>
    </div>
    <div class="table_cell">
        <?php echo $this->Form->input('TeacherTraining]['.$index.'][teacher_training_category_id', array('label' => false, 'class' => 'training_category', 'options' => $categories)); ?>
    </div>
    <div class="table_cell">
    	<span class="icon_delete" title="<?php echo __("Delete"); ?>" onClick="Training.removeRow(this)"></span>
    </div>
</div>