<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php if (in_array($action, ['add', 'reconfirm'])) : ?>
	<div class="input clearfix required">
		<label><?= isset($attr['label']) ? __($attr['label']) : __($attr['field']) ?></label>
		<div class="table-wrapper"> 
			<div class="table-in-view">
				<table class="table table-checkable">
					<thead>
						<tr>
							<?php if ($action != 'reconfirm') { ?>
							<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
							<?php } ?>
							<th><?= __('OpenEmis ID') ?></th>
							<th><?= __('Student') ?></th>
							<th><?= __('Current Grade') ?></th>
							<th><?= __('Class') ?></th>
						</tr>
					</thead>
					<?php if (isset($attr['data'])) : 
							$selectedStudents = array_key_exists('selectedStudents', $attr)? $attr['selectedStudents']: [];
							$onlySelectedStudents = [];
							foreach ($selectedStudents as $sskey => $ssvalue) {
								if (!empty($ssvalue['selected'])) {
									$onlySelectedStudents[] = $ssvalue['student_id'];
								}
							}
					?>
						<tbody>
							<?php 
							$studentCount = 0;
							foreach ($attr['data'] as $i => $obj) : 
								if ($action == 'reconfirm') {
									if (!in_array($obj->student_id, $onlySelectedStudents)) continue;
								}
								?>
								<tr>
									<?php if ($action != 'reconfirm') { ?>
									<td class="checkbox-column tooltip-orange">
										<?php
											$pendingRequestsCount = $obj->dropoutRequestCount + $obj->admissionRequestCount;
											if ($pendingRequestsCount > 0) {
												echo '<i class="fa fa-info-circle fa-lg table-tooltip icon-orange" data-animation="false" data-container="body" data-placement="top" data-toggle="tooltip" title="" data-original-title="' .$this->Label->get($ControllerAction['table']->alias().'.pendingRequest'). '"></i>';
											} else {
												$alias = $ControllerAction['table']->alias();
												$fieldPrefix = "$alias.students.$i";

												$checkboxOptions = ['type' => 'checkbox', 'class' => 'icheck-input', 'label' => false, 'div' => false];
												echo $this->Form->input("$fieldPrefix.selected", $checkboxOptions);
												echo $this->Form->hidden("$fieldPrefix.student_id", ['value' => $obj->student_id]);
											}
										?>
									</td>
									<?php } ?>
									<td><?= $obj->_matchingData['Users']->openemis_no ?></td>
									<td><?= $obj->_matchingData['Users']->name ?></td>
									<td><?= $obj->_matchingData['EducationGrades']->programme_grade_name ?></td>
									<td><?= $obj->institution_class_name?></td>
								</tr>
							<?php $studentCount++;
							endforeach ?>
							<?php 
							if ($studentCount <= 0) {
								?>
								<tr><td><?= $this->Label->get($ControllerAction['table']->alias().'.noStudentSelected'); ?></td></tr>
								<?php 
							}
							 ?>
						</tbody>
					<?php endif ?>
				</table>
			</div>
		</div>
	</div>
<?php endif ?>
