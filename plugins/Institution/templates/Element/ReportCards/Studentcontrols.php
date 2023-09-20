<?php if (!empty($academicPeriodOptions) || !empty($reportCardOptions) || !empty($institutionOptions)) : ?>
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
                    echo $this->Form->input('student_profile_template_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $reportCardOptions,
                        'default' => $selectedReportCard,
                        'url' => $baseUrl,
                        'data-named-key' => 'student_profile_template_id',
                        'data-named-group' => 'academic_period_id'
                    ));
                }

                if (!empty($areaLevelOptions)) {
                    echo $this->Form->input('area_level_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $areaLevelOptions,
                        'default' => $selectedAreaLevel,
                        'url' => $baseUrl,
                        'data-named-key' => 'area_level_id',
                        'data-named-group' => 'academic_period_id,student_profile_template_id'
                    ));
                }
				
				if (!empty($areaOptions)) {
                    echo $this->Form->input('area_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $areaOptions,
                        'default' => $selectedArea,
                        'url' => $baseUrl,
                        'data-named-key' => 'area_id',
                        'data-named-group' => 'academic_period_id,student_profile_template_id, area_level_id'
                    ));
                }

                if (!empty($institutionOptions)) {
                    echo $this->Form->input('institution_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $institutionOptions,
                        'default' => $selectedInstitution,
                        'url' => $baseUrl,
                        'data-named-key' => 'institution_id',
                        'data-named-group' => 'academic_period_id,student_profile_template_id,area_id, area_level_id'
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
                        'data-named-group' => 'academic_period_id,student_profile_template_id,area_id,institution_id, area_level_id'
                    ));
                }
            ?>
        </div>
    </div>
<?php endif ?>
