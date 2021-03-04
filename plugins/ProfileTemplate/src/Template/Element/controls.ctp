<?php if (!empty($academicPeriodOptions) || !empty($reportCardOptions) || !empty($classOptions)) : ?>
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

                if (!empty($academicPeriodOptions)) {
                    echo $this->Form->input('academic_period_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $academicPeriodOptions,
                        'default' => $selectedAcademicPeriod,
                        'url' => $baseUrl,
                        'data-named-key' => 'academic_period_id'
                    ));
                }

                if (!empty($reportCardOptions)) {
                    echo $this->Form->input('report_card_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $reportCardOptions,
                        'default' => $selectedReportCard,
                        'url' => $baseUrl,
                        'data-named-key' => 'report_card_id',
                        'data-named-group' => 'academic_period_id'
                    ));
                }

                if (!empty($classOptions)) {
                    echo $this->Form->input('class_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $classOptions,
                        'default' => $selectedClass,
                        'url' => $baseUrl,
                        'data-named-key' => 'class_id',
                        'data-named-group' => 'academic_period_id,report_card_id'
                    ));
                }
            ?>
        </div>
    </div>
<?php endif ?>
