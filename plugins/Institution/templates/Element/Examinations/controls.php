<?php if (!empty($periodOptions)) : ?>
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
