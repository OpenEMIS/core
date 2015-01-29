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
				<th><?php echo $this->Label->get('InstitutionSiteSection.staff_id'); ?></th>
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
				<td><?php echo $this->Html->link($obj['InstitutionSiteSection']['name'], array('plugin' => false, 'controller' => 'InstitutionSites', 'action' => 'InstitutionSiteSection', 'view', $obj['StudentSection']['id']), array('escape' => false)); ?></td>
				<td><?php echo $obj['Staff']['staff_name']; ?></td>
			</tr>
			
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>
