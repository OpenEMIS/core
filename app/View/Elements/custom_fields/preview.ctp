<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	$params = $this->params->named;
	echo $this->Html->link(__('Back'), array_merge(array('action' => 'index'), $params), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
	echo $this->element('/custom_fields/controls');

	if(isset($selectedGroup)) {
		$formOptions = $this->FormUtility->getFormOptions(array('plugin' => $this->params->plugin, 'controller' => $this->params['controller'], 'action' => 'preview', 'module' => $selectedModule, 'group' => $selectedGroup));
		$formOptions['url'] = array_merge($formOptions['url'], $params);
		$labelOptions = $formOptions['inputDefaults']['label'];
		echo $this->Form->create($Custom_Field, $formOptions);
			if(isset($groupOptions)) {
				echo $this->element('customfields/index', compact('model', 'modelOption', 'modelValue', 'modelRow', 'modelColumn', 'modelCell', 'action'));
			}
		echo $this->Form->end();
	}
$this->end();
?>