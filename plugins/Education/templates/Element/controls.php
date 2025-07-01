<?php if (!empty($academicPeriodOptions) || !empty($systemOptions) || !empty($levelOptions) || !empty($cycleOptions) || !empty($programmeOptions) || !empty($gradeOptions) || !empty($setupOptions)) : ?>
	
<?php 
//POCOR-9225[START]
$this->Html->css('ControllerAction.../plugins/chosen/css/chosen.min.css', ['block' => true]);
$this->Html->script('ControllerAction.../plugins/chosen/js/chosen.jquery.min.js', ['block' => true]);
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    $('.chosenSelect').chosen({
        width: '100%',
        allow_single_deselect: true,
        placeholder_text_single: 'Select an option'
    });
});
</script>
<style>
.chosen-container-single .chosen-single {
    background-color:  #ffffff !important;
    border: 1px solid #ccc;
    font-weight: 390;
    font-size: 12px;
    padding: 6px 10px !important;
    border-radius: 4px !important;
    line-height: 1.4;
    display: flex;
    align-items: center;
	height: 25px !important
}

.chosen-container-single .chosen-single div {
    top: 50%;
    transform: translateY(-50%);
}

.chosen-container {
    width: 100% !important;
}

.form-control.chosenSelect {
    height: auto;
    font-size: 12px;
    font-weight: 300;
}
/* POCOR-9225[END] */
</style>
	<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
			<?php
				$baseUrl = $this->Url->build([
					'plugin' => $this->request->getParam('plugin'),
				    'controller' => $this->request->getParam('controller'),
				    'action' => $this->request->getParam('action')
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
						'data-named-key' => 'level',
						'data-named-group' => 'academic_period_id'
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
						'data-named-group' => 'level,academic_period_id'
					));
				}

				if (!empty($programmeOptions)) {
					echo $this->Form->input('programmes', array(
						'class' => 'form-control chosenSelect', //POCOR-9225
						'label' => false,
						'options' => $programmeOptions,
						'default' => $selectedProgramme,
						'url' => $baseUrl,
						'data-named-key' => 'programme',
						'data-named-group' => 'level,academic_period_id'
					));
				}

				if (!empty($gradeOptions)) {
					echo $this->Form->input('grades', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $gradeOptions,
						'default' => $selectedGrade,
						'url' => $baseUrl,
						'data-named-key' => 'grade',
						'data-named-group' => 'level,programme,academic_period_id'
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
