<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php if ($action == 'add') : ?>
	<div class="input clearfix">
		<label><?= isset($attr['label']) ? __($attr['label']) : __($attr['field']) ?></label>
		<div class="table-in-view">
			<table class="table table-checkable">
				<thead>
					<tr>
						<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
						<th><?= __('OpenEmis ID') ?></th>
						<th><?= __('Student') ?></th>
						<th><?= __('Current Grade') ?></th>
					</tr>
				</thead>
				<?php if (isset($attr['data'])) : ?>
					<tbody>
						<?php foreach ($attr['data'] as $i => $obj) : ?>
							<tr>
								<td class="checkbox-column">
									<?php
										$alias = $ControllerAction['table']->alias();
										$fieldPrefix = "$alias.students.$i";

										$checkboxOptions = ['type' => 'checkbox', 'class' => 'icheck-input', 'label' => false, 'div' => false];
										echo $this->Form->input("$fieldPrefix.selected", $checkboxOptions);
										echo $this->Form->hidden("$fieldPrefix.student_id", ['value' => $obj->student_id]);
									?>
								</td>
								<td><?= $obj->_matchingData['Users']->openemis_no ?></td>
								<td><?= $obj->_matchingData['Users']->name ?></td>
								<td><?= $obj->_matchingData['EducationGrades']->programme_grade_name ?></td>
							</tr>
						<?php endforeach ?>
					</tbody>
				<?php endif ?>
			</table>
		</div>
	</div>
<?php endif ?>
