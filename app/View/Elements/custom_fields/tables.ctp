<div class="row form-group custom-field-table">
	<div class="col-md-12">
		<fieldset class="section_group">
			<legend id="custom_table_name"><?php echo $this->request->data[$Custom_Field]['name']; ?></legend>
			<div class="table-responsive">
				<table class="table table-striped table-hover table-bordered">
					<thead>
						<tr>
							<th>
								<div>
									<a class="void icon_plus" onclick="$('#reload').val('<?php echo $Custom_TableColumn ?>').click()"><?php echo __('Add Column'); ?></a>
									<br>
									<a class="void icon_plus" onclick="$('#reload').val('<?php echo $Custom_TableRow ?>').click()"><?php echo __('Add Row'); ?></a>
								</div>
							</th>
							<?php
							if(isset($this->request->data[$Custom_TableColumn])) :
								$columnOrder = 1;
								foreach ($this->request->data[$Custom_TableColumn] as $i => $obj) {
									if($obj['visible'] == 1) :
							?>
										<th>
											<div>
												<?php
													if(isset($this->request->data[$Custom_TableColumn][$i]['id'])) {
														echo $this->Form->hidden("$Custom_TableColumn.$i.id");
													}
													echo $this->Form->input("$Custom_TableColumn.$i.name", array('label' => false, 'div' => false, 'before' => false, 'between' => false, 'after' => false, 'value' => $obj['name']));
													echo $this->Form->hidden("$Custom_TableColumn.$i.order", array('value' => $columnOrder));
													echo $this->Form->hidden("$Custom_TableColumn.$i.visible", array('value' => $obj['visible']));
												?>
												<span class="icon_delete" title="<?php echo $this->Label->get('general.delete'); ?>" onclick="jsTable.doRemoveColumn(this)"></span>
											</div>
										</th>
							<?php
									$columnOrder++;
									endif;
								}
							endif;
							?>
						</tr>
					</thead>
					<tbody>
						<?php
						if(isset($this->request->data[$Custom_TableRow])) :
							$rowOrder = 1;
							foreach ($this->request->data[$Custom_TableRow] as $i => $obj) {
								if($obj['visible'] == 1) :
						?>
									<tr>
										<td>
											<?php
												if(isset($this->request->data[$Custom_TableRow][$i]['id'])) {
													echo $this->Form->hidden("$Custom_TableRow.$i.id");
												}
												echo $this->Form->input("$Custom_TableRow.$i.name", array('label' => false, 'div' => false, 'before' => false, 'between' => false, 'after' => false, 'value' => $obj['name']));
												echo $this->Form->hidden("$Custom_TableRow.$i.order", array('value' => $rowOrder));
												echo $this->Form->hidden("$Custom_TableRow.$i.visible", array('value' => $obj['visible']));
											?>
											<span class="icon_delete" title="<?php echo $this->Label->get('general.delete'); ?>" onclick="jsTable.doRemove(this)"></span>
										</td>
										<?php
										if(isset($this->request->data[$Custom_TableColumn])) :
											foreach ($this->request->data[$Custom_TableColumn] as $i => $obj) {
												if($obj['visible'] == 1) :
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
						endif;
						?>
					</tbody>
				</table>
			</div>
		</fieldset>
	</div>
</div>