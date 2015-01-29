<?php 
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Classes'));

$this->start('contentBody');
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('AcademicPeriod.name'); ?></th>
				<th><?php echo $this->Label->get('Institution.name'); ?></th>
				<th><?php echo $this->Label->get('general.section'); ?></th>
				<th><?php echo $this->Label->get('general.class'); ?></th>
				<th><?php echo $this->Label->get('general.subject'); ?></th>
				<th><?php echo $this->Label->get('general.male_students'); ?></th>
				<th><?php echo $this->Label->get('general.female_students'); ?></th>
			</tr>
		</thead>
		
		<tbody>
			<?php foreach ($data as $obj) : ?>
			<tr>
				<td><?php echo $obj['AcademicPeriod']['name']; ?></td>
				<td><?php echo $obj['InstitutionSite']['name']; ?></td>
				<td><?php echo $this->Html->link($obj['InstitutionSiteSection']['name'], array('plugin' => false, 'controller' => 'InstitutionSites', 'action' => 'InstitutionSiteSection', 'view', $obj['InstitutionSiteSection']['id']), array('escape' => false)); ?></td>
				<td><?php echo $this->Html->link($obj['InstitutionSiteClass']['name'], array('plugin' => false, 'controller' => 'InstitutionSites', 'action' => 'InstitutionSiteClass', 'view', $obj['InstitutionSiteClass']['id']), array('escape' => false)); ?></td>
				<td><?php echo $obj['EducationSubject']['name']; ?></td>
				<td><?php echo empty($obj[$model]['gender']['M']) ? 0 : $obj[$model]['gender']['M']; ?></td>
				<td><?php echo empty($obj[$model]['gender']['F']) ? 0 : $obj[$model]['gender']['F']; ?></td>
			</tr>
			
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>
