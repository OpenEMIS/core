<?php $CustomFields = $attr['model']; ?>
<?php if ($action == 'view') : ?>
	<?php if (!empty($data->custom_table_columns)) : ?>
		<div class="table-in-view table-responsive">
			<table class="table table-striped table-hover table-bordered">
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
	<?php endif ?>
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
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
		<label>Create Table</label>
		<div class="table-toolbar">
			<button onclick="$('#reload').val('addRow').click();return false;" class="btn btn-default btn-xs">
				<i class="fa fa-plus"></i> 
				<span><?= __('Add Rows')?></span>
			</button>
			<button onclick="$('#reload').val('addColumn').click();return false;" class="btn btn-default btn-xs">
				<i class="fa fa-plus"></i> 
				<span><?= __('Add Columns')?></span>
			</button>
		</div>
		<?php if (!empty($data->custom_table_columns) || !empty($data->custom_table_rows)) : ?>
			<div class="table-in-view col-md-4 table-responsive">
				<table class="table table-striped table-hover table-bordered table-checkable table-input">
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
													echo $this->Form->hidden("$CustomFields.custom_table_columns.$key.id");
												}
												echo $this->Form->input("$CustomFields.custom_table_columns.$key.name", ['label' => false]);
												echo $this->Form->hidden("$CustomFields.custom_table_columns.$key.order", ['value' => $columnOrder]);
												echo $this->Form->hidden("$CustomFields.custom_table_columns.$key.visible");
											?>
											<button class="btn btn-xs btn-reset" onclick="jsTable.doRemoveColumn(this);"><i class="fa fa-close"></i></button>
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
													echo $this->Form->hidden("$CustomFields.custom_table_rows.$key.id");
												}
												echo $this->Form->input("$CustomFields.custom_table_rows.$key.name", ['label' => false]);
												echo $this->Form->hidden("$CustomFields.custom_table_rows.$key.order", ['value' => $rowOrder]);
												echo $this->Form->hidden("$CustomFields.custom_table_rows.$key.visible");
											?>
											<button class="btn btn-xs btn-reset" onclick="jsTable.doRemove(this);"><i class="fa fa-close"></i></button>
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
		<?php endif; ?>
	</div>
<?php endif ?>
