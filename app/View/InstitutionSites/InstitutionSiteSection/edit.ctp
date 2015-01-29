<?php
echo $this->Html->css('../js/plugins/icheck/skins/minimal/blue', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/tableCheckable/jquery.tableCheckable', false);
echo $this->Html->script('plugins/icheck/jquery.icheck.min', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->data[$model]['name']);

$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => $model, 'view', $this->data[$model]['id']), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('action' => $model, 'edit', $this->data[$model]['id']));
$labelOptions = $formOptions['inputDefaults']['label'];

echo $this->Form->create($model, $formOptions);

echo $this->element('edit');
?>
<?php
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $model, 'view', $this->data[$model]['id']), 'reloadBtn' => true));
echo $this->Form->end();

$this->end(); 
?>


<fieldset class="section_break">
	<legend><?php echo __('Section'); ?></legend>
<?php
echo $this->Form->hidden('id');
echo $this->Form->hidden('academic_period_id');

$labelOptions['text'] = $this->Label->get('AcademicPeriod.name');
echo $this->Form->input('academic_period', array('value' => $this->data['AcademicPeriod']['name'], 'disabled' => 'disabled', 'label' => $labelOptions));

?>
<div class="form-group">
	<label class="col-md-3 control-label"><?php echo $this->Label->get('EducationGrade.name'); ?></label>
	<div class="col-md-4 stackElements">
	<?php
	foreach($grades as $g) {
		echo $this->Form->input('grade_name)', array('value' => $g, 'label' => false, 'div' => false, 'disabled' => 'disabled', 'between' => false, 'after' => false));
	}
	?>
	</div>
</div>
<?php
$labelOptions['text'] = $this->Label->get('general.section');
echo $this->Form->input('name', array('label' => $labelOptions));

$labelOptions['text'] = $this->Label->get('InstitutionSiteClass.shift');
echo $this->Form->input('institution_site_shift_id', array('options' => $shiftOptions, 'label' => $labelOptions));

$labelOptions['text'] = $this->Label->get('InstitutionSiteSection.staff_id');
echo $this->Form->input('institution_site_staff_id', array(
	'options' => $staffOptions,
	'label' => $labelOptions
));
?>
</fieldset>
<fieldset class="section_break form">
	<legend><?php echo __('Students'); ?></legend>
	<div class="row">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th><?php echo $this->Label->get('general.openemisId'); ?></th>
						<th><?php echo $this->Label->get('general.name'); ?></th>
						<th><?php echo $this->Label->get('general.sex'); ?></th>
						<th><?php echo $this->Label->get('general.date_of_birth'); ?></th>
						<th><?php echo $this->Label->get('general.category'); ?></th>
						<th class="cell-delete"></th>
					</tr>
				</thead>

				<tbody>
			<?php foreach($studentsData as $i => $obj) : ?>
					<tr>
						<td><?php echo $obj['Student']['identification_no']; ?></td>
						<td><?php echo $obj['Student']['first_name'] . ' ' . $obj['Student']['last_name']; ?></td>
						<td><?php echo ''; ?></td>
						<td><?php echo ''; ?></td>
						<td>
							<?php
							echo $this->Form->input($i . '.student_category_id', array(
								'label' => false,
								'div' => false,
								'before' => false,
								'between' => false,
								'after' => false,
								'options' => $categoryOptions,
								'value' => $obj['InstitutionSiteSectionStudent']['student_category_id']
							));
							?>
						</td>
						<td><span class="icon_delete" title="<?php echo $this->Label->get('general.delete') ?>" onclick="jsTable.doRemove(this)"></span></td>
					</tr>
			<?php endforeach ?>
				</tbody>
			</table>
			<a class="void icon_plus" url="" onclick="">
				<?php echo $this->Label->get('general.add'); ?>
			</a>
		</div>
	</div>
</fieldset>