<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered <?php  echo isset($tableClass)? $tableClass : ''; ?>">
		<thead>
			<tr><?php echo $this->Html->tableHeaders($tableHeaders); ?></tr>
		</thead>
		<tbody><?php echo $this->Html->tableCells($tableData); ?></tbody>
	</table>
</div>
