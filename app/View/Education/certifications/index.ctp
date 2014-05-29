<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => $_action.'Add'), array('class' => 'divider'));
}
if ($_edit && count($data) > 1) {
	echo $this->Html->link($this->Label->get('general.reorder'), array('action' => 'reorder', $_action), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('../Education/controls');
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th class="cell-visible"><?php echo $this->Label->get('general.visible'); ?></th>
				<th><?php echo $this->Label->get('general.name'); ?></th>
			</tr>
		</thead>

		<tbody>
			<?php foreach($data as $obj) { ?>
			<tr>
				<td class="center"><?php echo $this->Utility->checkOrCrossMarker($obj[$model]['visible']==1); ?></td>
				<td><?php echo $this->Html->link($obj[$model]['name'], array('action' => $_action.'View', $obj[$model]['id'])); ?></td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>
