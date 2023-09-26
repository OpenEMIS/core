<?php if (!empty($academicPeriodOptions) || !empty($examinationOptions)) : ?>
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

                if (!empty($academicPeriodOptions)) {
                    echo $this->Form->input('academic_period', array(
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $academicPeriodOptions,
                        'default' => $selectedAcademicPeriod,
                        'url' => $baseUrl,
                        'data-named-key' => 'academic_period_id'
                    ));
                }

                if (!empty($examinationOptions)) {
                    echo $this->Form->input('examination', array(
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $examinationOptions,
                        'default' => $selectedExamination,
                        'url' => $baseUrl,
                        'data-named-key' => 'examination_id',
                        'data-named-group' => 'academic_period_id'
                    ));
                }
            ?>
        </div>
    </div>
<?php endif ?>
