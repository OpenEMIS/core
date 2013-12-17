<?php
$index = $order;
// $fieldName = sprintf('data[Training][%s][%%s]', $index);
?>

<!-- <div class="table_row"> -->
<div data-id="<?php echo $index; ?>" class="table_row new_row <?php echo $order%2==0 ? 'even' : ''; ?>">
	<input type="hidden" value="0" name="data[TeacherSalaryAddition][<?php echo $index;?>][id]" />
    <div class="table_cell">
        <?php echo $this->Form->input('TeacherSalaryAddition.'.$index.'.salary_addition_type_id', array('label' => false, 'options' => $categories)); ?>
    </div>
    <div class="table_cell">
        <?php echo $this->Form->input('TeacherSalaryAddition.'.$index.'.amount', array('label' => false)); ?>
    </div>
    <div class="table_cell">
    	<span class="icon_delete" title="<?php echo __("Delete"); ?>" onClick="Training.removeRow(this)"></span>
    </div>
</div>