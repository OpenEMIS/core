<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => $_action, $selectedAcademicPeriod), array('class' => 'divider'));
if($_delete) {
	echo $this->Html->link(__('Delete'), array('action' => $_action.'Delete', $selectedAcademicPeriod), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
$this->end();

$this->start('contentBody');

$action = array('controller' => $this->params['controller'], 'action' => $_action.'Edit', $this->data['CensusFinance']['id']);
$formOptions = $this->FormUtility->getFormOptions($action);

echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->Form->hidden('finance_category_id');
echo $this->Form->input('academicPeriod', array('value' => $academicPeriod, 'disabled'));
echo $this->Form->input(__('Finance Nature'), array('value' => $financeNature['name'], 'disabled'));
echo $this->Form->input(__('Finance Type'), array('value' => $financeType['FinanceType']['name'], 'disabled'));
echo $this->Form->input(__('Finance Category'), array('value' => $financeCategory['name'], 'disabled'));
echo $this->Form->input('finance_source_id', array('options' => $sourceOptions));
echo $this->Form->input('amount');
echo $this->Form->input('description', array('type' => 'textarea'));
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $_action, $selectedAcademicPeriod)));
echo $this->Form->end();
$this->end();
?>
