<?php if (!empty($periodOptions) || !empty($programmeOptions)) : ?>
    <div class="toolbar-responsive panel-toolbar">
        <div class="toolbar-wrapper">
            <?php
                $url = [
                    'plugin' => $this->request->params['plugin'],
                    'controller' => $this->request->params['controller'],
                    'action' => $this->request->params['action']
                ];

                $baseUrl = $this->Url->build($url);
                $template = $this->ControllerAction->getFormTemplate();
                $this->Form->templates($template);

                if (!empty($periodOptions)) {
                    echo $this->Form->input('periods', array(
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $periodOptions,
                        'default' => $selectedPeriodOption,
                        'url' => $baseUrl,
                        'data-named-key' => 'period',
                        'data-named-group' => 'programme'
                    ));
                }

                if (!empty($programmeOptions)) {
                    echo $this->Form->input('periods', array(
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $programmeOptions,
                        'default' => $selectedProgrammeOptions,
                        'url' => $baseUrl,
                        'data-named-key' => 'programme',
                        'data-named-group' => 'period'
                    ));
                }
            ?>
        </div>
    </div>
<?php endif ?>
