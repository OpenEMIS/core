<?php
	$gradingOptions = $attr['attr']['gradingOptions'];
?>
<?php if ($action == 'view') : ?>
	<div class="table-in-view">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?= $this->Label->get('General.openemis_no'); ?></th>
					<th><?= $this->Label->get('Users.name'); ?></th>
					<th><?= $this->Label->get('InstitutionAssessments.mark'); ?></th>
					<th><?= $this->Label->get('InstitutionAssessments.grading'); ?></th>
				</tr>
			</thead>
			<?php if (isset($attr['data'])) : ?>
				<tbody>
					<?php foreach ($attr['data'] as $i => $student) : ?>
						<tr>
							<td><?= $student->_matchingData['Users']->openemis_no; ?></td>
							<td><?= $student->_matchingData['Users']->name; ?></td>
							<td><?= $student->AssessmentItemResults['marks']; ?></td>
							<td>
								<?php
									if (!empty($student->AssessmentItemResults['assessment_grading_option_id'])) {
										echo $gradingOptions[$student->AssessmentItemResults['assessment_grading_option_id']];
									}
								?>
							</td>
						</tr>
					<?php endforeach ?>
				</tbody>
			<?php endif ?>
		</table>
	</div>
<?php elseif ($action == 'edit') : ?>
	<div class="input clearfix">
		<label><?= isset($attr['label']) ? __($attr['label']) : __($attr['field']) ?></label>
		<div class="table-in-view">
			<table class="table table-striped table-hover table-bordered table-checkable">
				<thead>
					<tr>
						<th><?= $this->Label->get('General.openemis_no'); ?></th>
						<th><?= $this->Label->get('Users.name'); ?></th>
						<th><?= $this->Label->get('InstitutionAssessments.mark'); ?></th>
						<th><?= $this->Label->get('InstitutionAssessments.grading'); ?></th>
					</tr>
				</thead>
				<?php if (isset($attr['data'])) : ?>
					<tbody>
						<?php foreach ($attr['data'] as $i => $student) : ?>
							<tr>
								<td>
									<?php
										$alias = $ControllerAction['table']->alias();
										$fieldPrefix = "$alias.students.$i";
										echo $student->_matchingData['Users']->openemis_no;
										echo $this->Form->hidden("$fieldPrefix.student_id", ['value' => $student->student_id]);
									?>
								</td>
								<td><?= $student->_matchingData['Users']->name; ?></td>
								<td><?= $this->Form->input("$fieldPrefix.marks", ['label' => false, 'value' => $student->AssessmentItemResults['marks']]); ?></td>
								<td>
									<?php
										$inputOptions = [
											'type' => 'select',
											'label' => false,
											'default' => $student->AssessmentItemResults['assessment_grading_option_id'],
											'value' => $student->AssessmentItemResults['assessment_grading_option_id'],
											'options' => $gradingOptions
										];
										echo $this->Form->input("$fieldPrefix.assessment_grading_option_id", $inputOptions);
										if (isset($student->AssessmentItemResults['id'])) {
											echo $this->Form->hidden("$fieldPrefix.id", ['value' => $student->AssessmentItemResults['id']]);
										}
									?>
								</td>
							</tr>
						<?php endforeach ?>
					</tbody>
				<?php endif ?>
			</table>
		</div>
	</div>
<?php endif ?>
