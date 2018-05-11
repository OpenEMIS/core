<?php if (!empty($groupOptions)) : ?>
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

				echo $this->Form->input('security_group', array(
					'class' => 'form-control',
					'label' => false,
					'options' => $groupOptions,
					'default' => $selectedGroup,
					'url' => $baseUrl,
					'data-named-key' => 'security_group_id'
				));
			?>
		</div>
	</div>
<?php endif ?>
