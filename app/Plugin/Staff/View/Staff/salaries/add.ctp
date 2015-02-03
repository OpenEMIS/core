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
$redirectAction = array('action' => 'salaries');
$salaryDate = array('id' => 'salaryDate', 'label'=> $this->Label->get('general.date'));
if(!empty($this->data[$model]['id'])){
    $redirectAction = array('action' => 'salariesView', $this->data[$model]['id']);
	$salaryDate['data-date'] = $this->data[$model]['salary_date'];
} else if(isset($this->data[$model]['salary_date'])) {
    $salaryDate['data-date'] = $this->data[$model]['salary_date'];
}
echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));
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