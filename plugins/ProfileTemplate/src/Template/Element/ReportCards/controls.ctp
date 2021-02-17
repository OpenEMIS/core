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
                    echo $this->Form->input('staff_profile_template_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $reportCardOptions,
                        'default' => $selectedReportCard,
                        'url' => $baseUrl,
                        'data-named-key' => 'staff_profile_template_id',
                        'data-named-group' => 'academic_period_id'
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
                        'data-named-group' => 'academic_period_id,staff_profile_template_id'
                    ));
                }
            ?>
        </div>
    </div>
<?php endif ?>
