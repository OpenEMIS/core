<?php

echo $this->Html->css('table', 'stylesheet', array('inline' => false));
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
?>
<fieldset class="section_break">
	<legend><?php echo __('Section'); ?></legend>
<?php
echo $this->Form->hidden('id');
echo $this->Form->hidden('school_year_id');
?>
<div class="form-group">
	<label class="col-md-3 control-label"><?php echo $this->Label->get('EducationGrade.name'); ?></label>
	<div class="col-md-4 stackElements">
	<?php
	foreach($grades as $g) {
		echo $this->Form->input('grade', array('value' => $g, 'label' => false, 'div' => false, 'disabled' => 'disabled', 'between' => false, 'after' => false));
	}
	?>
	</div>
</div>
<?php
echo $this->Form->input('year', array('value' => $this->data['SchoolYear']['name'], 'disabled' => 'disabled'));
echo $this->Form->input('name');

$labelOptions['text'] = $this->Label->get('InstitutionSiteClass.shift');
echo $this->Form->input('institution_site_shift_id', array('options' => $shiftOptions, 'label' => $labelOptions));

$labelOptions['text'] = $this->Label->get('InstitutionSiteSection.home_room_teacher');
echo $this->Form->input('institution_site_staff_id', array(
	'options' => $staffOptions,
	'label' => $labelOptions
));
?>
</fieldset>
<fieldset class="section_break">
	<legend><?php echo __('Section'); ?></legend>
	<div class="row">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th><?php echo $this->Label->get('general.openemisId'); ?></th>
						<th><?php echo $this->Label->get('general.name'); ?></th>
						<th><?php echo $this->Label->get('general.category'); ?></th>
					</tr>
				</thead>

				<tbody>
			<?php foreach($studentsData as $i => $obj) : ?>
					<tr>
						<td><?php echo $obj['Student']['identification_no']; ?></td>
						<td><?php echo $obj['Student']['first_name'] . ' ' . $obj['Student']['last_name']; ?></td>
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
					</tr>
			<?php endforeach ?>
				</tbody>
			</table>
		</div>
	</div>
</fieldset>
<?php
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $model, 'view', $this->data[$model]['id'])));
echo $this->Form->end();

$this->end(); 
?>
