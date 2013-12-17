<?php
$index = $order;
// $fieldName = sprintf('data[Training][%s][%%s]', $index);
?>

<!-- <div class="table_row"> -->
<div data-id="<?php echo $index; ?>" class="table_row new_row <?php echo $order%2==0 ? 'even' : ''; ?>">
	<input type="hidden" value="0" class="addition-control-id" name="data[TeacherSalaryAddition][<?php echo $index;?>][id]" />
    <div class="table_cell">
        <?php echo $this->Form->input('TeacherSalaryAddition.'.$index.'.salary_addition_type_id', array('class'=>'default', 'label' => false, 'options' => $categories, 'empty'=>__('--Select'))); ?>
    </div>
    <div class="table_cell">
        <?php echo $this->Form->input('TeacherSalaryAddition.'.$index.'.amount', array('class'=>'default', 'label' => false)); ?>
    </div>
    <div class="table_cell">
    	<span class="icon_delete" title="<?php echo __("Delete"); ?>" onClick="Salary.deleteAddition(this)"></span>
    </div>
</div>