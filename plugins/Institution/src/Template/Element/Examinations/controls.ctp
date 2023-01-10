<?php if (!empty($periodOptions)) : ?>
    <div class="toolbar-responsive panel-toolbar">
        <div class="toolbar-wrapper">
            <?php
                $baseUrl = $this->Url->build([
                    'plugin' => $this->request->params['plugin'],
                    'controller' => $this->request->params['controller'],
                    'action' => $this->request->params['action'],
                    'index'
                ]);

                $template = $this->ControllerAction->getFormTemplate();
                $this->Form->templates($template);

                echo $this->Form->input('academic_period', array(
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $periodOptions,
                    'default' => $selectedPeriod,
                    'url' => $baseUrl,
                    'data-named-key' => 'academic_period_id'
                ));
            ?>
        </div>
    </div>
<?php endif ?>
