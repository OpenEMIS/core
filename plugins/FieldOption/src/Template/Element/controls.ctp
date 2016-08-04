<div class="toolbar-responsive panel-toolbar">
	<div class="toolbar-wrapper">
		<?php
			$baseUrl = $this->Url->build([
				'plugin' => $this->request->params['plugin'],
			    'controller' => $this->request->params['controller'],
			    'action' => ' '
			]);
			$template = $this->ControllerAction->getFormTemplate();
			$this->Form->templates($template);

			echo $this->Form->input('field_option', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $fieldOptions,
				'default' => $selectedOption,
				'url' => $baseUrl
			));

			if(!empty($parentFieldOptions)) {
				$baseUrl = trim($baseUrl) . $this->request->params['action'];
				echo $this->Form->input('parent_field_option', array(
					'class' => 'form-control',
					'label' => false,
					'options' => $parentFieldOptions,
					'default' => $selectedParentFieldOption,
					'url' => $baseUrl,
					'data-named-key' => 'parent_field_option_id'
				));
			}

		?>
	</div>
</div>