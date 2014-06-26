<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
if(!$WizardMode) {
	echo $this->Html->link(__('View'), array('action' => 'additional'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$model = 'StaffCustomField';
$modelOption = 'StaffCustomFieldOption';
$modelValue = 'StaffCustomValue';
$action = 'edit';

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'additionalEdit'));
unset($formOptions['div']);
unset($formOptions['label']);
echo $this->Form->create($modelValue, $formOptions);
echo $this->element('customfields/index', compact('model', 'modelOption', 'modelValue', 'action'));
echo $this->FormUtility->getFormWizardButtons(array(
    'cancelURL' => array('action' => 'additional'),
    'WizardMode' => $WizardMode,
    'WizardEnd' => isset($wizardEnd) ? $wizardEnd : NULL,
    'WizardMandatory' => isset($mandatory) ? $mandatory : NULL
));
echo $this->Form->end();
$this->end();
?>
