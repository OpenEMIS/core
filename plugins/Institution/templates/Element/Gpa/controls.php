<?php if (!empty($academicPeriodOptions) || !empty($educationGradeOptions) || !empty($classOptions)) : ?>
    <div class="toolbar-responsive panel-toolbar">
        <div class="toolbar-wrapper">
            <?php
                $baseUrl = $this->Url->build([
                    'plugin' => $this->request->getParam('plugin'),
                    'controller' => $this->request->getParam('controller'),
                    'action' => $this->request->getParam('action'),
                    0 => 'index',
                    1 => $encodedQueryString,
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
                        'default' => $education_grade_id,
                        'url' => $baseUrl,
                        'data-named-key' => 'education_grade_id',
                        'data-named-group' => 'academic_period_id'
                    ));
                }

                if (!empty($classOptions)) {
                    // Check if the institution_class_id is set in the request

                    echo $this->Form->input('class_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $classOptions,
                        'default' => $institution_class_id,
                        'url' => $baseUrl,
                        'data-named-key' => 'institution_class_id',
                        'data-named-group' => 'academic_period_id,education_grade_id'
                    ));
                }

                if (!empty($gpaOptions)) {

                    echo $this->Form->input('gpa_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $gpaOptions,
                        'default' => $gpa_id,
                        'url' => $baseUrl,
                        'data-named-key' => 'gpa_id',
                        'data-named-group' => 'academic_period_id,education_grade_id,institution_class_id'
                    ));
                }
            ?>
        </div>
    </div>
<?php endif ?>
