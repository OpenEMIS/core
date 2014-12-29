<?php
echo $this->Html->script('/Alerts/js/alerts', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Alerts'));
$this->start('contentActions');
$this->end();

$this->start('contentBody');
?>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.name'); ?></th>
				<th><?php echo $this->Label->get('Alert.threshold'); ?></th>
				<th><?php echo $this->Label->get('general.status'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($data as $obj) : ?>
				<tr>
					<td><?php echo $this->Html->link($obj['Alert']['name'], array('action' => 'view', $obj['Alert']['id'])); ?></td>
					<td><?php echo $obj['Alert']['threshold']; ?></td>
					<td><?php echo $obj['Alert']['status'] == 1 ? __('Active') : __('Inactive') ?></td>
				</tr>

			<?php endforeach ?>
		</tbody>
	</table>
</div>
<?php $this->end(); ?>
