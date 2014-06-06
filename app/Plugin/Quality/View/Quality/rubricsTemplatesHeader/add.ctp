<?php

//2echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
echo $this->Html->script('config', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');
if ($_edit) {
	echo $this->Html->link($this->Label->get('general.back'), array('action' => 'rubricsTemplatesHeader'), array('class' => 'divider', 'id' => 'back'));
}
$this->end();
$this->start('contentBody');
$formOptions = array_merge(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin' => 'Quality'), $this->params['pass']);
$formOptions = $this->FormUtility->getFormOptions($formOptions);
echo $this->Form->create($modelName, $formOptions);

echo $this->Form->hidden('rubric_template_id', array('value' => $rubric_template_id));
if (!empty($this->data[$modelName]['id'])) {
	echo $this->Form->hidden('id');
}

if (!empty($this->data[$modelName]['order'])) {
	echo $this->Form->hidden('order');
}
echo $this->Form->input('title');


if (!empty($this->data[$modelName]['id'])) {
	$redirectAction = array('action' => 'rubricsTemplatesHeaderView', $rubric_template_id, $this->data[$modelName]['id']);
} else {
	$redirectAction = array('action' => 'rubricsTemplatesHeader', $rubric_template_id);
}
echo $this->FormUtility->getFormButtons(array('cancelURL' => $redirectAction));
echo $this->Form->end();
$this->end();
?>  