<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_edit && !$WizardMode) {
    echo $this->Html->link($this->Label->get('general.back'), array('action' => 'bankAccounts'), array('class' => 'divider'));
}
$this->end();
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'bankAccountsAdd'));
echo $this->Form->create($model, $formOptions);

echo $this->Form->input('staff_id', array('type'=>'hidden', 'value'=>$staffId));
echo $this->Form->input('bank_id', array(
	'options' => $bankOptions,
	'default' => $bankId,
	'url' => sprintf('%s/%s', $this->params['controller'], $this->params['action']),
	'onchange' => 'jsForm.change(this)'
));
echo $this->Form->input('bank_branch_id', array('options' => $bankBranchesOptions));
echo $this->Form->input('account_name');
echo $this->Form->input('account_number');
echo $this->Form->input('active', array('options' => $yesnoOptions));
echo $this->Form->input('remarks', array('type'=>'textarea'));

echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'bankAccounts')));

echo $this->Form->end();

$this->end();
?>
