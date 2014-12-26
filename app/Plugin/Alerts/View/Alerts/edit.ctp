<?php
echo $this->Html->css('../js/plugins/chosen/chosen.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/chosen/chosen.jquery.min', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Alerts'));

$model = 'Alert';
$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'view', $id), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('action' => 'edit', $id));
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create($model, $formOptions);

	echo $this->Form->hidden('id');
	echo $this->Form->input('name', array('type' => 'text', 'disabled' => true));
	echo $this->Form->input('threshold', array('type' => 'text', 'label' => $labelOptions));
	echo $this->Form->input('status', array('options' => $statusOptions));
	echo $this->Form->input('method', array('options' => $methodOptions, 'disabled' => true));
	echo $this->Form->input('subject', array('type' => 'text'));
	echo $this->Form->input('message', array('type' => 'textarea'));
	
	$labelOptions['text'] = $this->Label->get('Alert.roles');
	echo $this->Form->input('SecurityRole.SecurityRole', array('options' => $roleOptions, 'class' => 'chosen-select', 'label' => $labelOptions, 'multiple' => true, 'data-placeholder' => __('Select roles')));
	
	echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'view', $id)));

echo $this->Form->end();
$this->end(); 
?>
