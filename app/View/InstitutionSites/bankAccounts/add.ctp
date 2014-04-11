<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'bankAccountsAdd'));
echo $this->Form->create('InstitutionSiteBankAccount', $formOptions);

echo $this->Form->input('institution_site_id', array('type'=>'hidden', 'value'=>$institutionSiteId));
echo $this->Form->input('bank_id', array(
	'options' => $bankList,
	'default' => $bankId,
	'url' => sprintf('%s/%s', $this->params['controller'], $this->params['action']),
	'onchange' => 'jsForm.change(this)'
));
echo $this->Form->input('bank_branch_id', array('options' => $branchList));
echo $this->Form->input('account_name');
echo $this->Form->input('account_number');
echo $this->Form->input('active', array('options' => $yesnoOptions));
echo $this->Form->input('remarks', array('type'=>'textarea'));
echo $this->FormUtility->getFormButtons($this, array('cancelURL' => array('action' => 'bankAccounts')));
echo $this->Form->end();

$this->end();
?>
