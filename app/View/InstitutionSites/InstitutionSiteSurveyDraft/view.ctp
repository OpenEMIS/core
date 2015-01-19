<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $template['name']);
$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => 'InstitutionSiteSurveyDraft', 'index'), array('class' => 'divider'));
	if ($_edit) {
	    echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'InstitutionSiteSurveyDraft', 'edit', $id), array('class' => 'divider'));
	}
	if ($_delete) {
		echo $this->Html->link($this->Label->get('general.delete'), array('action' => 'InstitutionSiteSurveyDraft', 'remove'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
	}
$this->end();

$this->start('contentBody');

	$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->params['action'], 'edit', $id));
	$labelOptions = $formOptions['inputDefaults']['label'];
	echo $this->Form->create('InstitutionSiteSurveyDraft', $formOptions);
		echo $this->Form->hidden('id', array('value' => $id));
		echo $this->element('customfields/index', compact('model', 'modelOption', 'modelRow', 'modelColumn', 'action'));
	echo $this->Form->end();

$this->end(); 
?>