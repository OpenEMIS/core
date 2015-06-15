<?= $this->Html->css('OpenEmis.../plugins/icheck/skins/minimal/blue', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/icheck/jquery.icheck.min', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php if ($action == 'add' || $action == 'edit') : ?>

<div class="input clearfix">
	<label class="pull-left" for="<?= $attr['id'] ?>"><?= $this->ControllerAction->getLabel($attr['model'], $attr['field'], $attr) ?></label>
	<div class="col-md-5">
		<table class="table table-striped table-hover table-bordered table-checkable">
			<thead>
				<tr>
					<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
					<th><?= __('Education Programme') ?></th>
					<th><?= __('Education Grade') ?></th>
				</tr>
			</thead>

			<?php if (isset($attr['data'])) : ?>
			<tbody>
				<?php foreach ($attr['data'] as $i=>$obj) : ?>
				<tr>
					<td class="checkbox-column">
						<input type="checkbox" class="icheck-input" name="<?php echo sprintf('MultiGrades[%d][id]', $i) ?>" value="<?php echo $obj->id?>" />
					</td>
					<td><?= $obj->education_programme->name ?></td>
					<td><?= $obj->name ?></td>
				</tr>
				<?php endforeach ?>
			</tbody>
			<?php endif ?>
		</table>
	</div>
</div>

<?php else : ?>



<?php endif ?>
