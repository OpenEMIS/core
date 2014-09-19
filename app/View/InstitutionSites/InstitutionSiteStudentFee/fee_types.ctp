<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.type') ?></th>
				<th style="width: 110px;"><?php echo sprintf('%s (%s)', $this->Label->get('general.fee'), $currency) ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		if (!empty($data['InstitutionSiteFeeType'])) :
			foreach ($data['InstitutionSiteFeeType'] as $i => $obj) : ?>
			<tr>
				<td><?php echo $obj['FeeType']['name'] ?></td>
				<td class="cell-number"><?php echo $obj['amount'] ?></td>
			</tr>
		<?php
			endforeach;
		endif;
		?>
		</tbody>
		
		<?php if (!empty($data['InstitutionSiteFeeType'])) : ?>
		
		<tfoot>
			<td class="cell-number bold"><?php echo $this->Label->get('general.total') ?></td>
			<td class="cell-number bold"><?php echo $data[$model]['total_fee'] ?></td>
		</tfoot>
		
		<?php endif ?>
	</table>
</div>
