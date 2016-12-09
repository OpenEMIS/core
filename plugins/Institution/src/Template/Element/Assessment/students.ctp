<?php
	$assessmentItemObj = $attr['attr']['assessmentItemObj'];
	if (!empty($assessmentItemObj)) {
		$resultType = $assessmentItemObj['result_type'];
		$maxMark = $assessmentItemObj['max'];
		$gradingOptions = $assessmentItemObj['options'];
	} else {
		$resultType = 'MARKS';
		$maxMark = 100;
		$gradingOptions = [];
	}
?>
<?php if ($action == 'view') : ?>
	<div class="table-wrapper">
		<div class="table-in-view">
			<table class="table">
				<thead>
					<tr>
						<th><?= $this->Label->get('General.openemis_no'); ?></th>
						<th><?= $this->Label->get('Users.name'); ?></th>
						<?php if ($resultType == 'MARKS') : ?>
							<th><?= $this->Label->get('InstitutionAssessments.mark'); ?></th>
						<?php endif ?>
						<th><?= $this->Label->get('InstitutionAssessments.grading'); ?></th>
					</tr>
				</thead>
				<?php if (isset($attr['data'])) : ?>
					<tbody>
						<?php foreach ($attr['data'] as $i => $student) : ?>
							<tr>
								<td>

									<?= $this->html->link($student->_matchingData['Users']->openemis_no, [
										'plugin' => 'Institution',
										'controller' => 'Institutions',
										'action' => 'StudentUser',
										'view',
										$this->ControllerAction->paramsEncode(['id' => $student->_matchingData['Users']->id])
									]); ?>
								</td>
								<td><?= $student->_matchingData['Users']->name; ?></td>
								<?php if ($resultType == 'MARKS') : ?>
									<td><?= $student->AssessmentItemResults['marks']; ?></td>
								<?php endif ?>
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
	</div>
<?php elseif ($action == 'edit') : ?>
	<div class="input clearfix">
		<label><?= isset($attr['label']) ? __($attr['label']) : __($attr['field']) ?></label>
		<div class="table-wrapper">
			<div class="table-in-view">
				<table class="table table-checkable">
					<thead>
						<tr>
							<th><?= $this->Label->get('General.openemis_no'); ?></th>
							<th><?= $this->Label->get('Users.name'); ?></th>
							<?php if ($resultType == 'MARKS') : ?>
								<th><?= $this->Label->get('InstitutionAssessments.mark'); ?></th>
							<?php endif ?>
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
									<?php if ($resultType == 'MARKS') : ?>
										<td>
											<?php
												$marks = strlen($student->AssessmentItemResults['marks']) != 0 ? $student->AssessmentItemResults['marks'] : '';
												echo $this->Form->input("$fieldPrefix.marks", [
													'type' => 'number',
													'label' => false,
													'value' => $marks,
													'min' => 0,
													'data-id' => $student->_matchingData['Users']->id,
													'class' => 'resultMark'
												]);
												echo $this->Form->hidden("$fieldPrefix.max_mark", ['value' => $maxMark, 'class' => 'maxMark']);
											?>
										</td>
									<?php endif ?>
									<td>
										<?php
											$grading = !empty($student->AssessmentItemResults['assessment_grading_option_id']) ? $student->AssessmentItemResults['assessment_grading_option_id'] : 0;
											$inputOptions = [
												'type' => 'select',
												'label' => false,
												'default' => $grading,
												'value' => $grading,
												'options' => $gradingOptions,
												'class' => 'resultGrade'
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
	</div>
<?php endif ?>
