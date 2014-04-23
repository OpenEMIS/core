<?php
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_edit && !$WizardMode) {
            echo $this->Html->link($this->Label->get('general.back'), array('action' => 'identities'), array('class' => 'divider'));
        }
$this->end();
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'identitiesAdd'));
echo $this->Form->create($model, $formOptions);

echo $this->Form->input('identity_type_id', array('options'=>$identityTypeOptions));
echo $this->Form->input('number'); 
echo $this->FormUtility->datepicker('issue_date', array('id' => 'IssueDate'));
echo $this->FormUtility->datepicker('expiry_date', array('id' => 'ExpiryDate', 'data-date' => date('d-m-Y', time() + 86400)));
echo $this->Form->input('issue_location');
echo $this->Form->input('comments', array('type'=>'textarea'));

echo $this->FormUtility->getFormWizardButtons(array(
    'cancelURL' => array('action' => 'identities'),
    'WizardMode' => $WizardMode,
    'WizardEnd' => isset($wizardEnd)?$wizardEnd : NULL,
    'WizardMandatory' => isset($mandatory)?$mandatory : NULL
));

echo $this->Form->end();
$this->end();
?>
