<?php $CustomFields = $attr['model']; ?>
<?php if ($action == 'view') : ?>
	<div class="table-in-view">
		<table class="table">
			<thead>
				<tr>
					<th><?= $this->Label->get('general.visible'); ?></th>
					<th><?= $this->Label->get('general.name'); ?></th>
					<th><?= $this->Label->get('general.default'); ?></th>
				</tr>
			</thead>
			<?php if (!empty($data->custom_field_options)) : ?>
				<tbody>
					<?php foreach ($data->custom_field_options as $key => $obj) : ?>
					<tr>
						<td>
							<?php if ($obj->visible == 1) : ?>
								<i class="fa fa-check"></i>
							<?php else : ?>
								<i class="fa fa-close"></i>
							<?php endif ?>
						</td>
						<td><?= $obj->name; ?></td>
						<td>
							<?php if ($obj->is_default == 1) : ?>
								<i class="fa fa-check"></i>
							<?php else : ?>
								<i class="fa fa-close"></i>
							<?php endif ?>
						</td>
					</tr>
					<?php endforeach ?>
				</tbody>
			<?php endif ?>
		</table>
	</div>
<?php else : ?>
	<div class="input">
		<label for="<?= $attr['id'] ?>"><?= isset($attr['label']) ? $attr['label'] : $attr['field'] ?></label>
		<div class="table-toolbar">
			<button onclick="$('#reload').val('addDropdownOption').click();return false;" class="btn btn-default btn-xs">
				<i class="fa fa-plus"></i>
				<span><?= __('Add');?></span>
			</button>
		</div>
		<div class="table-in-view">
			<table class="table table-checkable table-input">
				<thead>
					<tr>
						<?php if ($action == 'edit') : ?>
							<th><?= $this->Label->get('general.visible'); ?></th>
						<?php endif ?>
						<th><?= $this->Label->get('general.name'); ?></th>
						<th><?= $this->Label->get('general.default'); ?></th>
						<th></th>
					</tr>
				</thead>
				<?php if (!empty($data->custom_field_options)) : ?>
					<tbody>
						<?php foreach ($data->custom_field_options as $key => $obj) : ?>
							<tr>
								<?php if ($action == 'edit') : ?>
									<td class="checkbox-column">
										<?= $this->Form->checkbox("$CustomFields.custom_field_options.$key.visible", ['class' => 'icheck-input', 'checked' => $obj->visible]); ?>
									</td>
								<?php endif ?>
								<td>
									<?php
										if(isset($obj->id)) {
											echo $this->Form->hidden("$CustomFields.custom_field_options.$key.id");
										}
										echo $this->Form->input("$CustomFields.custom_field_options.$key.name", ['label' => false]);
										echo $this->Form->hidden("$CustomFields.custom_field_options.$key.is_default", ['value' => 0]);
									?>
								</td>
								<td>
									<?php
										$attributes = [
											'label' => false,
											'legend' => false,
											'hiddenField' => false,
											'class' => 'icheck-input icheckbox_minimal-grey'
										];
										if(isset($data->custom_field_options[$key]->is_default) && $data->custom_field_options[$key]->is_default == 1) {
											$attributes['value'] = $key;
										}
									?>
									<?= $this->Form->radio("$CustomFields.is_default", [$key => false], $attributes); ?>
								</td>
								<td>
									<button class="btn btn-dropdown action-toggle btn-single-action" style="cursor: pointer;" title="<?= $this->Label->get('general.delete.label'); ?>" onclick="jsTable.doRemove(this);">
										<i class="fa fa-trash"></i>&nbsp;<span><?= __('Delete')?></span>
									</button>
								</td>
							</tr>
						<?php endforeach ?>
					</tbody>
				<?php endif ?>
			</table>
		</div>
	</div>
<?php endif ?>
