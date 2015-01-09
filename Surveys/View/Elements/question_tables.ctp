<?php
	if ($this->action == 'add' || $this->action == 'edit') {
		echo $this->Form->button('SurveyTableRow', array('id' => 'SurveyTableRow', 'type' => 'submit', 'name' => 'submit', 'value' => 'SurveyTableRow', 'class' => 'hidden'));
		echo $this->Form->button('SurveyTableColumn', array('id' => 'SurveyTableColumn', 'type' => 'submit', 'name' => 'submit', 'value' => 'SurveyTableColumn', 'class' => 'hidden'));
	}
?>
<div class="row form-group">
	<div class="col-md-12">
		<fieldset class="section_group">
			<legend id="question_table_name"><?php echo $data['SurveyQuestion']['name']; ?></legend>
			<table width="100%">
				<tr>
					<td>
						<div class="table-responsive">
							<table class="table table-striped table-hover table-bordered">
								<thead>
									<tr>
										<th></th>
										<?php
										if(isset($data['SurveyTableColumn'])) :
											foreach ($data['SurveyTableColumn'] as $i => $obj) {
												if($obj['visible'] == 1) :
										?>
													<th>
														<?php
															$tableColumnOrder = $i + 1;
															if($this->action == 'edit') {
																echo $this->Form->hidden('SurveyTableColumn.' . $i . '.id', array('value' => $obj['id']));
															}
															echo $this->Form->input('SurveyTableColumn.' . $i . '.name', array('label' => false, 'div' => false, 'before' => false, 'between' => false, 'after' => false, 'value' => $obj['name']));
															echo $this->Form->hidden('SurveyTableColumn.' . $i . '.order', array('value' => $tableColumnOrder));
															echo $this->Form->hidden('SurveyTableColumn.' . $i . '.visible', array('value' => $obj['visible']));
															echo $this->Form->hidden('SurveyTableColumn.' . $i . '.survey_question_id', array('value' => $obj['survey_question_id']));
														?>
														<span class="icon_delete" title="<?php echo __('Delete'); ?>" onclick="jsTable.doRemoveColumn(this)"></span>
													</th>
										<?php
												endif;
											}
										endif;
										?>
									</tr>
								</thead>
								<tbody>
									<?php
									if(isset($data['SurveyTableRow'])) :
										foreach ($data['SurveyTableRow'] as $i => $obj) {
											if($obj['visible'] == 1) :
									?>
												<tr>
													<td>
														<?php
															$tableRowOrder = $i + 1;
															if($this->action == 'edit') {
																echo $this->Form->hidden('SurveyTableRow.' . $i . '.id', array('value' => $obj['id']));
															}
															echo $this->Form->input('SurveyTableRow.' . $i . '.name', array('label' => false, 'div' => false, 'before' => false, 'between' => false, 'after' => false, 'value' => $obj['name']));
															echo $this->Form->hidden('SurveyTableRow.' . $i . '.order', array('value' => $tableRowOrder));
															echo $this->Form->hidden('SurveyTableRow.' . $i . '.visible', array('value' => $obj['visible']));
															echo $this->Form->hidden('SurveyTableRow.' . $i . '.survey_question_id', array('value' => $obj['survey_question_id']));
														?>
														<span class="icon_delete" title="<?php echo __('Delete'); ?>" onclick="jsTable.doRemove(this)"></span>
													</td>
													<?php
													if(isset($data['SurveyTableColumn'])) :
														foreach ($data['SurveyTableColumn'] as $i => $obj) {
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
											endif;
										}
									endif;
									?>
								</tbody>
							</table>
						</div>
					</td>
					<td class="cell-delete" valign="top">
						<a class="void icon_plus" onclick="$('#SurveyTableColumn').click()"><?php echo __('Add Column'); ?></a>
					</td>
				</tr>
				<tr>
					<td class="cell-delete">
						<a class="void icon_plus" onclick="$('#SurveyTableRow').click()"><?php echo __('Add Row'); ?></a>
					</td>
					<td></td>
				</tr>
			</table>
		</fieldset>
	</div>
</div>