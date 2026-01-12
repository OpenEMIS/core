<div class="toolbar-responsive panel-toolbar">
	<div class="toolbar-wrapper">
		<?php
			if (!is_null($this->request->getQuery('mode'))) {
				$url = [
					'plugin' => $this->request->getParam('plugin'),
	                'controller' => $this->request->getParam('controller'),
	                'action' => $this->request->getParam('action')
				];
				if (!empty($this->request->getParam('pass'))) {
					$url = array_merge($url, $this->request->getParam('pass'));
				}

				$dataNamedGroup = [];
				if (!empty($this->request->getParam('query'))) {
					foreach ($this->request->getParam('query') as $key => $value) {
						if ($key == 'class' || $key == 'grade') continue;
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

				$inputOptions = [
					'class' => 'form-control',
					'label' => false,
					'options' => $gradeOptions,
					'url' => $baseUrl,
					'data-named-key' => 'grade',
					'escape' => false
				];
				if (!empty($dataNamedGroup)) {
					$inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
				}
				echo $this->Form->input('education_grade', $inputOptions);
			} else {
				$baseUrl = $this->Url->build([
					'plugin' => $this->request->getParam('plugin'),
				    'controller' => $this->request->getParam('controller'),
				    'action' => $this->request->getParam('action')
				]);
				$template = $this->ControllerAction->getFormTemplate();
				$this->Form->templates($template);

				if (!empty($periodOptions)) {
					echo $this->Form->input('academic_period', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $periodOptions,
						'url' => $baseUrl,
						'data-named-key' => 'period'
					));
				}

				if (!empty($gradeOptions)) {
					echo $this->Form->input('education_grade', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $gradeOptions,
						'url' => $baseUrl,
						'data-named-key' => 'grade',
						'data-named-group' => 'period'
					));
				}
			}
		?>
	</div>
</div>
