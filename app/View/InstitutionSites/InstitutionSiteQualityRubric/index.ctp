<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
$this->end();

$this->start('contentBody');
echo $this->element($tabsElement, array(), array());
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('InstitutionSiteQualityRubric.rubric_template_id'); ?></th>
				<th><?php echo $this->Label->get('InstitutionSiteQualityRubric.academic_period_id'); ?></th>
				<th><?php echo $this->Label->get('InstitutionSiteQualityRubric.education_grade_id'); ?></th>
				<th class="section-info">
					<span><?php echo $this->Label->get('InstitutionSiteQualityRubric.institution_site_section_id'); ?></span>
					<span class="middot">&middot;</span>
					<span><?php echo $this->Label->get('InstitutionSiteQualityRubric.institution_site_class_id'); ?></span>
				</th>
				<?php if ($selectedAction == 0) : ?>
					<th><?php echo __('To Be Completed By') ?></th>
				<?php elseif ($selectedAction == 1) : ?>
					<th><?php echo __('Last Modified On') ?></th>
					<th><?php echo __('To Be Completed By') ?></th>
				<?php elseif ($selectedAction == 2) : ?>
					<th><?php echo __('Completed On') ?></th>
				<?php endif ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($data as $obj) : ?>
				<?php foreach ($obj['InstitutionSiteClass'] as $class) : ?>
					<tr>
						<td>
							<?php if ($selectedAction == 0) : ?>
								<?php
									echo $this->Html->link($obj['RubricTemplate']['name'], array(
										'action' => $model, 'listSection',
										'template' => $obj['RubricTemplate']['id'],
										'period' => $class['AcademicPeriod']['id'],
										'grade' => $class['EducationGrade']['id'],
										'siteSection' => $class['InstitutionSiteSection']['id'],
										'siteClass' => $class['InstitutionSiteClass']['id'],
										'status' => $selectedAction
									));
								?>
							<?php else : ?>
								<?php
									echo $this->Html->link($obj['RubricTemplate']['name'], array(
										'action' => $model, 'listSection', $class['InstitutionSiteQualityRubric']['id'],
										'status' => $selectedAction
									));
								?>
							<?php endif ?>
						</td>
						<td><?php echo $class['AcademicPeriod']['name']; ?></td>
						<td><?php echo $class['EducationGrade']['programme_grade_name']; ?></td>
						<td class="section-info">
							<span><?php echo $class['InstitutionSiteSection']['name']; ?></span>
							<span class="middot">&middot;</span>
							<span><?php echo $class['InstitutionSiteClass']['name']; ?></span>
						</td>
						<?php if ($selectedAction == 0) : ?>
							<td><?php echo $class['QualityStatus']['date_disabled']; ?></td>
						<?php elseif ($selectedAction == 1) : ?>
							<td><?php echo !empty($class['InstitutionSiteQualityRubric']['modified']) ? $class['InstitutionSiteQualityRubric']['modified'] : $class['InstitutionSiteQualityRubric']['created']; ?></td>
							<td><?php echo $class['QualityStatus']['date_disabled']; ?></td>
						<?php elseif ($selectedAction == 2) : ?>
							<td><?php echo !empty($class['InstitutionSiteQualityRubric']['modified']) ? $class['InstitutionSiteQualityRubric']['modified'] : $class['InstitutionSiteQualityRubric']['created']; ?></td>
						<?php endif ?>
					</tr>
				<?php endforeach ?>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php
$this->end();
?>
