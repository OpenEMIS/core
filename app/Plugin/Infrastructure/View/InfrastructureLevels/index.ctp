<?php 
echo $this->Html->css('/Infrastructure/css/infrastructure', 'stylesheet', array('inline' => false));
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Levels'));
$this->start('contentActions');
if ($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'add', 'parent_id' => $parentId, 'plugin' => false), array('class' => 'divider'));
}
if ($_edit && count($data) > 1) {
	echo $this->Html->link($this->Label->get('general.reorder'), array('action' => 'reorder', 'parent_id' => $parentId, 'plugin' => false), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('nav_tabs');

$breadcrumbOptions = array(
	'breadcrumbs' => $breadcrumbs,
	'rootName' => __('All'),
	'rootUrl' => array('controller' => 'InfrastructureLevels', 'action' => 'index', 'plugin' => false)
);
echo $this->element('breadcrumbs', $breadcrumbOptions);
?>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<tr>
				<th class="cell-visible"><?php echo $this->Label->get('general.visible'); ?></th>
				<th><?php echo $this->Label->get('general.name'); ?></th>
				<th class="cell-action"><?php echo $this->Label->get('general.action'); ?></th>
			</tr>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($data as $obj) : ?>
			<tr>
				<td class="center"><?php echo $this->Utility->checkOrCrossMarker($obj[$model]['visible']==1); ?></td>
				<td><?php echo $this->Html->link($obj[$model]['name'], array('action' => 'index', 'parent_id' => $obj[$model]['id'])); ?></td>
				<td class="center"><?php echo $this->Html->link($this->Icon->get('details'), array('action' => 'view', $obj[$model]['id']), array('escape' => false)); ?></td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>
<?php $this->end(); ?>