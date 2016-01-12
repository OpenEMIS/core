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
								<td class="checkbox-column tooltip-red">
									<?php
										if (isset($obj->info_message)) {
											echo '<i class="fa fa-exclamation-circle fa-lg icon-red" data-placement="right" data-toggle="tooltip" title="" data-original-title="' .$obj->info_message. '"></i>';
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
