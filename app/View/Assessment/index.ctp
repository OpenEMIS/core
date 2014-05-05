<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('assessment', 'stylesheet', array('inline' => false));

echo $this->Html->script('assessment', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('National Assessments'));
$this->start('contentActions');
if($_edit && !empty($data)) {
	echo $this->Html->link(__('Edit'), array('action' => 'indexEdit', $selectedProgramme), array('class' => 'divider'));
}
if($_add) {
	echo $this->Html->link(__('Add'), array('action' => 'assessmentsAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
?>

<?php echo $this->element('alert'); ?>

<div class="filter_wrapper">
	<div class="row edit">
		<label class="control-label col-md-3"><?php echo __('Education Programme'); ?></label>
		<div class="col-md-4">
			<?php
			echo $this->Form->input('education_programme_id', array(
				'id' => 'EducationProgrammeId',
				'label' => false,
				'div' => false,
				'class' => 'default',
				'options' => $programmeOptions,
				'default' => $selectedProgramme,
				'class' => 'form-control',
				'url' => 'Assessment/index/',
				'onchange' => 'Assessment.switchProgramme(this)'
			));
			?>
		</div>
	</div>
</div>

<?php foreach($data as $key => $obj) { ?>
<fieldset class="section_group">
	<legend><?php echo $obj['name']; ?></legend>
	<div class="table-responsive">
	 <table class="table table-striped table-hover table-bordered">
	 	<thead class="table_head">
		 	<tr>
				<td class="table_cell cell_code"><?php echo __('Code'); ?></td>
				<td class="table_cell cell_name"><?php echo __('Name'); ?></td>
				<td class="table_cell"><?php echo __('Description'); ?></td>
			</tr>
		</thead>
		<tbody class="table_body">
			<?php foreach($obj['assessment'][$type] as $item) { ?>
			<tr class="table_row <?php echo $item['visible'] == 0 ? 'inactive' : ''; ?>" row-id="<?php echo $item['id']; ?>">
				<td class="table_cell"><?php echo $item['code']; ?></td>
				<td class="table_cell"><?php echo $this->Html->link($item['name'], array('action' => 'assessmentsView', $item['id']), array('escape' => false)); ?></td>
				<td class="table_cell"><?php echo $item['description']; ?></td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
	</div>
</fieldset>
<?php } ?>

<?php $this->end(); ?>