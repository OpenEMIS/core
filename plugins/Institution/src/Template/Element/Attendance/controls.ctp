<?php if (!empty($periodOptions) || !empty($weekOptions) || !empty($dayOptions) || !empty($classOptions)) : ?>
	<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
			<?php
				if (!is_null($this->request->query('mode'))) {
					$url = [
						'plugin' => $this->request->params['plugin'],
					    'controller' => $this->request->params['controller'],
					    'action' => $this->request->params['action']
					];
					if (!empty($this->request->pass)) {
						$url = array_merge($url, $this->request->pass);
					}

					$dataNamedGroup = [];
					if (!empty($this->request->query)) {
						foreach ($this->request->query as $key => $value) {
							if (in_array($key, ['period_id', 'week', 'day', 'class_id'])) continue;
							echo $this->Form->hidden($key, [
								'value' => $value,
								'data-named-key' => $key
							]);
							$dataNamedGroup[] = $key;
						}
					}

					$baseUrl = $this->Url->build($url);
					$template = $this->ControllerAction->getFormTemplate();
					$this->Form->templates($template);

					if (!empty($periodOptions)) {
						$inputOptions = [
							'class' => 'form-control',
							'label' => false,
							'options' => $periodOptions,
							'url' => $baseUrl,
							'data-named-key' => 'academic_period_id',
							'escape' => false
						];
						if (!empty($dataNamedGroup)) {
							$inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
							$dataNamedGroup[] = 'period_id';
						}
						echo $this->Form->input('academic_period', $inputOptions);
					}

					if (!empty($weekOptions)) {
						$inputOptions = [
							'class' => 'form-control',
							'label' => false,
							'options' => $weekOptions,
							'url' => $baseUrl,
							'data-named-key' => 'week',
							'escape' => false
						];
						if (!empty($dataNamedGroup)) {
							$inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
							$dataNamedGroup[] = 'week';
						}
						echo $this->Form->input('weeks', $inputOptions);
					}

					if (!empty($dayOptions)) {
						$inputOptions = [
							'class' => 'form-control',
							'label' => false,
							'options' => $dayOptions,
							'url' => $baseUrl,
							'data-named-key' => 'day',
							'escape' => false
						];
						if (!empty($dataNamedGroup)) {
							$inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
							$dataNamedGroup[] = 'day';
						}
						echo $this->Form->input('days', $inputOptions);
					}

					if (!empty($classOptions)) {
						$inputOptions = [
							'class' => 'form-control',
							'label' => false,
							'options' => $classOptions,
							'url' => $baseUrl,
							'data-named-key' => 'class_id',
							'escape' => false
						];
						if (!empty($dataNamedGroup)) {
							$inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
							$dataNamedGroup[] = 'class_id';
						}
						echo $this->Form->input('classes', $inputOptions);
					}
				} else {
					$baseUrl = $this->Url->build([
						'plugin' => $this->request->params['plugin'],
					    'controller' => $this->request->params['controller'],
					    'action' => $this->request->params['action'],
					]);
					$template = $this->ControllerAction->getFormTemplate();
					$this->Form->templates($template);

					if (!empty($periodOptions)) {
						echo $this->Form->input('academic_period', array(
							'class' => 'form-control',
							'label' => false,
							'options' => $periodOptions,
							'url' => $baseUrl,
							'data-named-key' => 'academic_period_id'
						));
					}

					if (!empty($weekOptions)) {
						echo $this->Form->input('weeks', array(
							'class' => 'form-control',
							'label' => false,
							'options' => $weekOptions,
							'url' => $baseUrl,
							'data-named-key' => 'week',
							'data-named-group' => 'academic_period_id'
						));
					}

					if (!empty($dayOptions)) {
						echo $this->Form->input('days', array(
							'class' => 'form-control',
							'label' => false,
							'options' => $dayOptions,
							'url' => $baseUrl,
							'data-named-key' => 'day',
							'data-named-group' => 'academic_period_id,week'
						));
					}

					if (!empty($classOptions)) {
						echo $this->Form->input('classes', array(
							'class' => 'form-control',
							'label' => false,
							'options' => $classOptions,
							'url' => $baseUrl,
							'data-named-key' => 'class_id',
							'data-named-group' => 'academic_period_id,week,day'
						));
					}
				}
			?>
		</div>
	</div>
<?php endif ?>
