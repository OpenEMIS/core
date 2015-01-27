<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('../js/plugins/icheck/skins/minimal/blue', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/tableCheckable/jquery.tableCheckable', false);
echo $this->Html->script('plugins/icheck/jquery.icheck.min', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Add Section'));

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'index', $selectedYear), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
echo $this->element('../InstitutionSites/InstitutionSiteSection/tabs', array());

$formOptions = $this->FormUtility->getFormOptions(array('action' => $model, 'add', $selectedYear));
$labelOptions = $formOptions['inputDefaults']['label'];

echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('institution_site_id', array('value' => $institutionSiteId));

$labelOptions['text'] = $this->Label->get('general.academic_period');
echo $this->Form->input('school_year_id', array(
	'options' => $yearOptions, 
	'url' => $this->params['controller'] . '/' . $model . '/singleGradeAdd',
	'default' => $selectedYear,
	'onchange' => 'jsForm.change(this)',
	'label' => $labelOptions
));
echo $this->Form->input('education_grade_id', array(
	'options' => $gradeOptions, 
	'url' => $this->params['controller'] . '/' . $model . '/singleGradeAdd/' . $selectedYear . '/',
	'onchange' => 'jsForm.change(this)'
));
//echo $this->Form->input('name');

$labelOptions['text'] = $this->Label->get('InstitutionSiteClass.shift');
echo $this->Form->input('institution_site_shift_id', array('options' => $shiftOptions, 'label' => $labelOptions));

echo $this->Form->input('number_of_sections', array(
	'options' => $numberOfSections, 
	'value' => 2
));

?>
<div class="form-group">
	<label class="col-md-3 control-label"></label>
	<div class="col-md-8">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered table-checkable table-input">
				<thead>
					<tr>
						<th><?php echo $this->Label->get('general.section'); ?></th>
						<th><?php echo $this->Label->get('InstitutionSiteSection.home_room_teacher'); ?></th>
					</tr>
				</thead>
				
				<tbody>
					<?php 
					for($i=0; $i<3; $i++) :
					?>
					<tr>
						<td><?php echo $this->Form->input(sprintf('InstitutionSection.%d.name', $i), array('label' => false, 'div' => false, 'between' => false, 'after' => false)); ?></td>
						<td><?php 
						echo $this->Form->input(sprintf('InstitutionSiteStaff.%d.id', $i), array(
							'options' => $staffOptions, 
							'label' => false,
							'div' => false,
							'between' => false,
							'after' => false
						));
						?></td>
					</tr>
					<?php endfor; ?>
				</tbody>
			</table>
		</div>

	</div>
</div>

<?php
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $model, 'index', $selectedYear)));
echo $this->Form->end();

$this->end(); 
?>
