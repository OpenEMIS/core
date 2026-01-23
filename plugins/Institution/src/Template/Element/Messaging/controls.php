	<div class="toolbar-responsive panel-toolbar">
	    <div class="toolbar-wrapper">
	        <?php
            $baseUrl = $this->Url->build([
                'plugin' => $this->request->getParams('plugin'),
                'controller' => $this->request->getParams('controller'),
                'action' => $this->request->getParams('action')
            ]);
            $template = $this->ControllerAction->getFormTemplate();
            $this->Form->templates($template);

            if (!empty($periodOptions)) {
                echo $this->Form->input('academic_period_id', array(
                    'type' => 'select',
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $periodOptions,
                    'default' => $selectedPeriod,
                    'url' => $baseUrl,
                    'data-named-key' => 'period'
                ));
            }

    ?>
	    </div>
	</div>