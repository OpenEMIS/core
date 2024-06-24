
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

				if (!empty($userTypeOptions)) {
					echo $this->Form->input('user_type', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $userTypeOptions,
						'url' => $baseUrl,
						'data-named-key' => 'user_type',
						// 'data-named-group' => 'status_id,education_grade_id'
					));
				}
			?>
		</div>
	</div>
