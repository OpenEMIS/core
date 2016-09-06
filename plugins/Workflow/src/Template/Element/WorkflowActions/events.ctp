<?php if ($action == 'view') : ?>
	<?php
		$tableHeaders = isset($attr['attr']['tableHeaders']) ? $attr['attr']['tableHeaders'] : [];
		$tableCells = isset($attr['attr']['tableCells']) ? $attr['attr']['tableCells'] : [];
	?>
	<div class="table-wrapper">
		<div class="table-in-view">
			<table class="table">
				<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
				<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
			</table>
		</div>
	</div>
<?php elseif ($action == 'add' || $action == 'edit') : ?>
	<?php
		$model = $ControllerAction['table'];
		$eventOptions = isset($attr['attr']['eventOptions']) ? $attr['attr']['eventOptions'] : [];
		$this->Form->unlockField("WorkflowActions.events");
	?>
	<div class="input">
		<label><?= isset($attr['label']) ? $attr['label'] : $attr['field']; ?></label>
		<div class="input-form-wrapper">
			<div class="table-toolbar">
				<a class="btn btn-default" href="#" onclick="$('#reload').val('addEvent').click();return false;">
					<?= __('Add');?>
				</a>
			</div>
			<div class="table-wrapper">
				<div class="table-in-view">
					<table class="table">
						<thead>
							<tr>
								<th><?= $this->Label->get('general.name'); ?></th>
								<th></th>
							</tr>
						</thead>
						<?php if (!empty($data->events)) : ?>
							<tbody>
								<?php foreach ($data->events as $key => $obj) : ?>
									<?php
										$prefix = $model->alias().'.events.'.$key;
									?>
									<tr class="checked">
										<td>
											<?= $this->Form->input("$prefix.event_key", ['label' => false, 'options' => $eventOptions]); ?>
										</td>
										<td>
											<a class="btn btn-dropdown action-toggle btn-single-action" title="<?= $this->Label->get('general.delete.label'); ?>" href="#" onclick="jsTable.doRemove(this);"><i class="fa fa-trash"></i><span><?= __('Delete')?></span></a>
										</td>
									</tr>
								<?php endforeach ?>
							</tbody>
						<?php endif ?>
					</table>
				</div>
			</div>
		</div>
	</div>
<?php endif ?>
