<?php if (!empty($filterOptions) || !empty($categoryOptions)) : ?>
	<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
			<?php
				$baseUrl = $this->Url->build([
					'plugin' => $this->request->params['plugin'],
				    'controller' => $this->request->params['controller'],
				    'action' => $this->request->params['action']
				]);
				$template = $this->ControllerAction->getFormTemplate();
				$this->Form->templates($template);

				$dataNamedGroup = [];
				if (!empty($filterOptions)) {
					echo $this->Form->input('filter', [
						'class' => 'form-control',
						'label' => false,
						'options' => $filterOptions,
						'url' => $baseUrl,
						'data-named-key' => 'filter'
					]);
					$dataNamedGroup[] = 'filter';
				}

				if (!empty($categoryOptions)) {
					$inputOptions = [
						'class' => 'form-control',
						'label' => false,
						'options' => $categoryOptions,
						'url' => $baseUrl,
						'data-named-key' => 'category'
					];
					if (!empty($dataNamedGroup)) {
						$inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
						$dataNamedGroup[] = 'category';
					}
					echo $this->Form->input('category', $inputOptions);
				}
			?>
		</div>
	</div>
<?php endif ?>
