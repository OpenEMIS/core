<div class="row page-controls">
	<?php
		$baseUrl = $this->params['controller'] . '/' . $model . '/' . $action;

		if(!empty($templateOptions)) {
			echo $this->Form->input('rubric_template_id', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $templateOptions,
				'default' => 'template:' . $selectedTemplate,
				'div' => 'col-md-3',
				'url' => $baseUrl,
				'onchange' => 'jsForm.change(this)'
			));

			$baseUrl = $baseUrl . '/template:' . $selectedTemplate;
		}

		if(!empty($sectionOptions)) {
			echo $this->Form->input('rubric_section_id', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $sectionOptions,
				'default' => 'section:' . $selectedSection,
				'div' => 'col-md-3',
				'url' => $baseUrl,
				'onchange' => 'jsForm.change(this)'
			));
		}
	?>
</div>