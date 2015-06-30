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

			<tbody>
			<?php foreach ($attr['data'] as $i => $obj) : ?>
				<tr>
					<td class="checkbox-column">
						<input type="checkbox" class="icheck-input" name="<?= sprintf('InstitutionSiteProgrammes[institution_site_grades][%d][education_subject_id]', $i) ?>" value="<?= $obj->id ?>" />
						<!-- <input type="hidden" name="<?php echo sprintf('InstitutionSiteProgrammes[institution_site_grades][%d][id]', $i) ?>" value="<?php echo $institutionSiteGradeId?>" /> -->
					</td>
					<td><?= $obj->code ?></td>
					<td><?= $obj->name ?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
</div>

<?php else : ?>

<?php endif ?>
