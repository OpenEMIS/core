<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered <?php  echo isset($tableClass)? $tableClass : ''; ?>">
		<thead>
			<tr><?php echo $this->Html->tableHeaders($tableHeaders); ?></tr>
		</thead>
	</table>
</div>
