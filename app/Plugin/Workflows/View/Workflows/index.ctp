<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
if ($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'add'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.name'); ?></th>
				<th><?php echo $this->Label->get('general.code'); ?></th>
			</tr>
		</thead>

		<tbody>
			<?php foreach($data as $obj) : ?>
			<tr>
				<td><?php echo $this->Html->link($obj[$model]['name'], array('action' => 'view', $obj[$model]['id'])); ?></td>
				<td><?php echo $obj[$model]['code']; ?></td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>
<?php $this->end(); ?>
