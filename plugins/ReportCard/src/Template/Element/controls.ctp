<?php if (!empty($academicPeriodOptions)||!empty($reportCardStatusOptions)||!empty($areaOptions)||!empty($institutionOptions)||!empty($EducationGradeOptions)): ?>
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
                 if (!empty($reportCardStatusOptions)) {
                    echo $this->Form->input('status', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $reportCardStatusOptions,
                        'default' => $selectedReportStatus,
                        'url' => $baseUrl,
                        'data-named-key' => 'status',

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
                        'data-named-group' => 'status,area_id'
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
                        'data-named-group' => 'status,area_id,institution_id'
                                       ));
                }
                 if (!empty($EducationGradeOptions)) {
                    echo $this->Form->input('education_grade_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $EducationGradeOptions,
                        'default' =>$selectedEducationGrade,
                        'url' => $baseUrl,
                        'data-named-key' => 'education_grade_id',
                        'data-named-group' => 'status,area_id,institution_id,education_grade_id'
                    ));
                }
            ?>
        </div>
    </div>
<?php endif ?>
