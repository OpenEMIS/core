<?php echo $this->element('breadcrumb'); ?>
<?php echo $this->Html->css('table', 'stylesheet', array('inline' => false)); ?>
<?php echo $this->Html->script('app.date', false); ?>
<?php echo $this->Html->script('/Staff/js/salary', false); ?>

<div id="identity" class="content_wrapper edit add">
     <h1>
        <span><?php echo __('Salary'); ?></span>
        <?php 
        if ($_edit) {
            echo $this->Html->link(__('Back'), array('action' => 'salariesView', $id), array('class' => 'divider'));
        }
        ?>
    </h1>
	<?php
	echo $this->Form->create('StaffSalary', array(
		'url' => array('controller' => 'Staff', 'action' => 'salariesEdit'),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>
    <?php $obj = @$this->request->data['StaffSalary']; ?>
	<?php echo $this->Form->input('StaffSalary.id');?>
	<div class="row">
        <div class="label"><?php echo __('Date'); ?></div>
       <div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'StaffSalary.salary_date',array('desc' => true)); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Gross Salary'); ?></div>
        <div class="value"><?php echo $this->Form->input('StaffSalary.gross_salary', array('class'=>'default total_gross_salary')); ?></div>
    </div>
        
    <fieldset class="section_group">
    <legend><?php echo __('Additions');?></legend>
    
    <div class="table full_width">
        <div class="delete deleteAddition" name="data[DeleteAddition][{index}][id]"></div>
        <div class="table_head">
            <div class="table_cell cell_title"><?php echo __('Type'); ?></div>
            <div class="table_cell"><?php echo __('Amount'); ?></div>
            <div class="table_cell cell_delete">&nbsp;</div>
        </div>
            
        <div class="table_body additions">
        <?php
            $totalAdditions = 0;
            if(isset($this->request->data['StaffSalaryAddition']) && !empty($this->data['StaffSalaryAddition'])){ 
            foreach($this->request->data['StaffSalaryAddition'] as $key=>$value){ ?>
                <?php 
                $index = $key;
                $order = $index;
                ?>
                <div data-id="<?php echo $index; ?>" class="table_row new_row <?php echo $order%2==0 ? 'even' : ''; ?>">
                    <div class="table_cell">
                        <?php echo $this->Form->input('StaffSalaryAddition.'.$index.'.id', array('type'=>'hidden', 'class'=>'addition-control-id', 'label' => false, 'value'=>$value['id'])); ?>

                        <?php echo $this->Form->input('StaffSalaryAddition.'.$index.'.salary_addition_type_id', array('class'=>'default', 'label' => false, 'options' => $additionOptions, 'default'=>$value['salary_addition_type_id'], 'empty'=>__('--Select'))); ?>
                    </div>
                    <div class="table_cell">
                        <?php echo $this->Form->input('StaffSalaryAddition.'.$index.'.addition_amount', 
                            array(
                                'class'=>'default addition_amount', 
                                'label' => false,
                                'type'=>'text',
                                'computeType' => 'total_salary_additions',
                                'onkeypress' => 'return utility.integerCheck(event)',
                                'onkeyup' => 'jsTable.computeTotal(this)'
                             )

                        ); ?>
                    </div>
                    <div class="table_cell">
                        <span class="icon_delete" title="<?php echo __("Delete"); ?>" onClick="Salary.deleteAddition(this)"></span>
                    </div>
                </div>
             <?php 
                $totalAdditions += $value['addition_amount'];
            } ?>
            <?php } ?>
            <br />
        </div>
        <a class="void icon_plus link_add" onclick="Salary.addAddition(this)"><?php echo __('Add') .' '. __('Addition'); ?></a>
        <div class="table_foot">
            <div class="table_cell cell_label"><?php echo __('Total Addition'); ?></div>
             <?php echo $this->Form->input('StaffSalary.additions', array('type'=>'hidden', 'class'=>'total_salary_additions_input', 'value'=>$totalAdditions)); ?>
            <div class="table_cell cell_value cell_number total_salary_additions"><?php echo $totalAdditions; ?></div>
        </div>
    </div>
    </fieldset>

    <fieldset class="section_group">
    <legend><?php echo __('Deductions');?></legend>
    <div class="table full_width">
        <div class="delete deleteDeduction" name="data[DeleteDeduction][{index}][id]"></div>
        <div class="table_head">
            <div class="table_cell cell_title"><?php echo __('Type'); ?></div>
            <div class="table_cell"><?php echo __('Amount'); ?></div>
            <div class="table_cell cell_delete">&nbsp;</div>
        </div>
        <div class="table_body deductions">
            <?php
            $totalDeductions = 0;
            if(isset($this->request->data['StaffSalaryDeduction']) && !empty($this->data['StaffSalaryDeduction'])){ 
            foreach($this->request->data['StaffSalaryDeduction'] as $key=>$value){ ?>
                <?php 
                $index = $key;
                $order = $index;
                ?>
                <div data-id="<?php echo $index; ?>" class="table_row new_row <?php echo $order%2==0 ? 'even' : ''; ?>">
                    <div class="table_cell">
                         <?php echo $this->Form->input('StaffSalaryDeduction.'.$index.'.id', array('type'=>'hidden', 'class'=>'deduction-control-id','label' => false, 'value'=>$value['id'])); ?>
                        <?php echo $this->Form->input('StaffSalaryDeduction.'.$index.'.salary_deduction_type_id', array('class'=>'default', 'label' => false, 'options' => $deductionOptions, 'default'=>$value['salary_deduction_type_id'], 'empty'=>__('--Select'))); ?>
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
            <?php 
                $totalDeductions += $value['deduction_amount'];
            } ?>
            <?php } ?>
            <br />
        </div>
        <a class="void icon_plus link_add" onclick="Salary.addDeduction(this)"><?php echo __('Add') .' '. __('Deduction'); ?></a>
        <div class="table_foot">
            <div class="table_cell cell_label"><?php echo __('Total Deduction'); ?></div>
             <?php echo $this->Form->input('StaffSalary.deductions', array('type'=>'hidden', 'class'=>'total_salary_deductions_input', 'value'=>$totalDeductions)); ?>
            <div class="table_cell cell_value cell_number total_salary_deductions"><?php echo $totalDeductions; ?></div>
        </div>
    </div>

    
    </fieldset>


    <div class="row">
        <div class="label"><?php echo __('Net Salary'); ?></div>
        <div class="value"><?php echo $this->Form->input('StaffSalary.net_salary', array('class'=>'default total_net_salary')); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Comment'); ?></div>
        <div class="value">
            <?php echo $this->Form->input('StaffSalary.comment', array('type'=>'textarea')); ?>
        </div>
    </div>
    <div class="controls view_controls">
        <input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
        <?php echo $this->Html->link(__('Cancel'), array('action' => 'salaries'), array('class' => 'btn_cancel btn_left')); ?>
    </div>
    <?php echo $this->Form->end(); ?>
</div>