<?php if (!empty($featureOptions) || !empty($workflowOptions)) : ?>
	<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
			<?php
				$baseUrl = $this->Url->build([
					'plugin' => $this->request->getParam('plugin'),
				    'controller' => $this->request->getParam('controller'),
				    'action' => $this->request->getParam('action')
				]);
				$template = $this->ControllerAction->getFormTemplate();
				$this->Form->templates($template);

				$dataNamedGroup = [];
				if (!empty($featureOptions)) {
					echo $this->Form->input('filter', [
						'class' => 'form-control',
						'label' => false,
						'options' => $featureOptions,
						'default' => $selectedFeature,
						'url' => $baseUrl,
						'data-named-key' => 'feature'
					]);
					$dataNamedGroup[] = 'feature';
				}

				if (!empty($workflowOptions)) {
					$inputOptions = [
						'class' => 'form-control',
						'label' => false,
						'options' => $workflowOptions,
						'default' => $selectedWorkflow,
						'url' => $baseUrl,
						'data-named-key' => 'workflow'
					];
					if (!empty($dataNamedGroup)) {
						$inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
						$dataNamedGroup[] = 'workflow';
					}
					echo $this->Form->input('workflow', $inputOptions);
				}
			?>
		</div>
	</div>
<?php endif ?>
