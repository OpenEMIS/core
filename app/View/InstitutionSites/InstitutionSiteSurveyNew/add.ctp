<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get("InstitutionSiteSurveyNew.title"));
$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => 'InstitutionSiteSurveyNew', 'index'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

	$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->params['action'], 'add', $templateId, $academicPeriodId));
	$labelOptions = $formOptions['inputDefaults']['label'];
	echo $this->Form->create('InstitutionSiteSurveyNew', $formOptions);
	echo $this->Form->hidden('survey_template_id', array('value' => $templateId));
		echo $this->Form->hidden('academic_period_id', array('value' => $academicPeriodId));
		$labelOptions['text'] = __('Name');
		echo $this->Form->input('survey_template_name', array('disabled' => 'disabled', 'label' => $labelOptions, 'value' => $template['SurveyTemplate']['name']));
		echo $this->element('customfields/index', compact('model', 'modelOption', 'modelValue', 'modelRow', 'modelColumn', 'modelCell', 'action'));
?>
		<div class="form-group">
			<div class="col-md-offset-4">
				<?php
					echo $this->Form->submit(__('Save As Draft'), array('name' => 'submit', 'class' => 'btn_save btn_right', 'div' => false));
					echo $this->Form->submit($this->Label->get('general.submit'), array('name' => 'postFinal', 'class' => 'btn_save btn_center', 'div' => false));
					echo $this->Html->link($this->Label->get('general.cancel'), array('action' => 'InstitutionSiteSurveyNew', 'index'), array('class' => 'btn_cancel btn_left'));
				?>
			</div>
		</div>
<?php
	echo $this->Form->end();

$this->end();
?>