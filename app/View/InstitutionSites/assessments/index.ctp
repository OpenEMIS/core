<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
//echo $this->Html->script('institution_site_results', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Assessments'));

$this->start('contentActions');
$this->end();

$this->start('contentBody');
echo $this->element('templates/year_options', array('url' => 'assessments'));
?>

<div id="assessment" class="">
	<?php foreach ($data as $obj) { ?>
		<fieldset class="section_group">
			<legend><?php echo $obj['name']; ?></legend>

			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th class="table_cell"><?php echo __('Code'); ?></th>
						<th class="table_cell"><?php echo __('Name'); ?></th>
						<th class="table_cell"><?php echo __('Description'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($obj['items'] as $item) { ?>
						<tr row-id="<?php echo $item['id']; ?>">
							<td class="table_cell"><?php echo $item['code']; ?></td>
							<td class="table_cell"><?php echo $this->Html->link($item['name'], array('action' => 'assessmentsResults', $selectedYear, $item['id']), array('escape' => false)); ?></td>
							<td class="table_cell"><?php echo $item['description']; ?></td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</fieldset>
	<?php } ?>
</div>
<?php $this->end(); ?>