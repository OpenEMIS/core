<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('setup_variables', 'stylesheet', array('inline' => false));

echo $this->Html->script('setup_variables', false);
echo $this->Html->script('/Alerts/js/alerts', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Alerts'));
$this->start('contentActions');
if ($_add) {
	echo $this->Html->link(__('Add'), array('action' => 'Alert', 'add'), array('class' => 'divider', 'id' => 'add'));
}
$this->end();

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>
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
					<td><?php echo $this->Html->link($obj[$model]['name'], array('action' => $model, 'view', $obj[$model]['id'])); ?></td>
					<td><?php echo $obj[$model]['threshold']; ?></td>
					<td><?php echo $obj[$model]['status'] == 1 ? __('Active') : __('Inactive') ?></td>
				</tr>

			<?php endforeach ?>
		</tbody>
	</table>
</div>
<?php $this->end(); ?>  