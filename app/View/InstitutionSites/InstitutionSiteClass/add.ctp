<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('../js/plugins/icheck/skins/minimal/blue', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/tableCheckable/jquery.tableCheckable', false);
echo $this->Html->script('plugins/icheck/jquery.icheck.min', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Add Classes'));

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => $_action, $selectedAcademicPeriod), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $_action . 'Add', $selectedAcademicPeriod));
$labelOptions = $formOptions['inputDefaults']['label'];

echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('institution_site_id', array('value' => $institutionSiteId));

$labelOptions['text'] = $this->Label->get('AcademicPeriod.name');
echo $this->Form->input('academic_period_id', array(
	'options' => $academicPeriodOptions, 
	'url' => $this->params['controller'] . '/' . $this->action,
	'default' => $selectedAcademicPeriod,
	'onchange' => 'jsForm.change(this)',
	'label' => $labelOptions
));
$labelOptions['text'] = $this->Label->get('general.section');
echo $this->Form->input('institution_site_section_id', array(
	'options' => $sectionOptions, 
	'url' => $this->params['controller'] . '/' . $this->action . '/' . $selectedAcademicPeriod,
	'default' => $selectedSection,
	'onchange' => 'jsForm.change(this)',
	'label' => $labelOptions
));

//echo $this->Form->input('name');

$labelOptions['text'] = $this->Label->get('InstitutionSiteClass.seats');
echo $this->Form->input('no_of_seats', array('label' => $labelOptions));
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
					foreach($subjects as $subject) :
					?>
					<tr>
						<td class="checkbox-column">
							<?php
							echo $this->Form->hidden('InstitutionSiteSectionClass.' . $i . '.institution_site_section_id', array('value' => ''));
							echo $this->Form->checkbox('InstitutionSiteSectionClass.' . $i++ . '.status', array('class' => 'icheck-input'));
							?>
						</td>
						<td><?php echo $subject; ?></td>
						<td><?php 
						echo $this->Form->input('class_name', array(
							'label' => false,
							'div' => false,
							'between' => false,
							'after' => false,
							'value' => $subject
						));
						?></td>
						<td><?php 
						echo $this->Form->input('institution_site_staff_id', array(
							'options' => $staffOptions, 
							'label' => false,
							'div' => false,
							'between' => false,
							'after' => false
						));
						?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

	</div>
</div>

<?php
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $_action, $selectedAcademicPeriod)));
echo $this->Form->end();

$this->end(); 
?>
