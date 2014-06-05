<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('More'));

$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'additional'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
$modelOption = 'InstitutionSiteCustomFieldOption';
$modelValue = 'InstitutionSiteCustomValue';
$action = 'edit';

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'additionalEdit'));
unset($formOptions['div']);
unset($formOptions['label']);
echo $this->Form->create($modelValue, $formOptions);
echo $this->element('customfields/index', compact('model', 'modelOption', 'modelValue', 'action'));
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'additional')));
echo $this->Form->end();
$this->end();
?>
