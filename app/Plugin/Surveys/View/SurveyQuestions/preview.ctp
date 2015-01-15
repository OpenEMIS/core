<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	$params = $this->params->named;
	echo $this->Html->link(__('Back'), array_merge(array('action' => 'index'), $params), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
?>

<div class="row page-controls">
	<?php
		echo $this->Form->input('survey_module_id', array(
			'class' => 'form-control',
			'label' => false,
			'options' => $moduleOptions,
			'default' => 'module:' . $selectedModule,
			'div' => 'col-md-3',
			'url' => $this->params['controller'] . '/preview',
			'onchange' => 'jsForm.change(this)'
		));

		if(isset($templateOptions)) {
			echo $this->Form->input('survey_template_id', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $templateOptions,
				'default' => 'parent:' . $selectedTemplate,
				'div' => 'col-md-3',
				'url' => $this->params['controller'] . '/preview/module:' . $selectedModule,
				'onchange' => 'jsForm.change(this)'
			));
		}
	?>
</div>

<?php
	$formOptions = $this->FormUtility->getFormOptions(array('plugin' => $this->params->plugin, 'controller' => $this->params['controller'], 'action' => 'preview'));
	$labelOptions = $formOptions['inputDefaults']['label'];
	echo $this->Form->create('SurveyQuestion', $formOptions);
		if(isset($templateOptions)) {
			echo $this->element('customfields/index', compact('model', 'modelOption', 'modelValue', 'modelRow', 'modelColumn', 'modelCell', 'action'));
		}
		echo $this->Form->end();
$this->end();
?>
