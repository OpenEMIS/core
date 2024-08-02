<?php if (!empty($periodOptions) || !empty($levelOptions)) : ?>
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

                if (!empty($levelOptions)) {
                    echo $this->Form->input('education_level_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $levelOptions,
                        'default' => $selectedLevel,
                        'url' => $baseUrl,
                        'data-named-key' => 'level',
                        'data-named-group' => 'period'
                    ));
                }

                echo $this->Form->input('education_programme_id', array(
                    'type' => 'select',
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $programmeOptions,
                    'default' => $selectedProgramme,
                    'url' => $baseUrl,
                    'data-named-key' => 'programme',
                    'data-named-group' => 'period, level'
                ));

            ?>
        </div>
    </div>
<?php endif ?>
