<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get("$model.title"));

$this->start('contentActions');
if ($_edit) {
	echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'view', $_condition => $conditionId, $this->data[$model]['id']), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $model, 'edit', $this->data[$model]['id'], $_condition => $conditionId));
echo $this->Form->create($model, $formOptions);
echo $this->element('edit');
echo $this->Form->button('reload', array('id' => 'reload', 'type' => 'submit', 'name' => 'submit', 'value' => 'reload', 'class' => 'hidden'));
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $model, 'view', $_condition => $conditionId, $this->data[$model]['id'])));
echo $this->Form->end();

$this->end();
?>
