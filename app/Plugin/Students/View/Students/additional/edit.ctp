<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
if (!$WizardMode) {
	echo $this->Html->link(__('View'), array('action' => 'additional'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$model = 'StudentCustomField';
$modelOption = 'StudentCustomFieldOption';
$modelValue = 'StudentCustomValue';
$action = 'edit';

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'additionalEdit'));
unset($formOptions['div']);
unset($formOptions['label']);
echo $this->Form->create($modelValue, $formOptions);
echo $this->element('customfields/index', compact('model', 'modelOption', 'modelValue', 'action'));

if (!$WizardMode) {
	echo $this->FormUtility->getFormButtons(array('center' => true, 'cancelURL' => array('action' => 'additional')));
} else {
	echo $this->FormUtility->getWizardButtons($WizardButtons);
}

echo $this->Form->end();
$this->end();
?>
