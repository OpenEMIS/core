<?php if (!empty($academicPeriodOptions) || !empty($examinationOptions) || !empty($subjectOptions)) : ?>
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

                if (!empty($subjectOptions)) {
                    echo $this->Form->input('education_subject', array(
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $subjectOptions,
                        'default' => $selectedSubject,
                        'url' => $baseUrl,
                        'data-named-key' => 'education_subject_id',
                        'data-named-group' => 'academic_period_id,examination_id'
                    ));
                }
            ?>
        </div>
    </div>
<?php endif ?>
