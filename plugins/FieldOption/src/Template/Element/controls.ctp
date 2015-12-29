<div class="toolbar-responsive panel-toolbar">
	<div class="toolbar-wrapper">
		<?php
			$baseUrl = $this->Url->build([
				'plugin' => $this->request->params['plugin'],
			    'controller' => $this->request->params['controller'],
			    'action' => $this->request->params['action'],
			]);
			$template = $this->ControllerAction->getFormTemplate();
			$this->Form->templates($template);

			echo $this->Form->input('field_option', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $fieldOptions,
				'default' => $selectedOption,
				'url' => $baseUrl,
				'data-named-key' => 'field_option_id',
			));

			if(!empty($parentFieldOptions)) {
				echo $this->Form->input('parent_field_option', array(
					'class' => 'form-control',
					'label' => false,
					'options' => $parentFieldOptions,
					'default' => $selectedParentFieldOption,
					'url' => $baseUrl,
					'data-named-key' => 'parent_field_option_id',
					'data-named-group' => 'field_option_id'
				));
			}

		?>
	</div>
</div>