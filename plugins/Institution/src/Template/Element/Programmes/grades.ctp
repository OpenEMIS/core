<?= $this->Html->css('OpenEmis.../plugins/icheck/skins/minimal/blue', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/icheck/jquery.icheck.min', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php if ($action == 'add' || $action == 'edit') : ?>

<div class="input">
	<label class="pull-left" for="<?= $attr['id'] ?>"><?= $this->ControllerAction->getLabel($attr['model'], $attr['field'], $attr) ?></label>
	<div class="col-md-5">
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
				<?php foreach ($attr['data'] as $obj) : ?>
				<tr>
					<td class="checkbox-column">
						<input type="checkbox" class="icheck-input" />
						<?php
						// TODO-jeff: populate hidden fields for education grades
						?>
					</td>
					<td><?= $obj->code ?></td>
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
