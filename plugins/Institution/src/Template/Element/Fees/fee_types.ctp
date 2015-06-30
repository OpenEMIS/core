<?php if ($action == 'add' || $action == 'edit') : ?>

<div class="input clearfix">
	<label class="pull-left" for="<?= $attr['id'] ?>"><?= $this->Label->get($attr['model'] .'.'. $attr['field']) ?></label>
	<div class="table-in-view col-md-4 table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?= __('Type') ?></th>
					<th><?= __('Amount ('.$attr['currency'].')') ?></th>
				</tr>
			</thead>

			<?php if (isset($attr['data'])) : ?>

			<tbody>
				<?php foreach ($attr['data'] as $i=>$obj) : ?>
				<?php 
					$record = $obj;
					if (isset($attr['exists']) && !empty($attr['exists'])) {
						foreach ($attr['exists'] as $exist) {
							if ($exist['fee_type_id'] == $obj['fee_type_id']) {
								$record = $exist;
								break;
							}
						}
					}
				?>
				<tr>
					<td><?= $record['type'] ?></td>
					<td>
						<input type="text" name="<?php echo sprintf('InstitutionSiteFees[institution_site_fee_types][%d][amount]', $i) ?>" value="<?= $record['amount'] ?>" onblur="return utility.checkDecimal(this, 2)" onkeyup="return utility.checkDecimal(this, 2)" onkeypress="return utility.floatCheck(event)" />
						<input type="hidden" name="<?php echo sprintf('InstitutionSiteFees[institution_site_fee_types][%d][fee_type_id]', $i) ?>" value="<?= $record['fee_type_id'] ?>" />
						<input type="hidden" name="<?php echo sprintf('InstitutionSiteFees[institution_site_fee_types][%d][id]', $i) ?>" value="<?= $record['id'] ?>" />
					</td>
				</tr>
				<?php endforeach ?>
			</tbody>
			
			<?php else : ?>

				<tr>&nbsp;</tr>
			
			<?php endif; ?>

		</table>
	</div>
</div>

<?php else : ?>

<div class="table-in-view col-md-4 table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?= __('Type') ?></th>
				<th><?= __('Amount ('.$attr['currency'].')') ?></th>
			</tr>
		</thead>
		<tbody>
		<?php 
		if (isset($attr['data']) && !empty($attr['data'])) :
			foreach ($attr['data'] as $i=>$obj) : ?>
			<tr>
				<td><?= $obj['type'] ?></td>
				<td class="cell-number"><?php echo $obj['amount'] ?></td>
			</tr>
		<?php
			endforeach;
		endif;
		?>
		</tbody>
		
		<?php if (isset($attr['data']) && !empty($attr['data'])) : ?>
		
		<tfoot>
			<td class="cell-number bold"><?php echo $this->Label->get('general.total') ?></td>
			<td class="cell-number bold"><?php echo $attr['total'] ?></td>
		</tfoot>
		
		<?php
		endif;
		?>
	</table>
</div>

<?php endif ?>
