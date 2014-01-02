<?php
$index = $order;
// $fieldName = sprintf('data[Training][%s][%%s]', $index);
?>
<!-- <div class="table_row"> -->
<div data-id="<?php echo $index; ?>" class="table_row new_row <?php echo $order%2==0 ? 'even' : ''; ?>">

    <div class="table_cell">
         <?php echo $this->Form->input('TeacherSalaryAddition.'.$index.'.id', array('type'=>'hidden', 'class'=>'addition-control-id', 'label' => false, 'value'=>"")); ?>
        <?php echo $this->Form->input('TeacherSalaryAddition.'.$index.'.salary_addition_type_id', array('class'=>'default', 'label' => false, 'options' => $categories, 'empty'=>__('--Select'), 'error' => false)); ?>
    </div>
    <div class="table_cell">
        <?php echo $this->Form->input('TeacherSalaryAddition.'.$index.'.addition_amount', 
            array(
                'class'=>'default addition_amount', 
                'label' => false,
                'type'=>'text',
                'computeType' => 'total_salary_additions',
                'onkeypress' => 'return utility.integerCheck(event)',
                'onkeyup' => 'jsTable.computeTotal(this)',
                'error' => false
             )

        ); ?>
    </div>
    <div class="table_cell">
    	<span class="icon_delete" title="<?php echo __("Delete"); ?>" onClick="Salary.deleteAddition(this)"></span>
    </div>
</div>