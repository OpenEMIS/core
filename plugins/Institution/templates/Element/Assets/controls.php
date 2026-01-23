<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
		<?php
			$baseUrl = $this->Url->build([
				'plugin' => $this->request->getParam('plugin'),
			    'controller' => $this->request->getParam('controller'),
			    'action' => $this->request->getParam('action'),
                '0' => 'index',
                '1' => $encodedQueryString,
			]);
			$template = $this->ControllerAction->getFormTemplate();
			$this->Form->templates($template);

            if (!empty($academicPeriodOptions)) {
                    echo $this->Form->input('academic_period_id', array(
                    'type' => 'select',
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $academicPeriodOptions,
                    'default' => $selectedAcademicPeriodOptions,
                    'url' => $baseUrl,
                    'data-named-key' => 'academic_period_id',
                    'data-named-group' => 'asset_type_id,accessibility'
                ));
            }

            if (!empty($assetTypeOptions)) {
                    echo $this->Form->input('asset_type_id', array(
                    'type' => 'select',
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $assetTypeOptions,
                    'default' => $selectedAssetType,
                    'url' => $baseUrl,
                    'data-named-key' => 'asset_type_id',
                    //'data-named-group' => 'academic_period_id,accessibility'
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
                    //'data-named-group' => 'academic_period_id,asset_type_id'
                    'data-named-group' => 'asset_type_id'
                ));
            }
		?>
		</div>
	</div>	
