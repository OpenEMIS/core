<?= $this->Html->css('OpenEmis.../plugins/icheck/skins/minimal/blue', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/icheck/jquery.icheck.min', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php if ($action == 'add' || $action == 'edit') : ?>

<div class="input clearfix">
	<?php if ($this->ControllerAction->locale() == 'ar'): ?>
	<label class="pull-right" for="<?= $attr['id'] ?>"><?= $this->Label->get($attr['model'] .'.'. $attr['field']) ?></label>
	<?php else: ?>
	<label class="pull-left" for="<?= $attr['id'] ?>"><?= $this->Label->get($attr['model'] .'.'. $attr['field']) ?></label>
	<?php endif; ?>
	<div class="table-in-view">
		<table class="table table-striped table-hover table-bordered table-checkable">
			<thead>
				<tr>
					<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
					<th><?= $this->Label->get('InstitutionSiteSections.education_programme') ?></th>
					<th><?= $this->Label->get('InstitutionSiteSections.education_grade') ?></th>
				</tr>
			</thead>

			<?php if (isset($attr['data'])) : ?>
			<tbody>
				<?php foreach ($attr['data'] as $i=>$obj) : ?>
				<?php 	$selected = (isset($attr['selected']) && in_array($obj->education_grade_id, $attr['selected'])) ? true : false; ?>
				<tr>
					<td class="checkbox-column">
						<input type="checkbox" class="icheck-input" name="<?php echo sprintf('InstitutionSiteSections[institution_site_section_grades][%d][education_grade_id]', $i) ?>" value="<?php echo $obj->education_grade_id?>" <?php echo ($selected) ? 'checked' : '';?> />
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

<?php else : ?>

<?php 
	$grades = [];
	foreach ($attr['data']['grades'] as $grade) {
		$grades[] = $grade->name;
	}
	echo implode(', ', $grades);
?>

<?php endif ?>
