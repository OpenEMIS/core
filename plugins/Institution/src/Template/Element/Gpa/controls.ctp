<?php if (!empty($academicPeriodOptions) || !empty($educationGradeOptions) || !empty($classOptions)) : ?>
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

                if (!empty($educationGradeOptions)) {
                    echo $this->Form->input('education_grade_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $educationGradeOptions,
                        'default' => $selectedGrade,
                        'url' => $baseUrl,
                        'data-named-key' => 'education_grade_id',
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
                        'data-named-group' => 'academic_period_id,education_grade_id'
                    ));
                }

                if (!empty($nameOption)) {
                    echo $this->Form->input('gpa_name', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $nameOption,
                        'default' => $selectedName,
                        'url' => $baseUrl,
                        'data-named-key' => 'gpa_name',
                        'data-named-group' => 'academic_period_id,education_grade_id,class_id'
                    ));
                }
            ?>
        </div>
    </div>
<?php endif ?>
