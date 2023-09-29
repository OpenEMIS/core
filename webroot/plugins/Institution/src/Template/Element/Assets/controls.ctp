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

            if (!empty($assetTypeOptions)) {
                    echo $this->Form->input('asset_type_id', array(
                    'type' => 'select',
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $assetTypeOptions,
                    'default' => $selectedAssetType,
                    'url' => $baseUrl,
                    'data-named-key' => 'asset_type_id',
                    'data-named-group' => 'accessibility'
                ));
            }
            
            if (!empty($accessibilityOptions)) {
                    echo $this->Form->input('accessibility', array(
                    'type' => 'select',
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $accessibilityOptions,
                    'default' => $selectedAccessibility,
                    'url' => $baseUrl,
                    'data-named-key' => 'accessibility',
                    'data-named-group' => 'asset_type_id'
                ));
            }
		?>
		</div>
	</div>	
