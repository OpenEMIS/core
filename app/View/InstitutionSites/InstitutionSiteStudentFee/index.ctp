<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);

$this->start('contentBody');
	echo $this->element("../InstitutionSites/$model/controls");
?>
	
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo $this->Label->get('general.openemisId') ?></th>
					<th><?php echo $this->Label->get('general.name') ?></th>
					<th><?php echo sprintf('%s (%s)', $this->Label->get('StudentFee.fee'), $currency) ?></th>
					<th><?php echo sprintf('%s (%s)', $this->Label->get('StudentFee.paid'), $currency) ?></th>
					<th><?php echo sprintf('%s (%s)', $this->Label->get('StudentFee.outstanding'), $currency) ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($data as $obj) : ?>
				<tr>
					<td><?php echo $this->Html->link($obj['SecurityUser']['openemis_no'], array('action' => $model, 'viewPayments', $obj['Student']['id'], $obj['InstitutionSiteFee']['id'])) ?></td>
					<td><?php echo $this->Model->getName($obj['SecurityUser']) ?></td>
					<td class="cell-number"><?php echo $obj['InstitutionSiteFee']['total'] ?></td>
					<td class="cell-number"><?php echo $obj[0]['paid'] ?></td>
					<td class="cell-number"><?php echo number_format($obj['InstitutionSiteFee']['total'] - $obj[0]['paid'], 2) ?></td>
				</tr>
				<?php endforeach ?>
			</tbody>
		</table>
	</div>

<?php $this->end(); ?>  
