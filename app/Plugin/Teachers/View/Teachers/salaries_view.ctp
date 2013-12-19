<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="salaryView" class="content_wrapper">
    <h1>
        <span><?php echo __('Salary'); ?></span>
        <?php
        $data = $salaryObj[0]['TeacherSalary'];
        echo $this->Html->link(__('List'), array('action' => 'salaries', $data['teacher_id']), array('class' => 'divider'));
        if($_edit) {
            echo $this->Html->link(__('Edit'), array('action' => 'salariesEdit', $data['id']), array('class' => 'divider'));
        }
        if($_delete) {
            echo $this->Html->link(__('Delete'), array('action' => 'salariesDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
        }
        ?>
    </h1>
    <?php echo $this->element('alert'); ?>
    
    <div class="row">
        <div class="label"><?php echo __('Date'); ?></div>
        <div class="value"><?php echo $this->Utility->formatDate($data['salary_date']); ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Gross Salary'); ?></div>
        <div class="value"><?php echo $data['gross_salary']; ?></div>
    </div>
    
  <fieldset class="section_group">
    <legend><?php echo __('Additions');?></legend>
    <div class="table full_width">
        <div class="table_head">
            <div class="table_cell cell_title"><?php echo __('Type'); ?></div>
            <div class="table_cell"><?php echo __('Amount'); ?></div>
        </div>
        <div class="table_body">
            <?php
            $totalAdditions = 0;
            if(isset($salaryObj[0]['TeacherSalaryAddition']) && !empty($salaryObj[0]['TeacherSalaryAddition'])){ 
            foreach($salaryObj[0]['TeacherSalaryAddition'] as $key=>$value){ ?>
                <?php 
                $index = $key;
                $order = $index;
                ?>
                <div data-id="<?php echo $index; ?>" class="table_row new_row <?php echo $order%2==0 ? 'even' : ''; ?>">
                    <div class="table_cell">
                        <?php echo $additionOptions[$value['salary_addition_type_id']]; ?>
                    </div>
                    <div class="table_cell">                        
                        <?php echo $value['addition_amount'];?>
                    </div>
                </div>
            <?php 
                $totalAdditions += $value['addition_amount'];
            } ?>
            <?php } ?>
        </div>
        <div class="table_foot">
            <div class="table_cell cell_label"><?php echo __('Total Addition'); ?></div>
            <div class="table_cell cell_value cell_number"><?php echo number_format($totalAdditions,2); ?></div>
        </div>
    </div>
    </fieldset>

    <fieldset class="section_group">
    <legend><?php echo __('Deductions');?></legend>
    <div class="table full_width">
        <div class="table_head">
            <div class="table_cell cell_title"><?php echo __('Type'); ?></div>
            <div class="table_cell"><?php echo __('Amount'); ?></div>
        </div>
        <div class="table_body">
            <?php
            $totalDeductions = 0;
            if(isset($salaryObj[0]['TeacherSalaryDeduction']) && !empty($salaryObj[0]['TeacherSalaryDeduction'])){ 
            foreach($salaryObj[0]['TeacherSalaryDeduction'] as $key=>$value){ ?>
                <?php 
                $index = $key;
                $order = $index;
                ?>
                <div data-id="<?php echo $index; ?>" class="table_row new_row <?php echo $order%2==0 ? 'even' : ''; ?>">
                    <div class="table_cell">
                        <?php echo $additionOptions[$value['salary_deduction_type_id']]; ?>
                    </div>
                    <div class="table_cell">                        
                        <?php echo $value['deduction_amount'];?>
                    </div>
                </div>
            <?php 
                $totalDeductions += $value['deduction_amount'];
            } ?>
            <?php } ?>
        </div>
        <div class="table_foot">
            <div class="table_cell cell_label"><?php echo __('Total Deduction'); ?></div>
            <div class="table_cell cell_value cell_number"><?php echo number_format($totalDeductions,2); ?></div>
        </div>
    </div>
    </fieldset>

    <div class="row">
        <div class="label"><?php echo __('Net Salary'); ?></div>
        <div class="value"><?php echo $data['net_salary']; ?></div>
    </div>

      <div class="row">
        <div class="label"><?php echo __('Comment'); ?></div>
        <div class="value"><?php echo $data['comment']; ?></div>
    </div>

    
   <div class="row">
        <div class="label"><?php echo __('Modified by'); ?></div>
        <div class="value"><?php echo trim($salaryObj[0]['ModifiedUser']['first_name'] . ' ' . $salaryObj[0]['ModifiedUser']['last_name']); ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Modified on'); ?></div>
        <div class="value"><?php echo $data['modified']; ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Created by'); ?></div>
        <div class="value"><?php echo trim($salaryObj[0]['CreatedUser']['first_name'] . ' ' . $salaryObj[0]['CreatedUser']['last_name']); ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Created on'); ?></div>
        <div class="value"><?php echo $data['created']; ?></div>
    </div>
    
</div>
