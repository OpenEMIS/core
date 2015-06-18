<div class="toolbar-responsive panel-toolbar">
	<div class="toolbar-wrapper">
		<?php
			$baseUrl = $this->Url->build([
					'plugin' => $this->request->params['plugin'],
				    'controller' => $this->request->params['controller'],
				    'action' => 'Absences',
				]);

			if (!empty($academicPeriodList)) {
				echo $this->Form->input('academic_period_', array(
					'class' => 'form-control',
					'label' => false,
					'options' => $academicPeriodList,
					'url' => $baseUrl,
					'default' => $selectedAcademicPeriod,
					'data-named-key' => 'academic_period',
				));
			}

			if (!empty($monthOptions)) {
				echo $this->Form->input('month', array(
					'class' => 'form-control',
					'label' => false,
					'options' => $monthOptions,
					'url' => $baseUrl,
					'default' => $selectedMonth,
					'data-named-key' => 'month',
				));
			}

		?>
	</div>
</div>
