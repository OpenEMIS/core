<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_edit && !$WizardMode) {
            echo $this->Html->link(__('Back'), array('action' => 'nationalitiesView', $id), array('class' => 'divider'));
        }
$this->end();
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'nationalitiesEdit'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->Form->input('country_id', array('options'=>$countryOptions));
echo $this->Form->input('comments', array('type'=>'textarea'));

echo $this->FormUtility->getFormWizardButtons(array(
    'cancelURL' => array('action' => 'nationalitiesView', $id),
    'WizardMode' => $WizardMode,
    'WizardEnd' => isset($wizardEnd)?$wizardEnd : NULL,
    'WizardMandatory' => isset($mandatory)?$mandatory : NULL
));

echo $this->Form->end();
$this->end();
?>