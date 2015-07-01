<?php if ($action == 'view') : ?>
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?= $this->Label->get('general.name'); ?></th>
					<th><?= $this->Label->get('general.description'); ?></th>
					<th><?= $this->Label->get('RubricTemplateOption.weighting'); ?></th>
				</tr>
			</thead>
			<?php if (!empty($data->rubric_criteria_options)) : ?>
				<tbody>
					<?php foreach ($data->rubric_criteria_options as $key => $obj) : ?>
					<tr>
						<td><?= $obj->rubric_template_option->name; ?></td>
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
		<label class="pull-left" for="<?= $attr['id'] ?>"><?= isset($attr['label']) ? $attr['label'] : $attr['field'] ?></label>
		<div class="col-md-6">
			<table class="table table-striped table-hover table-bordered table-checkable table-input">
				<thead>
					<tr>
						<th><?= $this->Label->get('general.name'); ?></th>
						<th><?= $this->Label->get('general.description'); ?></th>
						<th><?= $this->Label->get('RubricTemplateOptions.weighting'); ?></th>
					</tr>
				</thead>
				<?php if (!empty($data->rubric_criteria_options)) : ?>
					<tbody>
						<?php foreach ($data->rubric_criteria_options as $key => $obj) : ?>
							<tr>
								<td>
									<?= $obj->rubric_template_option->name; ?>
									<?= $this->Form->hidden("RubricCriterias.rubric_criteria_options.$key.rubric_template_option.name"); ?>
								</td>
								<td>
									<?php
										if(isset($obj->id)) {
											echo $this->Form->hidden("RubricCriterias.rubric_criteria_options.$key.id");
										}
										echo $this->Form->hidden("RubricCriterias.rubric_criteria_options.$key.rubric_template_option_id");
										echo $this->Form->input("RubricCriterias.rubric_criteria_options.$key.name", ['type' => 'textarea', 'label' => false]);
									?>
								</td>
								<td>
									<?= $obj->rubric_template_option->weighting; ?>
									<?= $this->Form->hidden("RubricCriterias.rubric_criteria_options.$key.rubric_template_option.weighting"); ?>
								</td>
							</tr>
						<?php endforeach ?>
					</tbody>
				<?php endif ?>
			</table>
		</div>
	</div>
<?php endif ?>
