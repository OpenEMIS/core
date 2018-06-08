<div class="toolbar-responsive panel-toolbar">
    <div class="toolbar-wrapper">
        <?php
            $baseUrl = $this->Url->build([
                'plugin' => $this->request->params['plugin'],
                'controller' => $this->request->params['controller'],
                'action' => $this->request->params['action'],
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
                    'data-named-key' => 'academic_period_id',
                    'data-named-group' => 'institution_committee_type_id'
                ));
            }

            if (!empty($institutionCommitteeTypeOptions)) {
                echo $this->Form->input('institution_committee_type', array(
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $institutionCommitteeTypeOptions,
                    'default' => $selectedInstitutionCommitteeType,
                    'url' => $baseUrl,
                    'data-named-key' => 'institution_committee_type_id',
                    'data-named-group' => 'academic_period_id'
                ));
            }
        ?>
    </div>
</div>
