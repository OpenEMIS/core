<?php
	echo $this->Html->css('../js/plugins/icheck/skins/minimal/blue', 'stylesheet', array('inline' => false));
	echo $this->Html->script('plugins/tableCheckable/jquery.tableCheckable', false);
	echo $this->Html->script('plugins/icheck/jquery.icheck.min', false);
?>
<?php
	if ($this->action == 'add' || $this->action == 'edit') {
		echo $this->Form->button('SurveyQuestionChoice', array('id' => 'SurveyQuestionChoice', 'type' => 'submit', 'name' => 'submit', 'value' => 'SurveyQuestionChoice', 'class' => 'hidden'));
	}
?>

<div class="form-group">
	<label class="col-md-3 control-label"><?php echo __('Choice');?></label>
	<div class="col-md-6">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered table-checkable table-input">
				<thead>
					<tr>
						<?php if ($this->action == 'edit') : ?>
						<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
						<?php endif ?>
						<th><?php echo __('Name'); ?></th>
						<?php if ($this->action == 'add') : ?>
						<th class="cell-delete"></th>
						<?php endif ?>
					</tr>
				</thead>
				<tbody>
					<?php
					if (($this->action == 'add' || $this->action == 'edit') && isset($data['SurveyQuestionChoice'])) :
						foreach ($data['SurveyQuestionChoice'] as $i => $obj) :
					?>
						<tr>
							<?php if ($this->action == 'edit') : ?>
							<td class="checkbox-column">
								<?php
									echo $this->Form->checkbox('SurveyQuestionChoice.'.$i.'.visible', array('class' => 'icheck-input', 'checked' => $obj['visible']));
								?>
							</td>
							<?php endif ?>
							<td>
								<?php
									echo $this->Form->hidden('SurveyQuestionChoice.'.$i.'.id', array('value' => $obj['id']));
									echo $this->Form->hidden('SurveyQuestionChoice.'.$i.'.default_choice', array('value' => $obj['default_choice']));
									echo $this->Form->hidden('SurveyQuestionChoice.'.$i.'.order', array('value' => $i));
									echo $this->Form->hidden('SurveyQuestionChoice.'.$i.'.visible', array('value' => $obj['visible']));
									echo $this->Form->hidden('SurveyQuestionChoice.'.$i.'.survey_question_id', array('value' => $obj['survey_question_id']));
									echo $this->Form->input('SurveyQuestionChoice.'.$i.'.value', array('label' => false, 'div' => false, 'between' => false, 'after' => false));
								?>
							</td>
							<?php if ($this->action == 'add') : ?>
							<td>
								<span class="icon_delete" title="<?php echo __('Delete'); ?>" onclick="jsTable.doRemove(this)"></span>
							</td>
							<?php endif ?>
						</tr>
					<?php
						endforeach;
					endif;
					?>
				</tbody>
			</table>
			<a class="void icon_plus" onclick="$('#SurveyQuestionChoice').click()"><?php echo __('Add'); ?></a>
		</div>
	</div>
</div>