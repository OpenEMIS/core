<?php
$index = $order;
// $fieldName = sprintf('data[Training][%s][%%s]', $index);
?>
<!-- <div class="table_row"> -->
<div data-id="<?php echo $index; ?>" class="table_row new_row <?php echo $order%2==0 ? 'even' : ''; ?>">
    <div class="table_cell">
         <?php echo $this->Form->input('StaffSalaryDeduction.'.$index.'.id', array('type'=>'hidden', 'class'=>'deduction-control-id', 'label' => false, 'value'=>"")); ?>
        <?php echo $this->Form->input('StaffSalaryDeduction.'.$index.'.salary_deduction_type_id', array('class'=>'default', 'label' => false, 'options' => $categories, 'empty'=>__('--Select'))); ?>
    </div>
    <div class="table_cell">
        <?php echo $this->Form->input('StaffSalaryDeduction.'.$index.'.deduction_amount', 
            array(
                'class'=>'default deduction_amount', 
                'label' => false,
                'type'=>'text',
                'computeType' => 'total_salary_deductions',
                'onkeypress' => 'return utility.integerCheck(event)',
                'onkeyup' => 'jsTable.computeTotal(this)'
             )

        ); ?>
    </div>
    <div class="table_cell">
    	<span class="icon_delete" title="<?php echo __("Delete"); ?>" onClick="Salary.deleteDeduction(this)"></span>
    </div>
</div>