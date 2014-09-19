<?php
echo $this->Html->css('../js/plugins/icheck/skins/minimal/blue', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/tableCheckable/jquery.tableCheckable', false);
echo $this->Html->script('plugins/icheck/jquery.icheck.min', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get("$model.title"));
$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, $_condition => $conditionId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $model, 'edit', $_condition => $conditionId));
echo $this->Form->create($model, $formOptions);
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered table-checkable table-input">
		<thead>
			<tr>
				<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
				<th><?php echo $this->Label->get('general.name') ?></th>
				<th><?php echo $this->Label->get('general.code') ?></th>
				<th class="cell-hours-required"><?php echo $this->Label->get('EducationGradeSubject.hours_required') ?></th>
			</tr>
		</thead>
		
		<tbody>
			<?php 
			foreach($data as $i => $obj) :
				$checked = !empty($obj[$model]['visible']);
				
				if($obj['EducationSubject']['visible']!=1 && !$checked) {
					continue;
				}
			?>
			<tr>
				<td class="checkbox-column">
					<?php
					echo $this->Form->hidden($i . '.id', array('value' => $obj[$model]['id']));
					echo $this->Form->hidden($i . '.education_grade_id', array('value' => $conditionId));
					echo $this->Form->hidden($i . '.education_subject_id', array('value' => $obj['EducationSubject']['id']));
					echo $this->Form->checkbox($i . '.visible', array('class' => 'icheck-input', 'checked' => $checked));
					?>
				</td>
				<td><?php echo $obj['EducationSubject']['name'] ?></td>
				<td><?php echo $obj['EducationSubject']['code'] ?></td>
				<td>
					<?php
					echo $this->Form->input($i . '.hours_required', array(
						'label' => false,
						'div' => false,
						'before' => false,
						'between' => false,
						'after' => false,
						'value' => $obj[$model]['hours_required']
					));
					?>
				</td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<div class="controls">
	<input type="submit" value="<?php echo $this->Label->get('general.save'); ?>" class="btn_save btn_right" />
	<?php echo $this->Html->link($this->Label->get('general.cancel'), array('action' => $model, $_condition => $conditionId), array('class' => 'btn_cancel btn_left')); ?>
</div>

<?php echo $this->Form->end() ?>
<?php $this->end() ?>
