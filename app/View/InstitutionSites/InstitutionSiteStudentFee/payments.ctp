<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.date') ?></th>
				<th><?php echo $this->Label->get('general.created_by') ?></th>
				<th><?php echo $this->Label->get('general.comment') ?></th>
				<th style="width: 110px;"><?php echo sprintf('%s (%s)', $this->Label->get('FinanceFee.paid'), $currency) ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		$paid = 0;
		if (!empty($data[$model]['payments'])) :
			foreach ($data[$model]['payments'] as $i => $obj) : 
				$paid += $obj[$model]['amount'];
				$date = $obj[$model]['payment_date'];
				if ($this->params['controller'] == 'InstitutionSites') {
					$date = $this->Html->link($obj[$model]['payment_date'], array('action' => $model, 'view', $obj[$model]['id']));
				}
		?>
			<tr>
				<td><?php echo $date ?></td>
				<td><?php echo trim($obj['CreatedUser']['first_name'] . ' - ' . $obj['CreatedUser']['last_name']) ?></td>
				<td><?php echo $obj[$model]['comments'] ?></td>
				<td class="cell-number"><?php echo $obj[$model]['amount'] ?></td>
			</tr>
		<?php
			endforeach;
		endif;
		?>
		</tbody>
		
		<?php if (!empty($data[$model]['payments'])) : ?>
		
		<tfoot>
			<td class="cell-number bold" colspan="3"><?php echo $this->Label->get('general.total') ?></td>
			<td class="cell-number bold"><?php echo number_format($paid, 2) ?></td>
		</tfoot>
		
		<?php endif ?>
	</table>
</div>
