<?php if(!empty($tableData)) : ?>
<div class="form-group">
	<label class="col-md-3 control-label">&nbsp;</label>
	<div class="col-md-7">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr><?php echo $this->Html->tableHeaders($tableHeaders); ?></tr>
				</thead>
				<tbody><?php echo $this->Html->tableCells($tableData); ?></tbody>
			</table>
		</div>
	</div>
</div>
<?php endif; ?>