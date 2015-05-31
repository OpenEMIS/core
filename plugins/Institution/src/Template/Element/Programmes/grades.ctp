<?php if ($action == 'add' || $action == 'edit') : ?>

<div class="input">
	<label class="pull-left" for="<?= $attr['id'] ?>"><?= $this->ControllerAction->getLabel($attr['model'], $attr['field'], $attr) ?></label>
	<div class="col-md-6">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?= __('Code') ?></th>
					<th><?= __('Name') ?></th>
				</tr>
			</thead>

			<?php if (isset($attr['data'])) : ?>
			<tbody>
				<?php foreach ($attr['data'] as $obj) : ?>
				<tr>
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
