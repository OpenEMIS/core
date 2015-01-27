<?php 
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Classes'));

$this->start('contentBody');
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.academic_period'); ?></th>
				<th><?php echo $this->Label->get('Institution.name'); ?></th>
				<th><?php echo $this->Label->get('general.section'); ?></th>
				<th><?php echo $this->Label->get('general.class'); ?></th>
				<th><?php echo $this->Label->get('general.subject'); ?></th>
				<th><?php echo $this->Label->get('general.teacher'); ?></th>
			</tr>
		</thead>
		
		<tbody>
			<?php foreach ($data as $obj) : ?>
			<tr>
				<td><?php echo $obj['SchoolYear']['name']; ?></td>
				<td><?php echo $obj['InstitutionSite']['name']; ?></td>
				<td><?php echo $obj['InstitutionSiteSection']['name']; ?></td>
			</tr>
			
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>