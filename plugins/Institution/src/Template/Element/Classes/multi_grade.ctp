<?= $this->Html->css('OpenEmis.../plugins/icheck/skins/minimal/blue', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/icheck/jquery.icheck.min', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php if ($action == 'add' || $action == 'edit') : ?>

<div class="input required">
	<label for="<?= $attr['id'] ?>"><?= $this->Label->get($attr['model'] .'.'. $attr['field']) ?></label>
	<div class="table-wrapper">
		<div class="table-in-view">
			<table class="table table-checkable">
				<thead>
					<tr>
						<th class="checkbox-column"><input type="checkbox" class="icheck-input" 
							<?php //logic to tick checkbox on the header if all checkboxes selected, this is used after validation process.
								foreach ($attr['data'] as $i=>$obj) {
									$classGradeArray[$i] = $attr['data'][$i]['education_grade_id'];
								}
								if (isset($attr['selected']) && ($classGradeArray == $attr['selected'])) {
									echo " checked";
								};
							?> />
						</th>
						<th><?= $this->Label->get($attr['model'].'.education_programme') ?></th>
						<th><?= $this->Label->get($attr['model'].'.education_grade') ?></th>
					</tr>
				</thead>

				<?php if (isset($attr['data'])) : ?>
				<tbody>
					<?php 
						foreach ($attr['data'] as $i=>$obj) : ?>
					<?php 	$selected = (isset($attr['selected']) && in_array($obj->education_grade_id, $attr['selected'])) ? true : false; ?>
					<tr>
						<td class="checkbox-column">
						<?= $this->Form->input(sprintf($attr['model'].'[education_grades][%d][id]', $i), [
								'type' => 'checkbox',
								'class' => 'icheck-input',
								'value' => $obj->education_grade_id,
								'label' => false,
								'checked' => $selected
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
