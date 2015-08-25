<?php if (($action == 'add' || $action == 'edit') && !isset($attr['non-editable'])) : ?>
<script type="text/javascript">
	$(function() {
		fees.load('totalFee');
		jsTable.computeTotalForMoney('totalFee');
		jsForm.compute(this);
	});
</script>
<div class="input clearfix">
	<label class="pull-left" for="<?= $attr['id'] ?>"><?= __('Fee Types') ?></label>
	<div class="table-in-view table-responsive">
		<table class="table">
			<thead>
				<tr>
					<th><?= __('Type') ?></th>
					<th><?= __('Amount ('.$attr['currency'].')') ?></th>
					<th></th>
				</tr>
			</thead>

			<?php if (isset($attr['data'])) : ?>

			<tbody id='table_totalFee'>
				<?php $totalFee = 0.00;?> 
				<?php foreach ($attr['data'] as $i=>$obj) : ?>
				<?php 
					$record = $obj;
					if (isset($attr['exists']) && !empty($attr['exists'])) {
						foreach ($attr['exists'] as $exist) {
							if ($exist['fee_type_id'] == $obj['fee_type_id']) {
								$record = $exist;
								$totalFee = $totalFee + $record['amount'];
								break;
							}
						}
					}
				?>
				<tr>
					<td><?= $record['type'] ?></td>
					<td>
						<input type="text" class="inputs_totalFee" name="<?php echo sprintf('InstitutionFees[institution_fee_types][%d][amount]', $i) ?>" value="<?= $record['amount'] ?>" onblur="jsTable.computeTotalForMoney('totalFee'); jsForm.compute(this); return fees.checkDecimal(this, 2); " 
						onkeypress="return utility.floatCheck(event); " onclick="fees.selectAll(this)" computeType="totalFee"/>
						<input type="hidden" name="<?php echo sprintf('InstitutionFees[institution_fee_types][%d][fee_type_id]', $i) ?>" value="<?= $record['fee_type_id'] ?>" />
						<input type="hidden" name="<?php echo sprintf('InstitutionFees[institution_fee_types][%d][id]', $i) ?>" value="<?= $record['id'] ?>" />
					</td>
				</tr>
				<?php endforeach ?>
			</tbody>
			
			<?php else : ?>

				<tr>&nbsp;</tr>
			
			<?php endif; ?>
			<tfoot>
				<tr>
					<td class="">Total</td>
					<td class="">
						<span><?= $attr['currency']?></span>
						<span class="totalFee"><?= $totalFee ?></span>
					<td/>
				</tr>
			</tfoot>

		</table>
	</div>
</div>

<?php else : ?>

	<?php if (isset($attr['non-editable']) && $action != 'view'):   ?>
	<div class="input clearfix">
		<label class="pull-left" for="<?= $attr['id'] ?>"><?= __('Fee Types') ?></label>
		<div class="table-in-view">
	<?php else : ?>
		<div class="table-in-view col-md-4 table-responsive" style="width:inherit">
	<?php endif; ?>
			<table class="table">
				<thead>
					<tr>
						<th><?= __('Type') ?></th>
						<th class="text-right"><?= __('Amount ('.$attr['currency'].')') ?></th>
					</tr>
				</thead>
				<tbody>
				<?php 
				if (isset($attr['data']) && !empty($attr['data'])) :
					foreach ($attr['data'] as $i=>$obj) : ?>
					<tr>
						<td><?= $obj['type'] ?></td>
						<td class="text-right"><?php echo $obj['amount'] ?></td>
					</tr>
				<?php
					endforeach;
				endif;
				?>
				</tbody>
				
				<?php if (isset($attr['data']) && !empty($attr['data'])) : ?>
				
				<tfoot>
					<td class="bold"><?php echo $this->Label->get('general.total') ?></td>
					<td class="text-right bold"><?php echo $attr['total'] ?></td>
				</tfoot>
				
				<?php
				endif;
				?>
			</table>
		</div>
	<?php if (isset($attr['non-editable']) && $action != 'view'): ?>
	</div>
	<?php endif ?>

<?php endif ?>
