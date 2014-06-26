<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'levelsEducationAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('../Areas/controls');
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.level'); ?></th>
				<th><?php echo $this->Label->get('general.name'); ?></th>
			</tr>
		</thead>

		<tbody>
			<?php foreach($data as $obj) { ?>
			<tr>
				<td><?php echo $obj[$model]['level']; ?></td>
				<td><?php echo $this->Html->link($obj[$model]['name'], array('action' => 'levelsEducationView', $obj[$model]['id'])); ?></td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>
