<?php 
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Sections'));

$this->start('contentBody');
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.academic_period'); ?></th>
				<th><?php echo $this->Label->get('Institution.name'); ?></th>
				<th><?php echo $this->Label->get('general.grade'); ?></th>
				<th><?php echo $this->Label->get('general.section'); ?></th>
				<th><?php echo $this->Label->get('general.male_students'); ?></th>
				<th><?php echo $this->Label->get('general.female_students'); ?></th>
			</tr>
		</thead>
		
		<tbody>
			<?php foreach ($data as $obj) : ?>
			<tr>
				<td><?php echo $obj['AcademicPeriod']['name']; ?></td>
				<td><?php echo $obj['InstitutionSite']['name']; ?></td>
				<td><?php 
				foreach($obj['EducationGrade']['grades'] as $grade){
					echo $grade . '<br>';
				}
				?></td>
				<td><?php echo $this->Html->link($obj['StaffSection']['name'], array('plugin' => false, 'controller' => 'InstitutionSites', 'action' => 'InstitutionSiteSection', 'view', $obj['StaffSection']['id']), array('escape' => false)); ?></td>
				<td><?php echo empty($obj[$model]['gender']['M']) ? 0 : $obj[$model]['gender']['M']; ?></td>
				<td><?php echo empty($obj[$model]['gender']['F']) ? 0 : $obj[$model]['gender']['F']; ?></td>
			</tr>
			
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>
