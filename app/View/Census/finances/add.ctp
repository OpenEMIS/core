<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
	echo $this->Html->link(__('Back'), array('action' => 'finances', $selectedYear), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$url = $this->params['controller'] . '/financesAdd/' . $selectedYear;
$action = array('controller' => $this->params['controller'], 'action' => 'financesAdd', $selectedYear);
if(!empty($natureId)) {
	$action[] = $natureId;
}
if(!empty($typeId)) {
	$action[] = $typeId;
}
$formOptions = $this->FormUtility->getFormOptions($action);
echo $this->Form->create($model, $formOptions);
echo $this->Form->input('year', array('value' => $year, 'disabled'));
echo $this->Form->input('finance_nature', array(
	'options' => $natureOptions,
	'url' => $url,
	'value' => $natureId,
	'onchange' => 'jsForm.change(this)'
));
echo $this->Form->input('finance_type', array(
	'options' => $typeOptions,
	'url' => $this->params['controller'] . '/financesAdd/' . $selectedYear . '/' . $natureId,
	'value' => $typeId,
	'onchange' => 'jsForm.change(this)'
));
echo $this->Form->input('finance_category_id', array('options' => $categoryOptions));
echo $this->Form->input('finance_source_id', array('options' => $sourceOptions));
echo $this->Form->input('amount');
echo $this->Form->input('description', array('type' => 'textarea'));
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $_action)));
echo $this->Form->end();

$this->end();
?>
