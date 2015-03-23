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
				<?php foreach ($rubricCriteriaOptions as $obj) : ?>
				<tr>
					<td><?php echo ($obj['RubricTemplateOption']['name']); ?></td>
					<td><?php echo ($obj['RubricCriteriaOption']['name']); ?></td>
					<td><?php echo ($obj['RubricTemplateOption']['weighting']); ?></td>
				</tr>
				<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php else : ?>
	<div class="form-group">
		<label class="col-md-3 control-label"><?php echo $this->Label->get('RubricCriteria.criterias');?></label>
		<div class="col-md-6">
			<table class="table table-striped table-hover table-bordered table-checkable table-input">
				<thead>
					<tr>
						<th><?php echo $this->Label->get('general.name'); ?></th>
						<th><?php echo $this->Label->get('general.description'); ?></th>
						<th><?php echo $this->Label->get('RubricTemplateOption.weighting'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if (!empty($rubricTemplateOptions)) : ?>
						<?php foreach ($rubricTemplateOptions as $obj) : ?>
							<tr>
								<td>
									<?php
										$key = $obj['RubricTemplateOption']['id'];
										//to handle add new rows in edit mode
										if(isset($this->request->data['RubricCriteriaOption'][$key]['id'])) {
											echo $this->Form->hidden("RubricCriteriaOption.$key.id");
										}
										echo $obj['RubricTemplateOption']['name'];
									?>
								</td>
								<td>
									<?php
										echo $this->Form->hidden("RubricCriteriaOption.$key.rubric_template_option_id", array('label' => false, 'div' => false, 'between' => false, 'after' => false));
										echo $this->Form->input("RubricCriteriaOption.$key.name", array('type' => 'textarea', 'label' => false, 'div' => false, 'between' => false, 'after' => false));
									?>
								</td>
								<td>
									<?php echo $obj['RubricTemplateOption']['weighting']; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif ?>
				</tbody>
			</table>
		</div>
	</div>
<?php endif ?>
