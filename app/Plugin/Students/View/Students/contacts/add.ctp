<?php

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
if ($_edit && !$WizardMode) {
    echo $this->Html->link($this->Label->get('general.back'), array('action' => 'contacts'), array('class' => 'divider'));
}
$this->end();
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'contactsAdd', $contactOptionId));
echo $this->Form->create($model, $formOptions);

echo $this->Form->input('student_id', array('type' => 'hidden', 'value' => $studentId));
echo $this->Form->input('contact_option_id', array(
    'options' => $contactOptions,
    'default' => $contactOptionId,
    'url' => sprintf('%s/%s', $this->params['controller'], $this->params['action']),
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
    'cancelURL' => array('action' => 'contacts'),
    'WizardMode' => $WizardMode,
    'WizardEnd' => isset($wizardEnd)?$wizardEnd : NULL,
    'WizardMandatory' => isset($mandatory)?$mandatory : NULL
));

echo $this->Form->end();

$this->end();
?>