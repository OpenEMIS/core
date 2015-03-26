<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
if ($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => $model, 'add'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('/../../Plugin/Quality/View/QualityRubrics/nav_tabs');
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.name'); ?></th>
				<th><?php echo $this->Label->get('general.description'); ?></th>
				<th><?php echo $this->Label->get('RubricTemplate.weighting_type'); ?></th>
				<th><?php echo $this->Label->get('RubricTemplate.security_roles'); ?></th>
				<th><?php echo $this->Label->get('RubricTemplate.grades'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($data as $obj) : ?>
				<tr>
					<td><?php echo $this->Html->link($obj['RubricTemplate']['name'], array('action' => $model, 'view', $obj['RubricTemplate']['id'])); ?></td>
					<td><?php echo $obj['RubricTemplate']['description']; ?></td>
					<td><?php echo $weightingTypeOptions[$obj['RubricTemplate']['weighting_type']]; ?></td>
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
							$rubricTemplateGrades = array();
							foreach ($obj['RubricTemplateGrade'] as $rubricTemplateGrade) {
								if ($rubricTemplateGrade['visible'] == 1) {
									$rubricTemplateGrades[] = $rubricTemplateGrade['EducationGrade']['name'];
								}
							}
							echo implode(', ', $rubricTemplateGrades);
						?>
					</td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php
$this->end();
?>
