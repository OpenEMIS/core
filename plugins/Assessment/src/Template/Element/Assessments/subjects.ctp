<?= $this->Html->css('OpenEmis.../plugins/icheck/skins/minimal/blue', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/icheck/jquery.icheck.min', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php if ($action == 'add' || $action == 'edit') : ?>
	<div class="input table">
		<label class="pull-left" for="<?= $attr['id'] ?>"><?= __($attr['label']); ?></label>
		<div class="table-in-view col-md-4 table-responsive">
			<table class="table table-striped table-hover table-bordered table-checkable">
				<thead>
					<tr>
						<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
						<th><?= __('Code') ?></th>
						<th><?= __('Name') ?></th>
						<th><?= __('Type') ?></th>
						<th><?= __('Pass') ?></th>
						<th><?= __('Max') ?></th>
						<th><?= __('Grading Types') ?></th>
					</tr>
				</thead>

				<tbody>
				<?php foreach ($data->assessment_items as $i => $obj) : ?>
					<tr>
						<td class="checkbox-column">
							<?php
							echo $this->Form->input("$model.assessment_items.$i.visible", [
								'type' => 'checkbox',
								'class' => 'icheck-input',
								'label' => false
							]);
							echo $this->Form->hidden("$model.assessment_items.$i.id");
							echo $this->Form->hidden("$model.assessment_items.$i.education_subject_id", ['value' => $obj['education_subject']->id]);
							?>
						</td>
						<td><?= $obj['education_subject']->code ?></td>
						<td><?= $obj['education_subject']->name ?></td>
						<td><?= $this->Form->input("$model.assessment_items.$i.result_type", ['options' => $markTypeOptions, 'label' => false]) ?></td>
						<td><?= $this->Form->input("$model.assessment_items.$i.pass_mark", ['label' => false]) ?></td>
						<td><?= $this->Form->input("$model.assessment_items.$i.max", ['label' => false]) ?></td>
						<td><?= $this->Form->input("$model.assessment_items.$i.assessment_grading_type_id", ['options' => $gradingTypeOptions, 'label' => false]) ?></td>
					</tr>
				<?php endforeach ?>
				</tbody>
			</table>
		</div>
	</div>
<?php else : ?>
	<div class="table-in-view col-md-4 table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?= __('Visible') ?></th>
					<th><?= __('Code') ?></th>
					<th><?= __('Name') ?></th>
					<th><?= __('Type') ?></th>
					<th><?= __('Pass') ?></th>
					<th><?= __('Max') ?></th>
					<th><?= __('Grading Types') ?></th>
				</tr>
			</thead>

			<tbody>
			<?php foreach ($data->assessment_items as $i => $obj) : ?>
				<tr>
					<td><?= $obj->visible == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>'; ?></td>
					<td><?= $obj->education_subject->code ?></td>
					<td><?= $obj->education_subject->name ?></td>
					<td><?= isset($markTypeOptions[$obj->result_type]) ? $markTypeOptions[$obj->result_type] : current($markTypeOptions) ?></td>
					<td><?= $obj->pass_mark ?></td>
					<td><?= $obj->max ?></td>
					<td><?= isset($gradingTypeOptions[$obj->assessment_grading_type_id]) ? $gradingTypeOptions[$obj->assessment_grading_type_id] : current($gradingTypeOptions) ?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>
