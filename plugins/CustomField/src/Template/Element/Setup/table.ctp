<?php
	$model = $ControllerAction['table'];
	$alias = $model->alias();
	$this->Form->unlockField("$alias.custom_table_columns");
	$this->Form->unlockField("$alias.custom_table_rows");
?>

<?php if ($ControllerAction['action'] == 'view') : ?>
	<?php if (!empty($data->custom_table_columns) || !empty($data->custom_table_rows)) : ?>
		<div class="table-wrapper">
			<div class="table-in-view">
				<table class="table">
					<thead>
						<tr>
							<?php foreach ($data->custom_table_columns as $col => $obj) : ?>
								<th><?= $obj->name?></th>
							<?php endforeach ?>
						</tr>
					</thead>
					<tbody>
						<?php if (!empty($data->custom_table_rows)) : ?>
							<?php foreach ($data->custom_table_rows as $row => $obj) : ?>
								<tr>
									<td><?= $obj->name?></td>
									<?php foreach ($data->custom_table_columns as $col => $obj) : ?>
										<?php
											if($col == 0) {
												continue;
											}
										?>
										<td></td>
									<?php endforeach ?>
								</tr>
							<?php endforeach ?>
						<?php endif ?>
					</tbody>
				</table>
			</div>
		</div>
	<?php endif; ?>
<?php elseif ($ControllerAction['action'] == 'add') : ?>
	<div class="input">
		<label><?= __('Create Table'); ?></label>
		<div class="input-form-wrapper">
				<div class="table-toolbar">
					<button class="btn btn-default btn-xs" onclick="$('#reload').val('addRow').click();return false;">
						<i class="fa fa-plus"></i>
						<span><?= __('Add Rows'); ?></span>
					</button>
					<button class="btn btn-default btn-xs" onclick="$('#reload').val('addColumn').click();return false;">
						<i class="fa fa-plus"></i>
						<span><?= __('Add Columns'); ?></span>
					</button>
				</div>
			<?php if (!empty($data->custom_table_columns) || !empty($data->custom_table_rows)) : ?>
				<div class="table-wrapper">
					<div class="table-in-view">
						<table class="table">
							<thead>
								<tr>
									<?php if (!empty($data->custom_table_columns)) : ?>
										<?php
											$columnOrder = 1;
											foreach ($data->custom_table_columns as $key => $obj) {
												if($obj->visible == 1) :
										?>
											<th>
												<div>
													<?php
														if(isset($obj->id)) {
															echo $this->Form->hidden("$alias.custom_table_columns.$key.id");
														}
														echo $this->Form->input("$alias.custom_table_columns.$key.name", ['label' => false]);
														echo $this->Form->hidden("$alias.custom_table_columns.$key.order", ['value' => $columnOrder]);
														echo $this->Form->hidden("$alias.custom_table_columns.$key.visible");
													?>
													<button type="button" class="btn btn-xs btn-reset" onclick="jsTable.doRemoveColumn(this);"><i class="fa fa-close"></i></button>
												</div>
											</th>
										<?php
												$columnOrder++;
												endif;
											}
										?>
									<?php endif; ?>
								</tr>
							</thead>
							<?php if (!empty($data->custom_table_rows)) : ?>
								<tbody>
									<?php
											$rowOrder = 1;
											foreach ($data->custom_table_rows as $key => $obj) {
												if($obj->visible == 1) :
										?>
											<tr>
												<td>
													<?php
														if(isset($obj->id)) {
															echo $this->Form->hidden("$alias.custom_table_rows.$key.id");
														}
														echo $this->Form->input("$alias.custom_table_rows.$key.name", ['label' => false]);
														echo $this->Form->hidden("$alias.custom_table_rows.$key.order", ['value' => $rowOrder]);
														echo $this->Form->hidden("$alias.custom_table_rows.$key.visible");
													?>
													<button type="button" class="btn btn-xs btn-reset" onclick="jsTable.doRemove(this);"><i class="fa fa-close"></i></button>
												</td>
												<?php
													if(!empty($data->custom_table_columns)) :
														foreach ($data->custom_table_columns as $key => $obj) {
															if($key == 0) {
																continue;
															}
															if($obj->visible == 1) :
												?>
															<td></td>
												<?php
															endif;
														}
													endif;
												?>
											</tr>
										<?php
												$rowOrder++;
												endif;
											}
										?>
								</tbody>
							<?php endif; ?>
						</table>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
<?php elseif ($ControllerAction['action'] == 'edit') : ?>
	<div class="input">
		<label class="tooltip-desc"><?= __('Create Table'); ?>
			<?php if (!$data->editable) : ?>
				<i class="fa fa-info-circle fa-lg icon-blue" tooltip-placement="bottom" uib-tooltip="<?= __('This question is in use') ?>" tooltip-append-to-body="true" tooltip-class="tooltip-blue"></i>
			<?php endif; ?>
		</label>
		<div class="input-form-wrapper">
			<?php if ($data->editable) : ?>
				<div class="table-toolbar">
					<button class="btn btn-default btn-xs" onclick="$('#reload').val('addRow').click();return false;">
						<i class="fa fa-plus"></i>
						<span><?= __('Add Rows'); ?></span>
					</button>
					<button class="btn btn-default btn-xs" onclick="$('#reload').val('addColumn').click();return false;">
						<i class="fa fa-plus"></i>
						<span><?= __('Add Columns'); ?></span>
					</button>
				</div>
			<?php endif; ?>
			<?php if (!empty($data->custom_table_columns) || !empty($data->custom_table_rows)) : ?>
				<div class="table-wrapper">
					<div class="table-in-view">
						<table class="table">
							<thead>
								<tr>
									<?php if (!empty($data->custom_table_columns)) : ?>
										<?php
											$columnOrder = 1;
											foreach ($data->custom_table_columns as $key => $obj) {
												if($obj->visible == 1) :
										?>
											<th>
												<div>
													<?php
														if(isset($obj->id)) {
															echo $this->Form->hidden("$alias.custom_table_columns.$key.id");
														}
														echo $this->Form->input("$alias.custom_table_columns.$key.name", ['label' => false]);
														echo $this->Form->hidden("$alias.custom_table_columns.$key.order", ['value' => $columnOrder]);
														echo $this->Form->hidden("$alias.custom_table_columns.$key.visible");
													?>
													<?php if ($data->editable) : ?>
														<button type="button" class="btn btn-xs btn-reset" onclick="jsTable.doRemoveColumn(this);"><i class="fa fa-close"></i></button>
													<?php endif; ?>
												</div>
											</th>
										<?php
												$columnOrder++;
												endif;
											}
										?>
									<?php endif; ?>
								</tr>
							</thead>
							<?php if (!empty($data->custom_table_rows)) : ?>
								<tbody>
									<?php
											$rowOrder = 1;
											foreach ($data->custom_table_rows as $key => $obj) {
												if($obj->visible == 1) :
										?>
											<tr>
												<td>
													<?php
														if(isset($obj->id)) {
															echo $this->Form->hidden("$alias.custom_table_rows.$key.id");
														}
														echo $this->Form->input("$alias.custom_table_rows.$key.name", ['label' => false]);
														echo $this->Form->hidden("$alias.custom_table_rows.$key.order", ['value' => $rowOrder]);
														echo $this->Form->hidden("$alias.custom_table_rows.$key.visible");
													?>
													<?php if ($data->editable) : ?>
														<button type="button" class="btn btn-xs btn-reset" onclick="jsTable.doRemove(this);"><i class="fa fa-close"></i></button>
													<?php endif; ?>
												</td>
												<?php
													if(!empty($data->custom_table_columns)) :
														foreach ($data->custom_table_columns as $key => $obj) {
															if($key == 0) {
																continue;
															}
															if($obj->visible == 1) :
												?>
															<td></td>
												<?php
															endif;
														}
													endif;
												?>
											</tr>
										<?php
												$rowOrder++;
												endif;
											}
										?>
								</tbody>
							<?php endif; ?>
						</table>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
<?php endif ?>
