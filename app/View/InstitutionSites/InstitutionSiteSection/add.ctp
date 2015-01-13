<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('../js/plugins/icheck/skins/minimal/blue', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/tableCheckable/jquery.tableCheckable', false);
echo $this->Html->script('plugins/icheck/jquery.icheck.min', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Add Section'));

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'index', $selectedAcademicPeriod), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('action' => $model, 'add', $selectedAcademicPeriod));
$labelOptions = $formOptions['inputDefaults']['label'];

echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('institution_site_id', array('value' => $institutionSiteId));
echo $this->Form->input('academic_period_id', array(
	'options' => $academicPeriodOptions, 
	'url' => $this->params['controller'] . '/' . $model . '/add',
	'default' => $selectedAcademicPeriod,
	'onchange' => 'jsForm.change(this)'
));
echo $this->Form->input('name');

$labelOptions['text'] = $this->Label->get('InstitutionSiteClass.shift');
echo $this->Form->input('institution_site_shift_id', array('options' => $shiftOptions, 'label' => $labelOptions));

?>

<div class="form-group">
	<label class="col-md-3 control-label"><?php echo $this->Label->get('EducationGrade.name'); ?></label>
	<div class="col-md-8">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered table-checkable table-input">
				<thead>
					<tr>
						<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
						<th><?php echo $this->Label->get('EducationProgramme.name'); ?></th>
						<th><?php echo $this->Label->get('EducationGrade.name'); ?></th>
					</tr>
				</thead>
				
				<tbody>
					<?php 
					$i = 0;
					foreach($grades as $obj) :
					?>
					<tr>
						<td class="checkbox-column">
							<?php
							echo $this->Form->hidden('InstitutionSiteSectionGrade.' . $i . '.education_grade_id', array('value' => $obj['EducationGrade']['id']));
							echo $this->Form->checkbox('InstitutionSiteSectionGrade.' . $i++ . '.status', array('class' => 'icheck-input'));
							?>
						</td>
						<td><?php echo $obj['EducationProgramme']['name']; ?></td>
						<td><?php echo $obj['EducationGrade']['name']; ?></td>
					</tr>
					<?php endforeach; ?>
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
