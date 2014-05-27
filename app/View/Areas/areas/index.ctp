<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'areasAdd', 'parent' => $parentId), array('class' => 'divider'));
}
if ($_edit && count($data) > 1) {
	echo $this->Html->link($this->Label->get('general.reorder'), array('action' => 'areasReorder', 'parent' => $parentId), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('../Areas/controls');
echo $this->element('../Areas/breadcrumbs');
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th class="cell-visible"><?php echo $this->Label->get('general.visible'); ?></th>
				<th><?php echo $this->Label->get('general.name'); ?></th>
				<th><?php echo $this->Label->get('general.code'); ?></th>
				<th><?php echo $this->Label->get('AreaLevel.name'); ?></th>
				<th><?php echo $this->Label->get('general.action'); ?></th>
			</tr>
		</thead>

		<tbody>
			<?php foreach($data as $obj) { ?>
			<tr>
				<td class="center"><?php echo $this->Utility->checkOrCrossMarker($obj[$model]['visible']==1); ?></td>
				<td>
					<?php
					if($obj['AreaLevel']['level'] == $maxLevel) {
						echo $obj[$model]['name'];
					} else {
						echo $this->Html->link($obj[$model]['name'], array('action' => $this->action, 'parent' => $obj[$model]['id']));
					}
					?>
				</td>
				<td><?php echo $obj[$model]['code']; ?></td>
				<td><?php echo $obj['AreaLevel']['name']; ?></td>
				<td class="center"><?php echo $this->Html->link('<i class="fa fa-file-o fa-2x">', array('action' => 'areasView', 'parent' => $parentId, $obj[$model]['id']), array('escape' => false)); ?></td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>
