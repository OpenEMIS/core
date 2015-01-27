<?php 
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Classes'));

$this->start('contentBody');
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.academic_period') ?></th>
				<th><?php echo $this->Label->get('Institution.name') ?></th>
				<th><?php echo $this->Label->get('general.section') ?></th>
				<th><?php echo $this->Label->get('general.class') ?></th>
				<th><?php echo $this->Label->get('general.subject') ?></th>
				<th><?php echo $this->Label->get('general.male_students') ?></th>
				<th><?php echo $this->Label->get('general.female_students') ?></th>
			</tr>
		</thead>
		
		<tbody>
			<?php foreach ($data as $obj) : ?>
			<tr>
				<td><?php echo $obj['SchoolYear']['name'] ?></td>
				<td><?php echo $obj['InstitutionSite']['name'] ?></td>
				<td></td>
				<td><?php echo $this->Html->link($obj['InstitutionSiteClass']['name'], array('action' => 'InstitutionSiteClass', 'view'), array('escape' => false)); ?></td>
				<td></td>
				<td></td>
				<td></td>
			</tr>
			
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>
