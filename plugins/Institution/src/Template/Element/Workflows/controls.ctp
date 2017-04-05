<?php if (!empty($featureOptions) || !empty($filterOptions) || !empty($statusOptions)) : ?>
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

				$dataNamedGroup = [];
				if (!empty($featureOptions)) {
					echo $this->Form->input('feature', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $featureOptions,
						'default' => $selectedFeature,
						'url' => $baseUrl,
						'data-named-key' => 'feature'
					));
					$dataNamedGroup[] = 'feature';
				}

				if (!empty($filterOptions)) {
					$inputOptions = [
						'class' => 'form-control',
						'label' => false,
						'options' => $filterOptions,
						'default' => $selectedFilter,
						'url' => $baseUrl,
						'data-named-key' => 'filter'
					];
					if (!empty($dataNamedGroup)) {
						$inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
						$dataNamedGroup[] = 'filter';
					}
					echo $this->Form->input('filter', $inputOptions);
				}

				if (!empty($statusOptions)) {
					$inputOptions = [
						'class' => 'form-control',
						'label' => false,
						'options' => $statusOptions,
						'default' => $selectedStatus,
						'url' => $baseUrl,
						'data-named-key' => 'status'
					];
					if (!empty($dataNamedGroup)) {
						$inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
						$dataNamedGroup[] = 'status';
					}
					echo $this->Form->input('status', $inputOptions);
				}
			?>
		</div>
	</div>
<?php endif ?>
