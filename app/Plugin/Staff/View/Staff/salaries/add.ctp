<?php /*
<?php echo $this->element('breadcrumb'); ?>
<?php echo $this->Html->css('table', 'stylesheet', array('inline' => false)); ?>
<?php echo $this->Html->script('app.date', false); ?>
<?php echo $this->Html->script('/Staff/js/salary', false); ?>

<div id="salary" class="content_wrapper edit">
   <h1>
        <span><?php echo __('Salary'); ?></span>
        <?php 
        if ($_edit) {
            echo $this->Html->link(__('Back'), array('action' => 'salaries'), array('class' => 'divider'));
        }
        ?>
    </h1>

    <?php

    echo $this->Form->create('StaffSalary', array(
        'url' => array('controller' => 'Staff', 'action' => 'salariesAdd'),
        'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
    ));
    ?>
    <div class="row">
        <div class="label"><?php echo __('Date'); ?></div>
       <div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'StaffSalary.salary_date',array('desc' => true)); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Gross Salary'); ?></div>
        <div class="value"><?php echo $this->Form->input('StaffSalary.gross_salary', array('class'=>'default total_gross_salary')); ?></div>
    </div>
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
        <?php echo $this->Form->input('StaffSalaryAdditionFiller', array('type'=>'hidden', 'label' => false, 'div'=>false)); ?>
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
            <?php echo $this->Form->input('StaffSalaryDeductionFiller', array('type'=>'hidden', 'label' => false, 'div'=>false)); ?>
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


    <div class="controls view_controls">
        <input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
        <?php echo $this->Html->link(__('Cancel'), array('action' => 'salaries'), array('class' => 'btn_cancel btn_left')); ?>
    </div>
    <?php echo $this->Form->end(); ?>
</div>
 * 
 * 
 */?>

<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
echo $this->Html->script('Staff.salary', false);
echo $this->Html->script('app', false);
echo $this->Html->script('app.table', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_edit) {
	$salaryDate = array('id' => 'salaryDate', 'label'=> $this->Label->get('general.date'));
    if(!empty($this->data[$model]['id'])){
        $redirectAction = array('action' => 'salariesView', $this->data[$model]['id']);
		$salaryDate['data-date'] = $this->data[$model]['salary_date'];
	}
    else{
        $redirectAction = array('action' => 'salaries');
    }
    echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));
}
$this->end();
$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin'=>'Staff'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->FormUtility->datepicker('salary_date', $salaryDate);
echo $this->Form->input('gross_salary', array('class'=> 'form-control total_gross_salary'));
echo $this->Form->input('net_salary', array('class'=> 'form-control total_net_salary'));

//Setup Additional Info for salary
$labelText = 'Additions';
$tableId = 'table-additions';
$tableHeaders = array(__('Type'), __('Amount'), '&nbsp;');

$addIconData = array(
	'onclick' => 'Salary.addAddition(this)',
	'url' => 'Staff/salariesAjaxAdditionAdd'
);
$tableData = array();
$tempData = empty($this->data['StaffSalaryAddition'] )? array():$this->data['StaffSalaryAddition'] ;
foreach ($tempData as $key => $obj) {
	$columnData = $this->Form->input('StaffSalaryAddition.' . $key . '.salary_addition_type_id', array(
		'class' => 'form-control',
		'label' => false,
		'options' => $additionOptions,
		'div' => false,
		'between' => '<div class="input text">'));
	
	$columnData .= $this->Form->hidden('StaffSalaryAddition.' . $key . '.id');
	$row = array();
 	$row[] = $columnData;
	$row[] = $this->Form->input('StaffSalaryAddition.' . $key . '.addition_amount', array(
		'class' => 'form-control addition_amount',
		'label' => false,
		'div' => false,
		'between' => '<div class="input text">',
		'computeType' => 'total_salary_additions',
		'onkeypress' => 'return utility.integerCheck(event)',
		'onkeyup' => 'jsTable.computeTotal(this)'
			)
	);
	$row[] = '<span class="icon_delete" title="' . $this->Label->get('general.delete') . '" onClick="Salary.deleteAddition(this)"></span>';
	$tableData[] = $row;
}


$tableFooter = array(
	array(
		array(__('Total'), array('class'=>'cell-number')),
		array((isset($this->data[$model]['additions'])?$this->data[$model]['additions']: 0), array('class'=>'total_salary_additions cell-number')),
		'&nbsp;'
	));

echo $this->element('salaries/additional_info', compact('labelText','tableHeaders', 'tableData', 'tableFooter', 'addIconData', 'tableId'));
//End Additional


//Start Deduction
$labelText = 'Deductions';
$tableId = 'table-deductions';

$addIconData = array(
	'onclick' => 'Salary.addDeduction(this)',
	'url' => 'Staff/salariesAjaxDeductionAdd'
);
$tableData = array();
$tempData = empty($this->data['StaffSalaryDeduction'] )? array():$this->data['StaffSalaryDeduction'] ;
foreach ($tempData as $key => $obj) {
	$columnData = $this->Form->input('StaffSalaryDeduction.' . $key . '.salary_deduction_type_id', array(
		'class' => 'form-control',
		'label' => false,
		'options' => $deductionOptions,
		'div' => false,
		'between' => '<div class="input text">'));
	
	$columnData .= $this->Form->hidden('StaffSalaryDeduction.' . $key . '.id');
	$row = array();
	$row[] = $columnData;
	$row[] = $this->Form->input('StaffSalaryDeduction.' . $key . '.deduction_amount', array(
		'class' => 'form-control deduction_amount',
		'label' => false,
		'div' => false,
		'between' => '<div class="input text">',
		'computeType' => 'total_salary_additions',
		'onkeypress' => 'return utility.integerCheck(event)',
		'onkeyup' => 'jsTable.computeTotal(this)'
			)
	);
	$row[] = '<span class="icon_delete" title="' . $this->Label->get('general.delete') . '" onClick="Salary.deleteDeduction(this)"></span>';
	$tableData[] = $row;
}

$tableFooter = array(
	array(
		array(__('Total'), array('class'=>'cell-number')),
		array((isset($this->data[$model]['deductions'])?$this->data[$model]['deductions']: 0), array('class'=>'total_salary_deductions cell-number')),
		'&nbsp;'
	));
	
echo $this->element('salaries/additional_info', compact('labelText','tableHeaders', 'tableData', 'tableFooter', 'addIconData', 'tableId'));
//End deduction

echo $this->Form->input('comment');
echo $this->FormUtility->getFormButtons(array('cancelURL' => $redirectAction));
echo $this->Form->end();
$this->end();
?>