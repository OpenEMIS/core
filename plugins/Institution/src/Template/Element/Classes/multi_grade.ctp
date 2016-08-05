<?= $this->Html->css('OpenEmis.../plugins/icheck/skins/minimal/blue', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/icheck/jquery.icheck.min', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php if ($action == 'add' || $action == 'edit') : ?>

<div class="input clearfix">
	<label for="<?= $attr['id'] ?>"><?= $this->Label->get($attr['model'] .'.'. $attr['field']) ?></label>
	<div class="table-wrapper">
		<div class="table-in-view">
			<table class="table table-checkable">
				<thead>
					<tr>
						<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
						<th><?= $this->Label->get($attr['model'].'.education_programme') ?></th>
						<th><?= $this->Label->get($attr['model'].'.education_grade') ?></th>
					</tr>
				</thead>

				<?php if (isset($attr['data'])) : ?>
				<tbody>
					<?php foreach ($attr['data'] as $i=>$obj) : ?>
					<?php 	$selected = (isset($attr['selected']) && in_array($obj->education_grade_id, $attr['selected'])) ? true : false; ?>
					<tr>
						<td class="checkbox-column">
						<?= $this->Form->input(sprintf('MultiSubjects[%d][education_subject_id]', $i), [
								'type' => 'checkbox',
								'class' => 'icheck-input',
								'name' => sprintf($attr['model'].'[education_grades][%d][id]', $i),
								'value' => $obj->education_grade_id,
								'label' => false
							]);
						?>
						</td>
						<td><?= $obj->education_grade->programme_name ?></td>
						<td><?= $obj->education_grade->name ?></td>
					</tr>
					<?php endforeach ?>
				</tbody>
				<?php endif ?>
			</table>
		</div>
	</div>
</div>

<?php else : ?>

<?php 
	$grades = [];
	foreach ($attr['data']['grades'] as $grade) {
		$grades[] = $grade->name;
	}
	echo implode(', ', $grades);
?>

<?php endif ?>
