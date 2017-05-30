<?php if (($action == 'add' || $action == 'edit') && !isset($attr['non-editable'])) : ?>
<script type="text/javascript">
	$(function() {
		fees.load('totalFee');
		jsTable.computeTotalForMoney('totalFee');
		jsForm.compute(this);
	});
</script>
<div class="input clearfix">
	<label for="<?= $attr['id'] ?>"><?= __('Fee Types') ?></label>
	<div class="table-wrapper">
		<div class="table-in-view">
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
						<td class="<?= (!empty($record['error']))?"error":"";?>">
							<?php
								$amountClass = (!empty($record['error'])) ? "inputs_totalFee form-error": "inputs_totalFee";
								echo $this->Form->input(sprintf('InstitutionFees.institution_fee_types.%d.amount', $i), [
										'type' => 'text',
										'label' => false,
										'value' => $record['amount'],
										'class' => $amountClass,
										'onblur' => "jsTable.computeTotalForMoney('totalFee'); jsForm.compute(this); return fees.checkDecimal(this, 2); ",
										'onkeypress' => "return utility.floatCheck(event); ",
										'onclick' => "fees.selectAll(this)",
										'computeType' => "totalFee"
									]);
								echo $this->Form->input(sprintf('InstitutionFees.institution_fee_types.%d.fee_type_id', $i), [
										'type' => 'hidden',
										'value' => $record['fee_type_id']
									]);
								echo $this->Form->input(sprintf('InstitutionFees.institution_fee_types.%d.id', $i), [
										'type' => 'hidden',
										'value' => $record['id']
									]);
							?>
						</td>
						<td>
							<?php if (!empty($record['error'])):?>
							<span class="<?= (!empty($record['error']))?"error-message":"";?>">
								<?= implode('<br/>', $record['error']);?>
							</span>
							<?php endif;?>
						</td>
					</tr>
					<?php endforeach ?>
				</tbody>
				
				<?php else : ?>

					<tr>&nbsp;</tr>
				
				<?php endif; ?>
				<tfoot>
					<tr>
						<td class=""><?= __('Total') ?></td>
						<td class="">
							<span><?= $attr['currency']?></span>
							<span class="totalFee"><?= $totalFee ?></span>
						<td/>
					</tr>
				</tfoot>

			</table>
		</div>
	</div>
</div>

<?php else : ?>

	<?php if (isset($attr['non-editable']) && $action != 'view'):   ?>
	<?php //if (!isset($attr['non-editable'])):   ?>
	<div class="input clearfix">
			<label for="<?= $attr['id'] ?>"><?= __('Fee Types') ?></label>
			<div class="table-wrapper">
				<div class="table-in-view">
		<?php else : ?>
				<div class="table-in-view">
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
	</div>
	<?php endif ?>

<?php endif ?>
