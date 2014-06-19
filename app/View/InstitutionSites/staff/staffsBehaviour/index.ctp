<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('institution_site_classes', false);

$this->extend('/Elements/layout/container');
$this->assign('contentId', 'staffBehaviour');
$this->assign('contentHeader', __('List of Behaviour'));
$this->start('contentActions');
echo $this->Html->link(__('Back'), array('controller' => 'InstitutionSites', 'action' => 'staffView', $id), array('class' => 'divider'));
if ($_add) {
	echo $this->Html->link(__('Add'), array('action' => 'staffsBehaviourAdd', $id), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->Form->create('InstitutionSite', array(
	'url' => array('controller' => 'InstitutionSites', 'action' => 'staffsBehaviour'),
	'inputDefaults' => array('label' => false, 'div' => false)
));
?>

<table class="table table-striped table-hover table-bordered" action="InstitutionSites/staffBehaviourView/">
	<thead class="table_head">
		<tr>
			<th class="table_cell cell_behaviour_date"><?php echo __('Date'); ?></th>
            <th class="table_cell cell_behaviour_category"><?php echo __('Category'); ?></th>
            <th class="table_cell cell_behaviour_title"><?php echo __('Title'); ?></th>
			<th class="table_cell"><?php echo __('Insitution Site'); ?></th>
		</tr>
	</thead>

	<tbody class="table_body">
		<?php
		foreach ($data as $id => $obj) {
			$i = 0;
			?>
			<tr class="table_row" row-id="<?php echo $obj['StaffBehaviour']['id']; ?>">
				<td class="table_cell center"><?php echo $this->Utility->formatDate($obj['StaffBehaviour']['date_of_behaviour']); ?></td>
				<td class="table_cell"><?php echo $obj['StaffBehaviourCategory']['name']; ?></td>
				<td class="table_cell"><?php echo $this->Html->link($obj['StaffBehaviour']['title'], array('action' => 'staffsBehaviourView', $obj['StaffBehaviour']['id'])); ?></td>
				<td class="table_cell"><?php echo $obj['InstitutionSite']['name']; ?></td>
			</tr>
<?php } ?>
	</tbody>
</table>
<?php $this->end(); ?>