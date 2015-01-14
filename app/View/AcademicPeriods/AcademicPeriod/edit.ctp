<?php
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);

$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'view', $this->data[$model]['id'], 'parent' => $parentId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $model, 'edit', $this->data[$model]['id'], 'parent' => $parentId));
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->Form->input('name');
echo $this->Form->input('code');
echo $this->FormUtility->datepicker('start_date', array('id' => 'startDate'));
echo $this->FormUtility->datepicker('end_date', array('id' => 'endDate'));
echo $this->Form->input('visible', array('options' => $yesnoOptions));
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $model, 'view', $this->data[$model]['id'])));
echo $this->Form->end();

$this->end();
?>
