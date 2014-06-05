<?php

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
if ($_edit && !$WizardMode) {
    echo $this->Html->link($this->Label->get('general.back'), array('action' => 'contactsView', $id), array('class' => 'divider'));
}
$this->end();
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'contactsEdit', $id, $contactOptionId));
echo $this->Form->create($model, $formOptions);
echo $this->Form->input('id');

echo $this->Form->input('contact_option_id', array(
    'options' => $contactOptions,
    'default' => $contactOptionId,
    'url' => sprintf('%s/%s/%s', $this->params['controller'], $this->params['action'], $id),
    'onchange' => 'jsForm.change(this)',
    'label' => array('text'=> $this->Label->get('general.type'), 'class'=>'col-md-3 control-label')
));
echo $this->Form->input('contact_type_id', array(
    'options' => $contactTypeOptions,
    'label' => array('text'=> $this->Label->get('general.description'), 'class'=>'col-md-3 control-label')
));
echo $this->Form->input('value');
echo $this->Form->input('preferred', array('options' => $yesnoOptions));

echo $this->FormUtility->getFormWizardButtons(array(
    'cancelURL' => array('action' => 'contactsView', $id),
    'WizardMode' => $WizardMode,
    'WizardEnd' => isset($wizardEnd)?$wizardEnd : NULL,
    'WizardMandatory' => isset($mandatory)?$mandatory : NULL
));
echo $this->Form->end();

$this->end();
?>