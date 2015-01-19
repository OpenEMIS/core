<div class="row page-controls">
	<?php
		$baseUrl = $this->params['controller'] . '/' . $this->request->action;

		if(isset($moduleOptions)) {
			echo $this->Form->input($Custom_Module.'_id', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $moduleOptions,
				'default' => 'module:' . $selectedModule,
				'div' => 'col-md-3',
				'url' => $baseUrl,
				'onchange' => 'jsForm.change(this)'
			));

			$baseUrl = $baseUrl . '/module:' . $selectedModule;
		}

		if(isset($groupOptions)) {
			echo $this->Form->input($Custom_Group.'_id', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $groupOptions,
				'default' => 'group:' . $selectedGroup,
				'div' => 'col-md-3',
				'url' => $baseUrl,
				'onchange' => 'jsForm.change(this)'
			));
		}
	?>
</div>