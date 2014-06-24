<?php

echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));

echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
$setDate = array('id' => 'date_of_behaviour', 'label'=> $this->Label->get('general.date'));
if (!empty($this->data[$model]['id'])) {
	$redirectAction = array('action' => 'behaviourStudentView', $this->data[$model]['id']);
	$setDate['data-date'] = $this->data[$model]['date_of_behaviour'];
} else {
	$redirectAction = array('action' => 'behaviourStudent' ,$studentId);
}
echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));

$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, $studentId));
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create('StudentBehaviour', $formOptions);

echo $this->Form->input('id', array('type' => 'hidden'));

$labelOptions['text'] = $this->Label->get('general.category');
echo $this->Form->input('student_behaviour_category_id', array('options' => $categoryOptions, 'label' => $labelOptions));
echo $this->FormUtility->datepicker('date_of_behaviour', $setDate);

echo $this->Form->input('title');

echo $this->Form->input('description', array(
	'onkeyup' => 'utility.charLimit(this)',
	'type' => 'textarea'
));

echo $this->Form->input('action', array(
	'onkeyup' => 'utility.charLimit(this)',
	'type' => 'textarea'
));

echo $this->FormUtility->getFormButtons(array('cancelURL' => $redirectAction));
echo $this->Form->end();
$this->end();
?>