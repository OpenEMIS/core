<div class="toolbar-responsive panel-toolbar">
	<div class="toolbar-wrapper">
		<?php
			$baseUrl = $this->Url->build([
				'plugin' => $this->request->getParam('plugin'),
			    'controller' => $this->request->getParam('controller'),
			    'action' => $this->request->getParam('action'),
			]);
			$template = $this->ControllerAction->getFormTemplate();
			$this->Form->templates($template);
			echo $this->Form->input('config_item_type', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $typeOptions,
				'url' => $baseUrl,
				'data-named-key' => 'type'
			));
			if($this->request->getParam('action') == 'Themes') { //POCOR-8951
//                dd($productThemes);
				echo $this->Form->input('online_service', array(
					'class' => 'form-control',
					'label' => false,
					'options' => $productThemes,
                    'default' => $selectedProduct,
					'url' => $baseUrl,
					'data-named-key' => 'online_service',
                    'data-named-group' => 'type'
				));
			}
		?>
	</div>
</div>
