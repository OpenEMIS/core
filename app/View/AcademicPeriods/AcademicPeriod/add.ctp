<?php
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);

$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'parent' => $parentId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $model, 'add', 'parent' => $parentId));
echo $this->Form->create($model, $formOptions);
echo $this->Form->input('name');
echo $this->Form->input('code');
$startDateAttr = array('id' => 'startDate');
if (!empty($parentStartDate)) $startDateAttr = array_merge($startDateAttr, $parentStartDate);
echo $this->FormUtility->datepicker('start_date', $startDateAttr);
$endDateAttr = array('id' => 'endDate');
if (!empty($parentEndDate)) $endDateAttr = array_merge($endDateAttr, $parentEndDate);
echo $this->FormUtility->datepicker('end_date', $endDateAttr);
echo $this->Form->input('parent', array('value' => $pathToString, 'disabled'));
echo $this->Form->input('academic_period_level_id', array('options' => $academicPeriodLevelOptions));
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $model, 'parent' => $parentId)));
echo $this->Form->end();
$this->end();
?>
