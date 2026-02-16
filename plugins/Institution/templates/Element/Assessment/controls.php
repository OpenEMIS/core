<?php if (!empty($periodOptions) || !empty($assessmentOptions)) : ?>
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
			<?php   if (!empty($periodOptions)) { ?>
                    <?php   echo $this->Form->input('academic_period', array(
						'type' => 'select',
						'class' => 'form-control',
						'label' => false,
						'options' => $periodOptions,
						'default' => $selectedPeriod,
						'url' => $baseUrl,
						'data-named-key' => 'academic_period_id'
					)); ?>
            <?php   } ?> 
            <?php   if (!empty($assessmentOptions)) { ?>
                    <?php   echo $this->Form->input('assessment', array(
						'type' => 'select',
						'class' => 'form-control',
						'label' => false,
						'options' => $assessmentOptions,
						'default' => $selectedAssessment,
						'url' => $baseUrl,
						'data-named-key' => 'assessment_id',
						'data-named-group' => 'academic_period_id'
					)); ?>
            <?php   } ?> 
            <?php   if (!empty($AssessmentPeriodsOptions)) { ?>
                    <?php  echo $this->Form->input('assessment', array(
						'type' => 'select',
						'class' => 'form-control',
						'label' => false,
						'options' => $AssessmentPeriodsOptions,
						'default' => $selectedAssessmentPeriods,
						'url' => $baseUrl,
						'data-named-key' => 'assessment_period_id',
						'data-named-group' => 'assessment_id'
					)); ?>
            <?php   } ?> 
            <?php   if (!empty($classOptions)) { ?>
                    <?php  echo $this->Form->input('assessment', array(
						'type' => 'select',
						'class' => 'form-control',
						'label' => false,
						'options' => $classOptions,
						'default' => $selectedClassId,
						'url' => $baseUrl,
						'data-named-key' => 'institution_class_id',
						'data-named-group' => 'academic_period_id'
					)); ?>
            <?php   } ?> 	
			<?php
				// if (!empty($subjectOptions)) {
				// 	echo $this->Form->input('assessment', array(
				// 		'class' => 'form-control',
				// 		'label' => false,
				// 		'options' => $subjectOptions,
				// 		'default' => $selectedSubject,
				// 		'url' => $baseUrl,
				// 		'data-named-key' => 'education_subject_id',
				// 		'data-named-group' => 'academic_period_id'
				// 	));
				// }
			?>
		</div>
	</div>
<?php endif ?>
