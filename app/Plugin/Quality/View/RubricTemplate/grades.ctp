<?php if ($action == 'view') : ?>
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th class="cell-visible"><?php echo $this->Label->get('general.visible'); ?></th>
					<th><?php echo $this->Label->get('general.name'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($data['RubricTemplateGrade'] as $obj) : ?>
				<tr>
					<td class="center"><?php echo $this->Utility->checkOrCrossMarker($obj['visible']==1); ?></td>
					<td>
						<?php
							$educationGradeName = isset($educationGrades[$obj['education_grade_id']]) ? $educationGrades[$obj['education_grade_id']] : '';
							echo $educationGradeName;
						?>
					</td>
				</tr>
				<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php else : ?>
	<?php
		echo $this->Html->css('../js/plugins/icheck/skins/minimal/blue', 'stylesheet', array('inline' => false));
		echo $this->Html->script('plugins/tableCheckable/jquery.tableCheckable', false);
		echo $this->Html->script('plugins/icheck/jquery.icheck.min', false);
	?>

	<div class="form-group">
		<label class="col-md-3 control-label"><?php echo $this->Label->get('RubricTemplate.grades');?></label>
		<div class="col-md-6">
			<table class="table table-striped table-hover table-bordered table-checkable table-input">
				<thead>
					<tr>
						<?php if ($this->action == 'edit') : ?>
							<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
						<?php endif ?>
							<th><?php echo $this->Label->get('general.name'); ?></th>
						<?php if ($this->action == 'add') : ?>
							<th class="cell-delete"></th>
						<?php endif ?>
					</tr>
				</thead>
				<tbody>
					<?php
					if (isset($this->request->data['RubricTemplateGrade'])) :
						foreach ($this->request->data['RubricTemplateGrade'] as $key => $obj) :
					?>
						<tr>
							<?php if ($this->action == 'edit') : ?>
								<td class="checkbox-column">
									<?php
										echo $this->Form->checkbox("RubricTemplateGrade.$key.visible", array('class' => 'icheck-input', 'checked' => $obj['visible']));
									?>
								</td>
							<?php endif ?>
								<td>
									<?php
										//to handle add new rows in edit mode
										if(isset($this->request->data['RubricTemplateGrade'][$key]['id'])) {
											echo $this->Form->hidden("RubricTemplateGrade.$key.id");
										}
										echo $this->Form->input("RubricTemplateGrade.$key.education_grade_id", array('options' => $educationGradeOptions, 'label' => false, 'div' => false, 'between' => false, 'after' => false));
									?>
								</td>
							<?php if ($this->action == 'add') : ?>
								<td>
									<span class="icon_delete" title="<?php echo $this->Label->get('general.delete'); ?>" onclick="jsTable.doRemove(this)"></span>
								</td>
							<?php endif ?>
						</tr>
					<?php
						endforeach;
					endif;
					?>
				</tbody>
			</table>
			<a class="void icon_plus" onclick="$('#reload').val('RubricTemplateGrade').click()"><?php echo $this->Label->get('general.add'); ?></a>
		</div>
	</div>
<?php endif ?>
