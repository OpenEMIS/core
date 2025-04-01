<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
		<?php
        $encodedQueryString = $this->request->getParam('pass')[1];
//        dd($encodedQueryString);
			$baseUrl = $this->Url->build([
				'plugin' => $this->request->getParam('plugin'),
			    'controller' => $this->request->getParam('controller'),
			    'action' => $this->request->getParam('action'),
                '0' => 'index',
                '1' => $encodedQueryString,
			]);
			$template = $this->ControllerAction->getFormTemplate();
			$this->Form->templates($template);
//            dd($yearsOptions);
            if (!empty($yearsOptions)) {
                    echo $this->Form->input('academic_period_id', array(
                    'type' => 'select',
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $yearsOptions,
                    'default' => $selectedYear,
                    'url' => $baseUrl,
                    'data-named-key' => 'academic_period_id',
                    'data-named-group' => 'asset_type_id,accessibility'
                ));
            }

		?>
		</div>
	</div>
