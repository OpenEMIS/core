<?php

echo $this->Html->css('../js/plugins/icheck/skins/minimal/blue', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/tableCheckable/jquery.tableCheckable', false);
echo $this->Html->script('plugins/icheck/jquery.icheck.min', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->data[$model]['name']);

$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => $_action . 'View', $this->data[$model]['id']), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $_action . 'Edit', $this->data[$model]['id']));
$labelOptions = $formOptions['inputDefaults']['label'];

echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');

echo $this->Form->hidden('school_year_id');
?>
<fieldset class="section_break">
	<legend><?php echo __('Class'); ?></legend>
<?php
$labelOptions['text'] = $this->Label->get('general.academic_period');
echo $this->Form->input('year', array('value' => $this->data['SchoolYear']['name'], 'disabled' => 'disabled', 'label' => $labelOptions));

$labelOptions['text'] = $this->Label->get('general.section');
echo $this->Form->input('section', array('label' => $labelOptions, 'disabled' => 'disabled'));

$labelOptions['text'] = $this->Label->get('general.subject');
echo $this->Form->input('subject', array('label' => $labelOptions, 'disabled' => 'disabled', 'value' => __('English')));

$labelOptions['text'] = $this->Label->get('general.class');
echo $this->Form->input('name', array('label' => $labelOptions));

$labelOptions['text'] = $this->Label->get('InstitutionSiteClass.seats');
echo $this->Form->input('no_of_seats', array('label' => $labelOptions));
?>
</fieldset>
<fieldset class="section_break form">
	<legend><?php echo __('Teachers'); ?></legend>
	<div class="row">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th><?php echo $this->Label->get('general.openemisId'); ?></th>
						<th><?php echo $this->Label->get('general.teacher'); ?></th>
						<th class="cell-delete"></th>
					</tr>
				</thead>

				<tbody>
			<?php foreach($staffData as $obj) { ?>
					<tr>
						<td><?php echo $obj['Staff']['identification_no']; ?></td>
						<td><?php echo $obj['Staff']['first_name'] . ' ' . $obj['Staff']['last_name']; ?></td>
						<td><span class="icon_delete" title="<?php echo $this->Label->get('general.delete') ?>" onclick="jsTable.doRemove(this)"></span></td>
					</tr>
			<?php } ?>
				</tbody>
			</table>
			<a class="void icon_plus" url="" onclick="">
				<?php echo $this->Label->get('general.add'); ?>
			</a>
		</div>
	</div>
</fieldset>
<fieldset class="section_break form">
	<legend><?php echo __('Students'); ?></legend>
	<div class="row">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th><?php echo $this->Label->get('general.openemisId'); ?></th>
						<th><?php echo $this->Label->get('general.student'); ?></th>
						<th><?php echo $this->Label->get('general.sex'); ?></th>
						<th><?php echo $this->Label->get('general.date_of_birth'); ?></th>
						<th><?php echo $this->Label->get('general.category'); ?></th>
						<th class="cell-delete"></th>
					</tr>
				</thead>

				<tbody>
			<?php foreach($studentsData as $obj) : ?>
					<tr>
						<td><?php echo $obj['Student']['identification_no']; ?></td>
						<td><?php echo $obj['Student']['first_name'] . ' ' . $obj['Student']['last_name']; ?></td>
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
<?php
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $_action . 'View', $this->data[$model]['id'])));
echo $this->Form->end();

$this->end(); 
?>
