<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Shifts'));

$this->start('contentActions');
	if ($_add) {
		echo $this->Html->link(__('Add'), array('action' => 'shiftsAdd'), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo __('Year'); ?></th>
				<th><?php echo __('Shift'); ?></th>
				<th><?php echo __('Period'); ?></th>
				<th><?php echo __('Location'); ?></th>
			</tr>
		</thead>
	
		<tbody>
			<?php foreach ($data as $obj): ?>
				<tr>
					<td><?php echo $obj['AcademicPeriod']['name']; ?></td>
					<td><?php echo $this->Html->link($obj['InstitutionSiteShift']['name'], array('action' => 'shiftsView', $obj['InstitutionSiteShift']['id']), array('escape' => false)); ?></td>
					<td><?php echo $obj['InstitutionSiteShift']['start_time']; ?> - <?php echo $obj['InstitutionSiteShift']['end_time']; ?></td>
					<td><?php echo $obj['InstitutionSite']['name']; ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>
