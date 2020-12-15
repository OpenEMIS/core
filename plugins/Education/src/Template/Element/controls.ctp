<?php if (!empty($academicPeriodOptions) || !empty($systemOptions) || !empty($levelOptions) || !empty($cycleOptions) || !empty($programmeOptions) || !empty($gradeOptions) || !empty($setupOptions)) : ?>
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
                    echo $this->Form->input('academic_period', array(
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $academicPeriodOptions,
                        'default' => $selectedAcademicPeriod,
                        'url' => $baseUrl,
                        'data-named-key' => 'academic_period_id'
                    ));
                }

				if (!empty($systemOptions)) {
					echo $this->Form->input('systems', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $systemOptions,
						'default' => $selectedSystem,
						'url' => $baseUrl,
						'data-named-key' => 'system',
						'data-named-group' => 'academic_period_id'
					));
				}

				if (!empty($levelOptions)) {
					echo $this->Form->input('levels', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $levelOptions,
						'default' => $selectedLevel,
						'url' => $baseUrl,
						'data-named-key' => 'level'
					));
				}

				if (!empty($cycleOptions)) {
					echo $this->Form->input('cycles', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $cycleOptions,
						'default' => $selectedCycle,
						'url' => $baseUrl,
						'data-named-key' => 'cycle',
						'data-named-group' => 'level'
					));
				}

				if (!empty($programmeOptions)) {
					echo $this->Form->input('programmes', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $programmeOptions,
						'default' => $selectedProgramme,
						'url' => $baseUrl,
						'data-named-key' => 'programme',
						'data-named-group' => 'level'
					));
				}

				if (!empty($gradeOptions)) {
					echo $this->Form->input('setups', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $gradeOptions,
						'default' => $selectedGrade,
						'url' => $baseUrl,
						'data-named-key' => 'grade',
						'data-named-group' => 'level,programme'
					));
				}

				if (!empty($setupOptions)) {
					echo $this->Form->input('setups', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $setupOptions,
						'url' => $baseUrl,
						'data-named-key' => 'setup'
					));
				}
			?>
		</div>
	</div>
<?php endif ?>
