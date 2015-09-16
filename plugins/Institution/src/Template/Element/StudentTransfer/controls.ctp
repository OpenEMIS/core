<div class="toolbar-responsive panel-toolbar">
	<div class="toolbar-wrapper">
		<?php
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
					if ($key == 'academic_period_id' || $key == 'education_grade_id') continue;
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

			// Academic Period
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
				$dataNamedGroup[] = 'academic_period_id';
			}
			echo $this->Form->input('academic_period_id', $inputOptions);
			// End

			// Education Grade
			$inputOptions = [
				'class' => 'form-control',
				'label' => false,
				'options' => $gradeOptions,
				'url' => $baseUrl,
				'data-named-key' => 'education_grade_id',
				'escape' => false
			];
			if (!empty($dataNamedGroup)) {
				$inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
				$dataNamedGroup[] = 'education_grade_id';
			}
			echo $this->Form->input('education_grade_id', $inputOptions);
			// End
		?>
	</div>
</div>
