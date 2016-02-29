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
						<th><?= $this->Label->get('InstitutionSections.education_programme') ?></th>
						<th><?= $this->Label->get('InstitutionSections.education_grade') ?></th>
					</tr>
				</thead>

				<?php if (isset($attr['data'])) : ?>
				<tbody>
					<?php foreach ($attr['data'] as $i=>$obj) : ?>
					<?php 	$selected = (isset($attr['selected']) && in_array($obj->education_grade_id, $attr['selected'])) ? true : false; ?>
					<tr>
						<td class="checkbox-column">
						<input type="checkbox" class="icheck-input" name="<?php echo sprintf('InstitutionSections[institution_section_grades][%d][education_grade_id]', $i) ?>" value="<?php echo $obj->education_grade_id?>" <?php echo ($selected) ? 'checked' : '';?> />
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
