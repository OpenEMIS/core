<?php if (!empty($academicPeriodOptions)||!empty($reportCardStatusOptions)||!empty($areaOptions)||!empty($institutionOptions)) : ?>
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
                        'data-named-group' => 'academic_period_id,staff_profile_template_id'
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
                        'data-named-group' => 'academic_period_id,staff_profile_template_id'
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
                        'data-named-group' => 'academic_period_id,staff_profile_template_id,area_id'
                    ));
                }
                 if (!empty($institutionGradeOptions)) {
                    echo $this->Form->input('institution_grade', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $institutionGradeOptions,
                        'default' => $selectedInstitutionGrade,
                        'url' => $baseUrl,
                        'data-named-key' => 'institution_grade',
                        'data-named-group' => 'academic_period_id,staff_profile_template_id,area_id'
                    ));
                }
            ?>
        </div>
    </div>
<?php endif ?>
