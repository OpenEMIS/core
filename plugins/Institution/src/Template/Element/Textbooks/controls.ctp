<?php if (!empty($periodOptions) && !empty($gradeOptions)) : ?>
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

                if (!empty($periodOptions)) {
                    echo $this->Form->input('academic_period_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $periodOptions,
                        'default' => $selectedPeriod,
                        'url' => $baseUrl,
                        'data-named-key' => 'period'
                    ));
                }

                if (!empty($gradeOptions)) {
                    echo $this->Form->input('education_grade_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $gradeOptions,
                        'default' => $selectedGrade,
                        'url' => $baseUrl,
                        'data-named-key' => 'grade',
                        'data-named-group' => 'period'
                    ));
                }

                echo $this->Form->input('education_subject_id', array(
                    'type' => 'select',
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $subjectOptions,
                    'default' => $selectedSubject,
                    'url' => $baseUrl,
                    'data-named-key' => 'subject',
                    'data-named-group' => 'period,grade'
                ));
                
                echo $this->Form->input('textbook_id', array(
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $textbookOptions,
                    'default' => $selectedTextbook,
                    'url' => $baseUrl,
                    'data-named-key' => 'textbook',
                    'data-named-group' => 'period,grade,subject'
                ));
                
            ?>
        </div>
    </div>
<?php endif ?>
