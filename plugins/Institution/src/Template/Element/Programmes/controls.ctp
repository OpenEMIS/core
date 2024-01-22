<?php if (!empty($academicPeriodOptions)) : ?>
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
                if (!empty($educationGradesOptions)) {
					echo $this->Form->input('education_grade', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $educationGradesOptions,
                        'default' => $selectedEducationGrades,
						'url' => $baseUrl,
						'data-named-key' => 'education_grade_id',
						'data-named-group' => 'status_id,academic_period_id'
					));
				}  //POCOR-8092
            ?>
        </div>
    </div>
<?php endif ?>
