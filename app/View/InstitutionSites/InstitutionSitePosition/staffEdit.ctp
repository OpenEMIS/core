<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get("$model.title"));

$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'view', $id), array('class' => 'divider'));
	echo $this->Html->link($this->Label->get('general.delete'), array('action' => $model, 'staffDelete', $staffId), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
$this->end();

$this->start('contentBody');
	if (isset($staffFields['staff_name'])) {
		$staffFields['staff_name']['value'] = $this->Model->getName($staffFields['staff_name']['value']);
	}
	$formOptions = $this->FormUtility->getFormOptions(array('action' => $model, 'staffEdit', $staffId));
	$labelDefault = $formOptions;
	echo $this->Form->create($model, $formOptions);
	echo $this->element('edit', array('fields' => $staffFields));
	echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $model, 'view', $id)));
	echo $this->Form->end();
$this->end();
?>
