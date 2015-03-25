<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => $roleType == 'user_defined' ? 'rolesUserDefined' : 'roles'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'rolesAdd'));
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->Form->input('name');
echo $this->Form->input('security_group_id', array('options' => $groupOptions, 'label' => array('text' => __('Groups'), 'class' => $labelOptions['class'])));
echo $this->Form->input('visible', array('options' => $yesnoOptions));
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $roleType == 'user_defined' ? 'roles_user_defined' : 'roles')));
echo $this->Form->end();

$this->end();
?>
