<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php if ($action == 'add') : ?>
	<div class="input clearfix">
		<label><?= isset($attr['label']) ? __($attr['label']) : __($attr['field']) ?></label>
		<div class="table-in-view">
			<table class="table table-striped table-hover table-bordered table-checkable">
				<thead>
					<tr>
						<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
						<th><?= __('OpenEmis ID') ?></th>
						<th><?= __('Student') ?></th>
						<th><?= __('Status') ?></th>
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
										echo $this->Form->hidden("$fieldPrefix.status", ['value' => $attr['attr']['status']]);
										echo $this->Form->hidden("$fieldPrefix.type", ['value' => $attr['attr']['type']]);
									?>
								</td>
								<td><?= $obj->_matchingData['Users']->openemis_no ?></td>
								<td><?= $obj->_matchingData['Users']->name ?></td>
								<td><?= $attr['attr']['statuses'][$obj->student_status_id ]?></td>
							</tr>
						<?php endforeach ?>
					</tbody>
				<?php endif ?>
			</table>
		</div>
	</div>
<?php endif ?>
