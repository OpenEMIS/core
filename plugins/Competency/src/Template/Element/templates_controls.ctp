<?php if (!empty($periodOptions) || !empty($templateOptions)) : ?>
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

                if (!empty($periodOptions)) {
                    echo $this->Form->input('periods', array(
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $periodOptions,
                        'default' => $selectedPeriod,
                        'url' => $baseUrl,
                        'data-named-key' => 'period'
                    ));
                }

                // if (!empty($templateOptions)) {
                //     echo $this->Form->input('templates', array(
                //         'class' => 'form-control',
                //         'label' => false,
                //         'options' => $templateOptions,
                //         'default' => $selectedTemplate,
                //         'url' => $baseUrl,
                //         'data-named-key' => 'template',
                //         'data-named-group' => 'period'
                //     ));
                // }
            ?>
        </div>
    </div>
<?php endif ?>