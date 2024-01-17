<div class="toolbar-responsive panel-toolbar">
	<div class="toolbar-wrapper">
		<?php
			$url = [
				'plugin' => $this->request->getParam('plugin'),
			    'controller' => $this->request->getParam('controller'),
			    'action' => $this->request->getParam('action'),
			];
			if (!empty($this->request->getParam('pass'))) {
				$url = array_merge($url, $this->request->getParam('pass'));
			}

			$dataNamedGroup = [];
			if (!empty($this->request->getQuery())) {
				foreach ($this->request->getQuery() as $key => $value) {
					if ($key == 'category') continue;
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
				'options' => $categoryOptions,
				'url' => $baseUrl,
				'default' => $selectedCategory,
				'data-named-key' => 'category',
				'escape' => false
			];
			if (!empty($dataNamedGroup)) {
				$inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
			}
			echo $this->Form->input('category', $inputOptions);
		?>
	</div>
</div>
