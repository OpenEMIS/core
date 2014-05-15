<?php

echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_edit && !$WizardMode) {
    echo $this->Html->link($this->Label->get('general.back'), array('action' => 'comments'), array('class' => 'divider'));
}
$this->end();
$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'commentsAdd'));
echo $this->Form->create($model, $formOptions);
echo $this->FormUtility->datepicker('comment_date', array('id' => 'CommentDate'));
echo $this->Form->input('title');
echo $this->Form->input('comment', array('type' => 'textarea'));

echo $this->FormUtility->getFormWizardButtons(array(
    'cancelURL' => array('action' => 'comments'),
    'WizardMode' => $WizardMode,
    'WizardEnd' => isset($wizardEnd)?$wizardEnd : NULL,
    'WizardMandatory' => isset($mandatory)?$mandatory : NULL
));
/*
  if (!$WizardMode) {
  echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'comments')));
  } else {
  echo '<div class="add_more_controls">' . $this->Form->submit(__('Add More'), array('div' => false, 'name' => 'submit', 'class' => "btn_save btn_right")) . '</div>';

  echo $this->Form->submit(__('Previous'), array('div' => false, 'name' => 'submit', 'class' => "btn_save btn_right"));
  if (!$wizardEnd) {
  echo $this->Form->submit(__('Next'), array('div' => false, 'name' => 'submit', 'name' => 'submit', 'class' => "btn_save btn_right"));
  } else {
  echo $this->Form->submit(__('Finish'), array('div' => false, 'name' => 'submit', 'name' => 'submit', 'class' => "btn_save btn_right"));
  }
  if ($mandatory != '1' && !$wizardEnd) {
  echo $this->Form->submit(__('Skip'), array('div' => false, 'name' => 'submit', 'class' => "btn_cancel btn_cancel_button btn_left"));
  }
  }
 */
echo $this->Form->end();
$this->end();
?>
