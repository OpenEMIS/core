
<div class="form-group">
	<label class="col-md-3 control-label"><?php echo $labelText; ?></label>
	<div class="col-md-9">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered" id='<?php echo isset($tableId)?$tableId :''; ?>'>
				<thead>
					<tr><?php echo $this->Html->tableHeaders($tableHeaders); ?></tr>
				</thead>
				<tbody><?php echo $this->Html->tableCells($tableData); ?></tbody>

				<tfoot><?php echo $this->Html->tableCells($tableFooter); ?></tfoot>
			</table>
		</div>

<?php
if (isset($addIconData)) {
	$addIconDefaultSetting = array('class' => 'void icon_plus', 'addIconURL' => $addIconData['url']);

	if (isset($addIconData['onclick'])) {
		$addIconDefaultSetting['onclick'] = $addIconData['onclick'];
	}
	echo $this->Html->link($this->Label->get('general.add'), array(), $addIconDefaultSetting);
}
?>
	</div>
</div>

