<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Alerts'));

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => $model), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('action' => $model, 'edit', $id));
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create($model, $formOptions);

echo $this->Form->hidden('id');
?>

<div id="staffAbsenceAdd" class="">
	<?php 
	echo $this->Form->input('name', array('type' => 'text', 'disabled' => true));
	echo $this->Form->input('threshold', array('type' => 'text', 'label' => $labelOptions));
	echo $this->Form->input('status', array('options' => $statusOptions));
	echo $this->Form->input('method', array('options' => $methodOptions, 'disabled' => true));
	echo $this->Form->input('subject', array('type' => 'text'));
	echo $this->Form->input('message', array('type' => 'textarea'));
	
	$labelOptions['text'] = $this->Label->get('Alert.roles');
	echo $this->Form->input('roles', array('options' => $roleOptions, 'label' => $labelOptions, 'multiple' => true, 'value' => $roleIds, 'size' => 8));
	
	echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $model, 'view', $id)));
	?>
</div>
<?php 
echo $this->Form->end();
$this->end(); 
?>