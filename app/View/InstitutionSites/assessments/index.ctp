<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Assessments'));

$this->start('contentActions');
if ($_execute) {
	echo $this->Html->link($this->Label->get('general.export'), array('action' => 'assessmentsToExcel', $selectedYear), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('templates/year_options', array('url' => 'assessments'));
if(!empty($data)):
?>
<?php foreach ($data as $obj) : ?>
	<fieldset class="section_group">
		<legend><?php echo $obj['name']; ?></legend>

		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo __('Code'); ?></th>
					<th><?php echo __('Name'); ?></th>
					<th><?php echo __('Description'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($obj['items'] as $item) : ?>
					<tr>
						<td><?php echo $item['code']; ?></td>
						<td><?php echo $this->Html->link($item['name'], array('action' => 'assessmentsResults', $selectedYear, $item['id']), array('escape' => false)); ?></td>
						<td><?php echo $item['description']; ?></td>
					</tr>
				<?php endforeach ?>
			</tbody>
		</table>
	</fieldset>
<?php endforeach;
 endif;
?>
<?php $this->end(); ?>
