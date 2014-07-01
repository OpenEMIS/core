<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => 'rolesView', $this->data[$model]['id']), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'rolesEdit'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->Form->input('name');
echo $this->Form->input('security_group_id', array('options' => $groupOptions));
echo $this->Form->input('visible', array('options' => $yesnoOptions));
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'rolesView', $this->data[$model]['id'])));
echo $this->Form->end();

$this->end();
?>
