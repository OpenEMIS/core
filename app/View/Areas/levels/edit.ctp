<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
if ($_edit) {
	echo $this->Html->link(__('Back'), array('action' => 'levelsView', $this->data[$model]['id']), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'levelsEdit'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->Form->input('name');
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'levelsView', $this->data[$model]['id'])));
echo $this->Form->end();

$this->end();
?>
