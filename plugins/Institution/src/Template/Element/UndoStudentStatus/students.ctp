<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php if ($action == 'reconfirm') : ?>
	<div class="input clearfix">
		<label><?= isset($attr['label']) ? __($attr['label']) : __($attr['field']) ?></label>
		<div class="table-in-view">
			<table class="table">
				<thead>
					<tr>
						<th><?= __('OpenEmis ID') ?></th>
						<th><?= __('Student') ?></th>
						<th><?= __('Current Grade') ?></th>
					</tr>
				</thead>
				<?php if (isset($attr['data'])) : ?>
					<tbody>
						<?php foreach ($attr['data'] as $i => $obj) : ?>
							<tr>
								<td>
									<?php
										$alias = $ControllerAction['table']->alias();
										$fieldPrefix = "$alias.students.$i";
										echo $obj->_matchingData['Users']->openemis_no;
										echo $this->Form->hidden("$fieldPrefix.id", ['value' => $obj->student_id]);
									?>
								</td>
								<td><?= $obj->_matchingData['Users']->name ?></td>
								<td><?= $obj->_matchingData['EducationGrades']->programme_grade_name ?></td>
							</tr>
						<?php endforeach ?>
					</tbody>
				<?php endif ?>
			</table>
		</div>
	</div>
<?php elseif ($action == 'add') : ?>
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
								<td class="checkbox-column tooltip-blue">
									<?php
										if (isset($obj->info_message)) {
											echo '<i class="fa fa-info-circle fa-lg icon-blue" data-placement="top" data-toggle="tooltip" title="" data-original-title="' .$obj->info_message. '"></i>';
										} else {
											$alias = $ControllerAction['table']->alias();
											$fieldPrefix = "$alias.students.$i";

											$checkboxOptions = ['type' => 'checkbox', 'class' => 'icheck-input', 'label' => false, 'div' => false, 'value' => $obj->student_id];
											echo $this->Form->input("$fieldPrefix.id", $checkboxOptions);
										}
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
