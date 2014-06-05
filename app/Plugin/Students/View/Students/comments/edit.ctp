<?php

echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
if ($_edit && !$WizardMode) {
    echo $this->Html->link($this->Label->get('general.back'), array('action' => 'commentsView', $this->data[$model]['id']), array('class' => 'divider'));
}

$this->end();

$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'commentsEdit'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->FormUtility->datepicker('comment_date', array('id' => 'CommentDate', 'data-date' => $this->data[$model]['comment_date']));
echo $this->Form->input('title');
echo $this->Form->input('comment', array('type' => 'textarea'));

echo $this->FormUtility->getFormWizardButtons(array(
    'cancelURL' => array('action' => 'commentsView', $this->data[$model]['id']),
    'WizardMode' => $WizardMode,
    'WizardEnd' => isset($wizardEnd) ? $wizardEnd : NULL,
    'WizardMandatory' => isset($mandatory) ? $mandatory : NULL
));
echo $this->Form->end();
$this->end();
?>
