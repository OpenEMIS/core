<?php if ($action == 'view') : ?>
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo $this->Label->get('general.name'); ?></th>
					<th><?php echo $this->Label->get('general.description'); ?></th>
					<th><?php echo $this->Label->get('RubricTemplateOption.weighting'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($criteriaOptions as $obj) : ?>
				<tr>
					<td><?php echo $obj['RubricTemplateOption']['name']; ?></td>
					<td><?php echo nl2br($obj['RubricCriteriaOption']['name']); ?></td>
					<td><?php echo $obj['RubricTemplateOption']['weighting']; ?></td>
				</tr>
				<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php else : ?>
	<div class="form-group">
		<label class="col-md-3 control-label"><?= $this->Label->get('RubricCriterias.criterias'); ?></label>
		<div class="col-md-6">
			<table class="table table-striped table-hover table-bordered table-checkable table-input">
				<thead>
					<tr>
						<th><?= $this->Label->get('general.name'); ?></th>
						<th><?= $this->Label->get('general.description'); ?></th>
						<th><?= $this->Label->get('RubricTemplateOptions.weighting'); ?></th>
					</tr>
				</thead>
				<?php if (!empty($this->request->data['RubricCriterias']['rubric_criteria_options'])) : ?>
					<tbody>
						<?php foreach ($this->request->data['RubricCriterias']['rubric_criteria_options'] as $key => $obj) : ?>
							<tr>
								<td>
									<?php
										if(isset($obj['id'])) {
											echo $this->Form->hidden("RubricCriterias.rubric_criteria_options.$key.id");
										}
										echo $this->Form->hidden("RubricCriterias.rubric_criteria_options.$key.rubric_template_option_id");
										echo $this->Form->hidden("RubricCriterias.rubric_criteria_options.$key.rubric_template_option_name");
										echo $this->Form->hidden("RubricCriterias.rubric_criteria_options.$key.rubric_template_option_weighting");
									?>
									<?= $obj['rubric_template_option_name']; ?>
								</td>
								<td><?= $this->Form->input("RubricCriterias.rubric_criteria_options.$key.name", ['type' => 'textarea', 'label' => false]); ?></td>
								<td><?= $obj['rubric_template_option_weighting']; ?></td>
							</tr>
						<?php endforeach ?>
					</tbody>
				<?php endif ?>
			</table>
		</div>
	</div>
<?php endif ?>
