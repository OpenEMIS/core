<div class="row page-controls">
	<?php
		$baseUrl = $this->params['controller'] . '/' . $model . '/' . $this->request->action;

		if(!empty($rubricTemplateOptions)) {
			echo $this->Form->input('rubric_template_id', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $rubricTemplateOptions,
				'default' => 'template:' . $selectedRubricTemplate,
				'div' => 'col-md-3',
				'url' => $baseUrl,
				'onchange' => 'jsForm.change(this)'
			));

			$baseUrl = $baseUrl . '/template:' . $selectedRubricTemplate;
		}

		if(!empty($rubricSectionOptions)) {
			echo $this->Form->input('rubric_section_id', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $rubricSectionOptions,
				'default' => 'section:' . $selectedRubricSection,
				'div' => 'col-md-3',
				'url' => $baseUrl,
				'onchange' => 'jsForm.change(this)'
			));
		}
	?>
</div>