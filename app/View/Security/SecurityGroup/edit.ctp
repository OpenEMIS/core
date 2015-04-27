<?php
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', array('inline' => false));
echo $this->Html->script('security.group', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->data[$model]['name']);

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'view', $this->data[$model]['id']), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

echo $this->element('../Security/SecurityGroup/nav_tabs');

$formOptions = $this->FormUtility->getFormOptions(array('action' => $model, 'edit', $this->data[$model]['id']));
echo $this->Form->create($model, $formOptions);
echo $this->element('edit');
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $model)));
echo $this->Form->end();
$this->end();
?>
