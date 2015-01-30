<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('../js/plugins/icheck/skins/minimal/blue', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/tableCheckable/jquery.tableCheckable', false);
echo $this->Html->script('plugins/icheck/jquery.icheck.min', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Add Classes'));

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'index', $selectedAcademicPeriod), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $model , 'add', $selectedAcademicPeriod, $selectedSection));
$labelOptions = $formOptions['inputDefaults']['label'];

echo $this->Form->create($model, $formOptions);

$labelOptions['text'] = $this->Label->get('AcademicPeriod.name');
echo $this->Form->input('InstitutionSiteSection.academic_period_id', array(
	'options' => $academicPeriodOptions, 
	'url' => $this->params['controller'] . '/' . $model . '/add',
	'default' => $selectedAcademicPeriod,
	'onchange' => 'jsForm.change(this)',
	'label' => $labelOptions
));
$labelOptions['text'] = $this->Label->get('general.section');
echo $this->Form->input('InstitutionSiteSection.section_id', array(
	'options' => $sectionOptions, 
	'url' => $this->params['controller'] . '/' . $model . '/add/' . $selectedAcademicPeriod,
	'default' => $selectedSection,
	'onchange' => 'jsForm.change(this)',
	'label' => $labelOptions
));
?>

<div class="form-group">
	<label class="col-md-3 control-label"><?php echo $this->Label->get('general.classes'); ?></label>
	<div class="col-md-8">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered table-checkable table-input">
				<thead>
					<tr>
						<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
						<th><?php echo $this->Label->get('general.subject'); ?></th>
						<th><?php echo $this->Label->get('general.class'); ?></th>
						<th><?php echo $this->Label->get('general.teacher'); ?></th>
					</tr>
				</thead>
				
				<tbody>
					<?php 
					$i = 0;
					foreach($subjectData as $subjectId => $subject) :
						// need to check if there is already an existing class
						$subjectClassExists = array_key_exists($subjectId, $classesBySectionBySubjectId);

						echo $this->Form->hidden($i. '.InstitutionSiteClass' . '.institution_site_id', array('value' => $institutionSiteId));
						echo $this->Form->hidden($i. '.InstitutionSiteClass' . '.education_subject_id', array('value' => $subjectId));
						echo $this->Form->hidden($i. '.InstitutionSiteClass' . '.academic_period_id', array('value' => $selectedAcademicPeriod));
						echo $this->Form->hidden($i. '.InstitutionSiteClass' . '.institution_site_section_id', array('value' => $selectedSection));
					?>
					<tr>
						<td class="checkbox-column">
							<?php
							echo $this->Form->checkbox($i. '.InstitutionSiteClass' . '.status', array('class' => 'icheck-input', 'checked' => $subjectClassExists, 'disabled' => $subjectClassExists));
							?>
						</td>
						<td><?php echo $subject; ?></td>
						<td><?php 
						echo $this->Form->input($i. '.InstitutionSiteClass' . '.name', array(
							'label' => false,
							'div' => false,
							'between' => false,
							'after' => false,
							'value' => ($subjectClassExists)? $classesBySectionBySubjectId[$subjectId]['InstitutionSiteClass']['name']:$subject,
							'disabled' => $subjectClassExists
						));
						?></td>
						<td><?php 
						echo $this->Form->hidden($i. '.InstitutionSiteClassStaff.0.status', array(
							'value' => 1));

						if (!$subjectClassExists) {
							echo $this->Form->input($i. '.InstitutionSiteClassStaff.0.staff_id', array(
								'options' => $staffOptions, 
								'label' => false,
								'div' => false,
								'between' => false,
								'after' => false,
								'disabled' => $subjectClassExists
							));
						} else {
							$currStaff = array();
							foreach ($classesBySectionBySubjectId[$subjectId]['InstitutionSiteClass']['InstitutionSiteClassStaff'] as $staffKey => $staffValue) {
								array_push($currStaff, $staffValue['staffName']);
							}
							echo implode(', <br>', $currStaff);
						}
						?></td>
					</tr>
					<?php 
					$i++;
					endforeach; 
					?>
				</tbody>
			</table>
		</div>

	</div>
</div>

<?php
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $model, 'index', $selectedAcademicPeriod)));
echo $this->Form->end();

$this->end(); 
?>
