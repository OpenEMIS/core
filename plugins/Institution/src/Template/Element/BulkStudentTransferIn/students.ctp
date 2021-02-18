<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php if (in_array($action, ['edit', 'reconfirm'])) : ?>
	<div class="input clearfix required">
		<label><?= isset($attr['label']) ? __($attr['label']) : __($attr['field']) ?></label>
		<div class="table-wrapper">
			<div class="table-in-view">
				<table class="table table-checkable">
					<thead>
						<tr>
							<?php if ($action != 'reconfirm') { ?>
								<th class="checkbox-column"><input type="checkbox" class="no-selection-label" kd-checkbox-radio/></th>
							<?php } ?>
							<th><?= __('Status') ?></th>
							<th><?= __('Assignee') ?></th>
							<th><?= __('Student') ?></th>
							<th><?= __('Academic Period') ?></th>
							<th><?= __('Education Grade') ?></th>
							<th><?= __('Institution Class') ?></th>
							<th><?= __('Start Date') ?></th>
							<th><?= __('End Date') ?></th>
							<th><?= __('Comment') ?></th>
						</tr>
					</thead>
					<?php if (isset($attr['data'])) : $selectedStudents = array_key_exists('selectedStudents', $attr) ? $attr['selectedStudents']: [];
						$onlySelectedStudents = [];
						foreach ($selectedStudents as $sskey => $ssvalue) {
							if (!empty($ssvalue['selected'])) {
								$onlySelectedStudents[] = $ssvalue['student_id'];
							}
						} ?>
						<tbody>
							<?php
							foreach ($attr['data'] as $i => $obj) :
								if ($action == 'reconfirm') {
									if (!in_array($obj->student_id, $onlySelectedStudents)) continue;
								} ?>
								<tr>
									<?php if ($action != 'reconfirm') { ?>
										<td class="checkbox-column tooltip-orange">
											<?php
											$alias = $ControllerAction['table']->alias();
											$fieldPrefix = "$alias.students.$i";
											echo $this->Form->checkbox("$fieldPrefix.selected", ['class' => 'no-selection-label', 'kd-checkbox-radio' => '']);
											echo $this->Form->hidden("$fieldPrefix.id", ['value' => $obj->id]);
											echo $this->Form->hidden("$fieldPrefix.student_id", ['value' => $obj->student_id]);
										?>
									</td>
									<?php } ?>
									<td><?= $obj->status->name ?></td>
									<td><?= isset($obj->assignee->name) ? $obj->assignee->name : null ?></td>
									<td><?= $obj->user->name_with_id ?></td>
									<td><?= $obj->academic_period->name ?></td>
									<td><?= $obj->education_grade->name ?></td>
									<td><?= isset($obj->institution_class->name) ? $obj->institution_class->name : null?></td>
									<td><?= $obj->start_date ?></td>
									<td><?= $obj->end_date ?></td>
									<td><?= $obj->comment ?></td>
								</tr>
								<?php
							endforeach ?>
							<?php if (count($attr['data']) <= 0) { ?>
								<tr>
									<td><?= $this->Label->get($ControllerAction['table']->alias().'.noStudentSelected'); ?>
									</td>
								</tr>
							<?php } ?>
						</tbody>
					<?php endif ?>
				</table>
			</div>
		</div>
	</div>
<?php endif ?>