<?php if (!empty($periodOptions) || !empty($competencyOptions)) : ?>
	<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
			<?php
				$baseUrl = $this->Url->build([
					'plugin' => $this->request->getParam('plugin'),
				    'controller' => $this->request->getParam('controller'),
				    'action' => $this->request->getParam('action')
				]);
				$template = $this->ControllerAction->getFormTemplate();
				$this->Form->templates($template); ?>

				<?php	if (!empty($periodOptions)) { ?>
					<div class="input select required">
		                <div class="input-select-wrapper">
						<?php	echo $this->Form->input('academic_period', array(
							'type' => 'select',
							'class' => 'form-control',
							'label' => false,
							'options' => $periodOptions,
							'default' => $selectedPeriod,
							'url' => $baseUrl,
							'data-named-key' => 'period'
						)); ?>
						</div>
					</div>
				<?php	} ?>

				<?php	if (!empty($competencyOptions)) { ?>
					<div class="input select required">
		                <div class="input-select-wrapper">
						<?php	echo $this->Form->input('assessment', array(
							'type' => 'select',
							'class' => 'form-control',
							'label' => false,
							'options' => $competencyOptions,
							'default' => $selectedCompetency,
							'url' => $baseUrl,
							'data-named-key' => 'competency',
							'data-named-group' => 'period'
						)); ?>
						</div>
					</div>
				<?php	} ?>

				<?php	if (!empty($competencyPeriodsOptions)) { ?>
					<!-- Start 6718 -->
					<div class="input select required">
		                <div class="input-select-wrapper">
						<?php	echo $this->Form->input('assessment', array(
							'type' => 'select',
							'class' => 'form-control',
							'label' => false,
							'options' => $competencyPeriodsOptions,
							'default' => $selectedCompetencyPeriods,
							'url' => $baseUrl,
							'data-named-key' => 'competencyPeriods',
							'data-named-group' => 'competency'
						)); ?>
						</div>
					</div>
					<!-- End 6718 -->
				<?php	} ?>
		</div>
	</div>
<?php endif ?>
