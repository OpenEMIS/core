<?php if (!empty($academicPeriodOptions) || !empty($reportCardOptions) || !empty($classOptions)) : ?>
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
                $this->Form->templates($template); ?>

            <?php if (!empty($academicPeriodOptions)) { ?>
                    <?php echo $this->Form->input('academic_period_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $academicPeriodOptions,
                        'default' => $selectedAcademicPeriod,
                        'url' => $baseUrl,
                        'data-named-key' => 'academic_period_id'
                    )); ?>
            <?php  } ?>

            <?php if (!empty($reportCardOptions)) { ?>
                    <?php echo $this->Form->input('class_profile_template_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $reportCardOptions,
                        'default' => $selectedReportCard,
                        'url' => $baseUrl,
                        'data-named-key' => 'class_profile_template_id',
                        'data-named-group' => 'academic_period_id'
                    )); ?>
            <?php  } ?>

            <?php if (!empty($classOptions)) { ?>
                    <?php echo $this->Form->input('class_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $classOptions,
                        'default' => $selectedClass,
                        'url' => $baseUrl,
                        'data-named-key' => 'class_id',
                        'data-named-group' => 'academic_period_id,class_profile_template_id'
                    )); ?>
            <?php  } ?>

            <?php if (!empty($areaLevelOptions)) { ?>
                    <?php echo $this->Form->input('area_level_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $areaLevelOptions,
                        'default' => $selectedAreaLevel,
                        'url' => $baseUrl,
                        'data-named-key' => 'area_level_id',
                        'data-named-group' => 'academic_period_id,class_profile_template_id'
                    )); ?>
            <?php  } ?>
                
            <?php if (!empty($areaOptions)) { ?>
                    <?php echo $this->Form->input('area_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $areaOptions,
                        'default' => $selectedArea,
                        'url' => $baseUrl,
                        'data-named-key' => 'area_id',
                        'data-named-group' => 'academic_period_id,class_profile_template_id, area_level_id'
                    )); ?>
            <?php  } ?>

            <?php if (!empty($institutionOptions)) { ?>
                    <?php echo $this->Form->input('institution_id', array(
                            'type' => 'select',
                            'class' => 'form-control',
                            'label' => false,
                            'options' => $institutionOptions,
                            'default' => $selectedInstitution,
                            'url' => $baseUrl,
                            'data-named-key' => 'institution_id',
                            'data-named-group' => 'academic_period_id,class_profile_template_id,area_id, area_level_id'
                        )); ?>
            <?php  } ?>
        </div>
    </div>
<?php endif ?>
