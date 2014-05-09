<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

echo $this->Html->script('institution_site_results', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Results'));

$this->start('contentActions');
if ($_edit) {
	echo $this->Html->link(__('Back'), array('action' => 'classesView', $classId), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
?>

<div id="assessment" class="content_wrapper">
	<?php foreach ($data as $obj) { ?>
		<fieldset class="section_group">
			<legend><?php echo $obj['name']; ?></legend>

			<table class="table table-striped table-hover table-bordered" action="InstitutionSites/classesResults/<?php echo $classId ?>/">
				<thead>
					<tr>
						<th class="table_cell"><?php echo __('Grade'); ?></th>
						<th class="table_cell"><?php echo __('Code'); ?></th>
						<th class="table_cell"><?php echo __('Name'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($obj['items'] as $item) { ?>
						<tr row-id="<?php echo $item['id']; ?>">
							<td class="table_cell"><?php echo $item['grade']; ?></td>
							<td class="table_cell"><?php echo $item['code']; ?></td>
							<td class="table_cell"><?php echo $this->Html->link($item['name'], array('action' => 'classesResults', $classId, $item['id']), array('escape' => false)); ?></td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</fieldset>
	<?php } ?>
</div>
<?php $this->end(); ?>