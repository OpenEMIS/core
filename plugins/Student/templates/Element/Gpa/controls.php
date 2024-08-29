<?php if (!empty($academicPeriodOptions)) : ?>
    <div class="toolbar-responsive panel-toolbar">
        <div class="toolbar-wrapper">
            <?php
                $baseUrl = $this->Url->build([
                    'plugin' => $this->request->getParam('plugin'),
                    'controller' => $this->request->getParam('controller'),
                    'action' => $this->request->getParam('action'),
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
                if (!empty($academicPeriodOptions) || !empty($programmeOptions) ) {
                    echo $this->Form->input('education_programme_id', array(
                    'type' => 'select',
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $programmeOptions,
                    'default' => $selectedProgramme,
                    'url' => $baseUrl,
                    'data-named-key' => 'education_programme_id',
                    'data-named-group' => 'academic_period_id'
                ));
                }

                if (!empty($educationGradeOptions)) {
                    echo $this->Form->input('education_grade_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $gradeOptions,
                        'default' => $selectedGrade,
                        'url' => $baseUrl,
                        'data-named-key' => 'education_grade_id',
                        'data-named-group' => 'academic_period_id,education_programme_id'
                    ));
                }
            ?>
        </div>
    </div>
<?php endif ?>
