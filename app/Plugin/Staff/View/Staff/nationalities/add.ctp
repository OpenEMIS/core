<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_edit && !$WizardMode) {
	echo $this->Html->link(__('Back'), array('action' => 'nationalities'), array('class' => 'divider'));
}
$this->end();
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'nationalitiesAdd'));
echo $this->Form->create($model, $formOptions);

echo $this->Form->input('country_id', array('options'=>$countryOptions, 'default'=>$defaultCountryId));
echo $this->Form->input('comments', array('type'=>'textarea'));

if (!$WizardMode) {
	echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'nationalities')));
} else {
	echo $this->FormUtility->getWizardButtons($WizardButtons);
}

echo $this->Form->end();
$this->end();
?>
