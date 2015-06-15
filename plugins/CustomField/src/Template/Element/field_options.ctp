<?php if ($action == 'view') : ?>
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?= $this->Label->get('general.name'); ?></th>
					<th><?= $this->Label->get('RubricTemplateOption.weighting'); ?></th>
				</tr>
			</thead>
			<?php if (!empty($data->rubric_criteria_options)) : ?>
				<tbody>
					<?php foreach ($data->rubric_criteria_options as $key => $obj) : ?>
					<tr>
						<td><?= $obj->name; ?></td>
						<td><?= $obj->rubric_template_option->weighting; ?></td>
					</tr>
					<?php endforeach ?>
				</tbody>
			<?php endif ?>
		</table>
	</div>
<?php else : ?>
	<div class="input">
		<label class="pull-left" for="<?= $attr['id'] ?>"><?= $this->ControllerAction->getLabel($attr['model'], $attr['field'], $attr) ?></label>
		<div class="col-md-6">
			<table class="table table-striped table-hover table-bordered table-checkable table-input">
				<thead>
					<tr>
						<?php if ($action == 'edit') : ?>
							<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
						<?php endif ?>
						<th><?= $this->Label->get('general.name'); ?></th>
						<th><?= $this->Label->get('CustomFieldOptions.is_default'); ?></th>
						<?php if ($action == 'add') : ?>
							<th class="cell-delete"></th>
						<?php endif ?>
					</tr>
				</thead>
				<?php if (!empty($data->custom_field_options)) : ?>
					<tbody>
						<?php foreach ($data->custom_field_options as $key => $obj) : ?>
							<tr>
								<td>
									<?php
										if(isset($obj->id)) {
											echo $this->Form->hidden("CustomFields.custom_field_options.$key.id");
										}
										echo $this->Form->input("CustomFields.custom_field_options.$key.name", ['label' => false]);
									?>
								</td>
								<td>
									<?= $this->Form->input("CustomFields.rubric_criteria_options.$key.is_default", ['label' => false]); ?>
								</td>
							</tr>
						<?php endforeach ?>
					</tbody>
				<?php endif ?>
			</table>
			<a class="void icon_plus" onclick="$('#reload').val('addOption').click()"><i class="fa fa-plus"></i></a>
		</div>
	</div>
<?php endif ?>
