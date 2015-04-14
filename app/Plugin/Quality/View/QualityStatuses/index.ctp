<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
if (!empty($selectedTemplate)) {
	if ($_add) {
		echo $this->Html->link($this->Label->get('general.add'), array('action' => 'add', 'template' => $selectedTemplate), array('class' => 'divider'));
	}
}
$this->end();

$this->start('contentBody');
echo $this->element($controlsElement, array(), array('plugin' => $this->params['plugin']));
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('QualityStatus.rubric_template_id'); ?></th>
				<th><?php echo $this->Label->get('QualityStatus.academic_period_level_id'); ?></th>
				<th><?php echo $this->Label->get('general.academic_period'); ?></th>
				<th><?php echo $this->Label->get('QualityStatus.security_roles'); ?></th>
				<th><?php echo $this->Label->get('QualityStatus.education_programmes'); ?></th>
				<th><?php echo $this->Label->get('QualityStatus.date_enabled'); ?></th>
				<th><?php echo $this->Label->get('QualityStatus.date_disabled'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if (!empty($data)) : ?>
				<?php foreach ($data as $obj) : ?>
					<tr>
						<td><?php echo $this->Html->link($obj['RubricTemplate']['name'], array('action' => 'view', $obj['QualityStatus']['id'], 'template' => $selectedTemplate)); ?></td>
						<td><?php echo $obj['AcademicPeriodLevel']['name']; ?></td>
						<td>
							<?php
								$academicPeriods = array();
								foreach ($obj['AcademicPeriod'] as $academicPeriod) {
									$academicPeriods[] = $academicPeriod['name'];
								}
								echo implode(', ', $academicPeriods);
							?>
						</td>
						<td>
							<?php
								$securityRoles = array();
								foreach ($obj['SecurityRole'] as $securityRole) {
									$securityRoles[] = $securityRole['name'];
								}
								echo implode(', ', $securityRoles);
							?>
						</td>
						<td>
							<?php
								$educationProgrammes = array();
								foreach ($obj['EducationProgramme'] as $educationProgramme) {
									$educationProgrammes[] = $educationProgramme['cycle_programme_name'];
								}
								echo implode('<br>', $educationProgrammes);
							?>
						</td>
						<td><?php echo $obj['QualityStatus']['date_enabled']; ?></td>
						<td><?php echo $obj['QualityStatus']['date_disabled']; ?></td>
					</tr>
				<?php endforeach ?>
			<?php endif ?>
		</tbody>
	</table>
</div>

<?php
$this->end();
?>
