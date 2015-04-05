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
				<th class="section-info">
					<span><?php echo $this->Label->get('InstitutionSiteQualityRubric.education_programme_id'); ?></span>
					<span class="middot">&middot;</span>
					<span><?php echo $this->Label->get('InstitutionSiteQualityRubric.education_grade_id'); ?></span>
				</th>
				<th class="section-info">
					<span><?php echo $this->Label->get('InstitutionSiteQualityRubric.institution_site_section_id'); ?></span>
					<span class="middot">&middot;</span>
					<span><?php echo $this->Label->get('InstitutionSiteQualityRubric.institution_site_class_id'); ?></span>
				</th>
				<th><?php echo __('Completed By') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($data as $obj) : ?>
				<?php if (isset($obj['InstitutionSiteClass'])) : ?>
					<?php foreach ($obj['InstitutionSiteClass'] as $class) : ?>
						<tr>
							<td>
								<?php
									echo $this->Html->link($obj['RubricTemplate']['name'], array(
										'action' => $model, 'listSection',
										'template' => $obj['RubricTemplate']['id'],
										'period' => $class['AcademicPeriod']['id'],
										'programme' => $class['EducationProgramme']['id'],
										'grade' => $class['EducationGrade']['id'],
										'section' => $class['InstitutionSiteSection']['id'],
										'class' => $class['InstitutionSiteClass']['id'],
										'status' => $selectedAction
									));
								?>
							</td>
							<td><?php echo $class['AcademicPeriod']['name']; ?></td>
							<td class="section-info">
								<span><?php echo $class['EducationProgramme']['name']; ?></span>
								<span class="middot">&middot;</span>
								<span><?php echo $class['EducationGrade']['name']; ?></span>
							</td>
							<td class="section-info">
								<span><?php echo $class['InstitutionSiteSection']['name']; ?></span>
								<span class="middot">&middot;</span>
								<span><?php echo $class['InstitutionSiteClass']['name']; ?></span>
							</td>
							<td><?php echo $class['QualityStatus']['date_disabled']; ?></td>
						</tr>
					<?php endforeach ?>
				<?php endif ?>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php
$this->end();
?>
