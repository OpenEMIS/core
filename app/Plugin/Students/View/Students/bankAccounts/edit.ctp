<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
if ($_edit) {
	echo $this->Html->link($this->Label->get('general.back'), array('action' => 'bankAccountsView', $this->data[$model]['id']), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'bankAccountsEdit'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->Form->input('bank', array('value' => $bankObj['Bank']['name'], 'disabled' => 'disabled'));
echo $this->Form->input('bank_branch_id', array('options' => $bankBranchOptions));
echo $this->Form->input('account_name');
echo $this->Form->input('account_number');
echo $this->Form->input('active', array('options' => $yesnoOptions));
echo $this->Form->input('remarks', array('type' => 'textarea'));

echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'bankAccountsView', $this->data[$model]['id'])));
echo $this->Form->end();

$this->end();
?>
