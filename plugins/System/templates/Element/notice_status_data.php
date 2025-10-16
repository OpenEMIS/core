<div class="toolbar-responsive panel-toolbar">
	<div class="toolbar-wrapper">
		<?php
			$baseUrl = $this->Url->build([
				'plugin' => $this->request->getParam('plugin'),
			    'controller' => $this->request->getParam('controller'),
			    'action' => $this->request->getParam('action'),
			     0 => 'index',
			]);
			$template = $this->ControllerAction->getFormTemplate();
			$this->Form->templates($template);
			
			echo $this->Form->input('notice_status', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $noticeOption,
				'default' => $noticeStatus,
				'url' => $baseUrl,
				'data-named-key' => 'notice_status'
			));
		?>
	</div>
</div>
