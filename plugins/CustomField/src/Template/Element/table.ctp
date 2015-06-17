<?php $CustomFields = $attr['model']; ?>
<?php if ($action == 'view') : ?>
	<?php if (!empty($data->custom_table_columns)) : ?>
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th></th>
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
		<fieldset class="section_group">
			<div class="table-responsive">
				<table class="table table-striped table-hover table-bordered">
					<thead>
						<tr>
							<th>
								<div>
									<a style="cursor:pointer;" onclick="$('#reload').val('addColumn').click()"><i class="fa fa-plus"></i>&nbsp;<?= __('Column'); ?></a>
									<br>
									<a style="cursor:pointer;" onclick="$('#reload').val('addRow').click()"><i class="fa fa-plus"></i>&nbsp;<?= __('Row'); ?></a>
								</div>
							</th>
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
											<span style="cursor: pointer;"><a onclick="jsTable.doRemoveColumn(this)"><i class="fa fa-minus-circle"></i></a></span>
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
					<tbody>
						<?php if (!empty($data->custom_table_rows)) : ?>
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
											<span style="cursor: pointer;"><a onclick="jsTable.doRemove(this)"><i class="fa fa-minus-circle"></i></a></span>
										</td>
										<?php
											if(!empty($data->custom_table_columns)) :
												foreach ($data->custom_table_columns as $key => $obj) {
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
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</fieldset>
	</div>
<?php endif ?>
