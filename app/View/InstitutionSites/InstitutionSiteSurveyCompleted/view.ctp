<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $template['name']);
$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => 'InstitutionSiteSurveyCompleted', 'index'), array('class' => 'divider'));
	if ($_delete) {
		echo $this->Html->link(
			__('Reject'), 
			array('action' => 'InstitutionSiteSurveyCompleted', 'remove'), 
			array(
				'class' => 'divider', 
				'onclick' => 'return jsForm.confirmDelete(this)',
				'data-title' => __('Reject Confirmation'),
				'data-content' => __('You are about to reject this survey.<br><br>Are you sure you want to do this?'),
				'data-button-text' => __('Reject')
			)
		);
	}
$this->end();

$this->start('contentBody');

	$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->params['action'], 'edit', $id));
	$labelOptions = $formOptions['inputDefaults']['label'];
	echo $this->Form->create('InstitutionSiteSurveyCompleted', $formOptions);
		echo $this->Form->hidden('id', array('value' => $id));
		echo $this->element('customfields/index', compact('model', 'modelOption', 'modelRow', 'modelColumn', 'action'));
	echo $this->Form->end();

$this->end(); 
?>