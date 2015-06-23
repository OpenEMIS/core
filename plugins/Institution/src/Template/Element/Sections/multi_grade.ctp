<?= $this->Html->css('OpenEmis.../plugins/icheck/skins/minimal/blue', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/icheck/jquery.icheck.min', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php if ($action == 'add' || $action == 'edit') : ?>

<div class="input clearfix">
	<label class="pull-left" for="<?= $attr['id'] ?>"><?= $this->ControllerAction->getLabel($attr['model'], $attr['field'], $attr) ?></label>
	<div class="table-in-view col-md-5 table-responsive">
		<table class="table table-striped table-hover table-bordered table-checkable">
			<thead>
				<tr>
					<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
					<th><?= __('Education Programme') ?></th>
					<th><?= __('Education Grade') ?></th>
				</tr>
			</thead>

			<?php if (isset($attr['data'])) : ?>
			<?php //pr($attr['data']);?>
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
	foreach ($attr['data']['grades'] as $grade) {
		// pr($grade);die;
		echo $grade->name.'<br/>';
	}
	// pr($attr['data']['grades']);
?>

<?php endif ?>
