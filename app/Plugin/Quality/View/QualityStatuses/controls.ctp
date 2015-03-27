<div class="row page-controls">
	<?php
		$baseUrl = $this->params['controller'] . '/' . $action;

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
		}
	?>
</div>
