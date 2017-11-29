<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php if ($action == 'add' || $action == 'edit') : ?>

<div class="input required">
	<label for="<?= $attr['id'] ?>"><?= $this->Label->get($attr['model'] .'.'. $attr['field']) ?></label>
	<div class="table-wrapper">
		<div class="table-in-view">
			<table class="table table-checkable">
				<thead>
					<tr>
						<th class="checkbox-column"><input type="checkbox"
							<?php //logic to tick checkbox on the header if all checkboxes selected, this is used after validation process.
								foreach ($attr['data'] as $i=>$obj) {
									$classGradeArray[$i] = $attr['data'][$i]['education_grade_id'];
								}
								if (isset($attr['selected']) && ($classGradeArray == $attr['selected'])) {
									echo " checked";
								};
							?> 
							class='no-selection-label'
							kd-checkbox-radio
							/>
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
						<?php
							$alias = $attr['model'] . ".education_grades._ids.%d";
							echo $this->Form->checkbox(sprintf($alias, $i), ['value' => $obj->education_grade_id, 'checked' => $selected, 'class' => 'no-selection-label', 'kd-checkbox-radio' => '']);
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
