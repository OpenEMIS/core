<?php
echo $this->Html->css('configuration', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.date', false);
echo $this->Html->script('config', false);
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);

echo $this->Html->charset();

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get('Config.name'));

$this->start('contentActions');
echo $this->Html->link(__('View'),array('controller' => 'Config', 'action'=>'index', $type) , array('class' => 'divider link_view'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action));
echo $this->Form->create('ConfigItem', $formOptions);

echo $this->Form->input('id', array('type' => 'hidden'));
echo $this->Form->input('name', array('type' => 'hidden'));
echo $this->Form->input('field_type', array('type' => 'hidden'));
echo $this->Form->input('option_type', array('type' => 'hidden'));
echo $this->Form->input('type', array('readonly' => 'readonly'));
echo $this->Form->input('label', array('readonly' => 'readonly'));

if($fieldType=='Dropdown'){
	echo $this->Form->input('value', array('options'=>$options));
}else if($fieldType=='Datepicker'){
	echo $this->FormUtility->datepicker('value');
}else{
	echo $this->Form->input('value');
}
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'view', $id)));
echo $this->Form->end();

$this->end();
?>
