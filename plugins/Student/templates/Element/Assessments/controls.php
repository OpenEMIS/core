<?php if (!empty($academicPeriodOptions) || !empty($institutionOptions)): ?>
    <div class="toolbar-responsive panel-toolbar">
        <div class="toolbar-wrapper">
            <?php
            $baseUrl = $this->Url->build([
                'plugin' => $this->request->getParam('plugin'),
                'controller' => $this->request->getParam('controller'),
                'action' => $this->request->getParam('action'),
                '0' => 'index',
                '1' => $encodedQueryString,

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
                ));
            }

            if (!empty($assessmentOptions)) {
                echo $this->Form->input('assessment', array(
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $assessmentOptions,
                    'default' => $selectedAssessment,
                    'url' => $baseUrl,
                    'data-named-key' => 'assessment_id',
                    'data-named-group' => 'academic_period_id'
                ));
            }

            if (!empty($AssessmentPeriodsOptions)) {
                echo $this->Form->input('assessment', array(
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $AssessmentPeriodsOptions,
                    'default' => $selectedAssessmentPeriod,
                    'url' => $baseUrl,
                    'data-named-key' => 'assessment_period_id',
                    'data-named-group' => 'assessment_id'
                ));
            }

            ?>
        </div>
    </div>
<?php endif ?>

