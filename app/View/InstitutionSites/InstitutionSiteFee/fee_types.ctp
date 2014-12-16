<?php
$defaults = $this->FormUtility->getFormDefaults();
if ($action == 'add' || $action == 'edit') : 

?>

<div class="form-group">
	<label class="<?php echo $defaults['label']['class'] ?>"><?php echo $this->Label->get("$model.InstitutionSiteFeeType") ?></label>
	<div class="col-md-6">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th><?php echo $this->Label->get('general.type') ?></th>
						<th><?php echo sprintf('%s (%s)', $this->Label->get('general.amount'), $currency) ?></th>
					</tr>
				</thead>
				<tbody>
				<?php 
				if (($action == 'add' || $action == 'edit') && isset($this->data['InstitutionSiteFeeType'])) :
					foreach ($this->data['InstitutionSiteFeeType'] as $i => $obj) :
				?>
					<tr>
						<td>
							<?php
							if ($this->action == 'edit') {
								echo $this->Form->hidden("InstitutionSiteFeeType.$i.institution_site_fee_id", array('value' => $this->data[$model]['id']));
							}
							echo $this->Form->hidden("InstitutionSiteFeeType.$i.id", array('value' => $obj['id']));
							echo $this->Form->hidden("InstitutionSiteFeeType.$i.fee_type_id", array('value' => $obj['fee_type_id']));
							echo $feeTypes[$obj['fee_type_id']];
							?>
						</td>
						<td>
							<?php echo $this->Form->input("InstitutionSiteFeeType.$i.amount", array('type' => 'text', 'value' => $obj['amount'], 'onblur' => 'return utility.checkDecimal(this, 2)', 'onkeyup' => 'return utility.checkDecimal(this, 2)', 'onkeypress' => 'return utility.floatCheck(event)', 'label' => false, 'div' => false, 'between' => false, 'after' => false)) ?>
						</td>
					</tr>
				<?php
					endforeach;
				endif;
				?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<?php else : ?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.type') ?></th>
				<th><?php echo sprintf('%s (%s)', $this->Label->get('general.amount'), $currency) ?></th>
			</tr>
		</thead>
		<tbody>
		<?php 
		if (!empty($data['InstitutionSiteFeeType'])) :
			foreach ($data['InstitutionSiteFeeType'] as $i => $obj) : ?>
			<tr>
				<td><?php echo $feeTypes[$obj['fee_type_id']] ?></td>
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
			<td class="cell-number bold"><?php echo $data[$model]['total'] ?></td>
		</tfoot>
		
		<?php
		endif;
		?>
	</table>
</div>

<?php endif ?>
