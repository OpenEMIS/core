<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'index', 'status' => $selectedAction), array('class' => 'divider'));
	if($_delete) {
		if ($selectedAction == 1) {
			echo $this->Html->link($this->Label->get('general.delete'), array('action' => $model, 'remove'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
		} else if ($selectedAction == 2) {
			echo $this->Html->link(
				$this->Label->get('general.reject'),
				array('action' => 'InstitutionSiteQualityRubric', 'remove'),
				array(
					'class' => 'divider',
					'onclick' => 'return jsForm.confirmDelete(this)',
					'data-title' => __('Reject Confirmation'),
					'data-content' => __('You are about to reject this quality rubric.<br><br>Are you sure you want to do this?'),
					'data-button-text' => $this->Label->get('general.reject')
				)
			);
		}
	}
$this->end();

$this->start('contentBody');
echo $this->element($tabsElement, array(), array());
?>

<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteQualityRubric.rubric_template_id'); ?></div>
	<div class="col-md-6"><?php echo $data['RubricTemplate']['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteQualityRubric.academic_period_id'); ?></div>
	<div class="col-md-6"><?php echo $data['AcademicPeriod']['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3">
		<span><?php echo $this->Label->get('InstitutionSiteQualityRubric.education_programme_id'); ?></span>
		<span class="middot">&middot;</span>
		<span><?php echo $this->Label->get('InstitutionSiteQualityRubric.education_grade_id'); ?></span>
	</div>
	<div class="col-md-6">
		<span><?php echo $data['EducationGrade']['EducationProgramme']['name']; ?></span>
		<span class="middot">&middot;</span>
		<span><?php echo $data['EducationGrade']['name']; ?></span>
	</div>
</div>
<div class="row">
	<div class="col-md-3">
		<span><?php echo $this->Label->get('InstitutionSiteQualityRubric.institution_site_section_id'); ?></span>
		<span class="middot">&middot;</span>
		<span><?php echo $this->Label->get('InstitutionSiteQualityRubric.institution_site_class_id'); ?></span>
	</div>
	<div class="col-md-6">
		<span><?php echo $data['InstitutionSiteSection']['name']; ?></span>
		<span class="middot">&middot;</span>
		<span><?php echo $data['InstitutionSiteClass']['name']; ?></span>
	</div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteQualityRubric.staff_id') ?></div>
	<div class="col-md-6"><?php echo $data['SecurityUser']['first_name'] . " " . $data['SecurityUser']['last_name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteQualityRubric.rubric_sections') ?></div>
	<div class="col-md-6">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th><?php echo __('No.') ?></th>
						<th><?php echo $this->Label->get('general.name'); ?></th>
						<th>
							<?php echo $this->Label->get('RubricSection.no_of_criterias'); ?>
							<?php
								if ($selectedAction == 1) {
									echo " (".__('Answered').")";
								}
							?>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php
						$named = $this->request->params['named'];
						$pass = $this->request->params['pass'];
						unset($pass[0]);
					?>
					<?php foreach ($data['RubricSection'] as $key => $obj) : ?>
						<tr>
							<td><?php echo $obj['RubricSection']['order']; ?></td>
							<td>
								<?php
									$actionUrl = array('action' => $model, 'edit', 'section' => $obj['RubricSection']['id']);
									$actionUrl = array_merge($actionUrl, $named, $pass);
									echo $this->Html->link($obj['RubricSection']['name'], $actionUrl);
								?>
							</td>
							<td>
								<?php echo $obj['RubricSection']['no_of_criterias']; ?>
								<?php
									if ($selectedAction == 1) {
										echo " (".$obj['RubricSection']['no_of_answers'].")";
									}
								?>
							</td>
						</tr>
					<?php endforeach ?>
				</tbody>
			</table>
		</div>	
	</div>
</div>

<?php
$this->end();
?>
