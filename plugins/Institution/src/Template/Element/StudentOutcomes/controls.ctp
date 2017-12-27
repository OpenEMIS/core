<?php if (!empty($periodOptions) || !empty($outcomeOptions)) : ?>
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
                    echo $this->Form->input('period', array(
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $periodOptions,
                        'default' => $selectedPeriod,
                        'url' => $baseUrl,
                        'data-named-key' => 'period'
                    ));
                }

                if (!empty($outcomeOptions)) {
                    echo $this->Form->input('outcome', array(
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $outcomeOptions,
                        'default' => $selectedOutcome,
                        'url' => $baseUrl,
                        'data-named-key' => 'outcome',
                        'data-named-group' => 'period'
                    ));
                }
            ?>
        </div>
    </div>
<?php endif ?>
