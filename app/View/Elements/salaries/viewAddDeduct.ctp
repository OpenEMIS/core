<div class="row">
	<div class="col-md-3"><?php echo $title; ?></div>
	<div class="col-md-9">
		<?php
		$tableHeader = array(__('Type'), __('Amount'));
		$tableData = array();

		foreach ($data as $obj) {
			$row = array();
			$row[] = $options[$obj['type_id']];
			$row[] = array($obj['amount'], array('class' => 'cell-number'));
			$tableData[] = $row;
		}
		
		$tableFooter = array(
			array(
				array(__('Total'), array('class' => 'cell-number')),
				array(empty($totalAmt)? 0 :$totalAmt , array('class' => 'cell-number'))
		));
		?>
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr><?php echo $this->Html->tableHeaders($tableHeader); ?></tr>
				</thead>
				<tbody><?php echo $this->Html->tableCells($tableData); ?></tbody>
				<tfoot><?php echo $this->Html->tableCells($tableFooter); ?></tfoot>
			</table>
		</div>
	</div>
</div>