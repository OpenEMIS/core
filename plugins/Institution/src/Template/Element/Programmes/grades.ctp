<?= $this->Html->css('OpenEmis.../plugins/icheck/skins/minimal/blue', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/icheck/jquery.icheck.min', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php if ($action == 'add' || $action == 'edit') : ?>

<div class="input clearfix">
	<label class="pull-left" for="<?= $attr['id'] ?>"><?= $this->ControllerAction->getLabel($attr['model'], $attr['field'], $attr) ?></label>
	<div class="table-in-view col-md-4 table-responsive">
		<table class="table table-striped table-hover table-bordered table-checkable">
			<thead>
				<tr>
					<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
					<th><?= __('Code') ?></th>
					<th><?= __('Name') ?></th>
				</tr>
			</thead>

			<?php if (isset($attr['data'])) : ?>

			<tbody>
				<?php foreach ($attr['data'] as $i=>$obj) : ?>
				<?php 
					$selected = false;
					$institutionSiteGradeId = false;
					if (isset($attr['selected']) && array_key_exists($obj->id, $attr['selected'])) {
						$selected = true;
					} 
					if (isset($attr['recorded']) && array_key_exists($obj->id, $attr['recorded'])) {
						$institutionSiteGradeId = $attr['recorded'][$obj->id];
					}
				?>
				<tr>
					<td class="checkbox-column">
						<input type="checkbox" class="icheck-input" name="<?php echo sprintf('InstitutionSiteProgrammes[institution_site_grades][%d][education_grade_id]', $i) ?>" value="<?php echo $obj->id?>" <?php echo ($selected) ? 'checked' : '';?> />
						<input type="hidden" name="<?php echo sprintf('InstitutionSiteProgrammes[institution_site_grades][%d][id]', $i) ?>" value="<?php echo $institutionSiteGradeId?>" />
					</td>
					<td><?= $obj->code ?></td>
					<td><?= $obj->name ?></td>
				</tr>
				<?php endforeach ?>
			</tbody>
			
			<?php else : ?>
				<tr>&nbsp;</tr>
			<?php endif; ?>

		</table>
	</div>
</div>

<?php else : ?>

<?php 
	$grades = [];
	foreach ($data->institution_site_grades as $grade) {
		$grades[] = $grade->education_grade->name;
	}
	echo implode(', ', $grades);
?>

<?php endif ?>
