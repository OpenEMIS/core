<?php if (!empty($periodOptions) || !empty($programmeOptions)) : ?>
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

                if (!empty($programmeOptions)) {
                    echo $this->Form->input('education_programme_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $programmeOptions,
                        'default' => $selectedProgramme,
                        'url' => $baseUrl,
                        'data-named-key' => 'programme',
                        'data-named-group' => 'period'
                    ));
                }

                if (!empty($gradeOptions)) {
                    echo $this->Form->input('education_grade_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $gradeOptions,
                        'default' => $selectedGrade,
                        'url' => $baseUrl,
                        'data-named-key' => 'grade',
                        'data-named-group' => 'period,programme'
                    ));
                }

                if (!empty($subjectOptions)) {
                    echo $this->Form->input('education_subject_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $subjectOptions,
                        'default' => $selectedSubject,
                        'url' => $baseUrl,
                        'data-named-key' => 'subject',
                        'data-named-group' => 'period,programme,grade'
                    ));
                }
            ?>
        </div>
    </div>
<?php endif ?>
