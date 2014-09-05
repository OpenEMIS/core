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
						<th><?php echo $this->Label->get('general.amount') ?></th>
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
							echo $this->Form->hidden("InstitutionSiteFeeType.$i.fee_type_id", array('value' => $obj['fee_type_id']));
							echo $this->Form->hidden("InstitutionSiteFeeType.$i.name", array('value' => $obj['name']));
							echo $obj['name'];
							?>
						</td>
						<td>
							<?php echo $this->Form->input("InstitutionSiteFeeType.$i.amount", array('value' => $obj['amount'], 'label' => false, 'div' => false, 'between' => false, 'after' => false)) ?>
						</td>
					</tr>
				<?php
					endforeach;
				endif;
				?>
				</tbody>
			</table>
			<!--a class="void icon_plus" onclick="$('#reload').val('InstitutionSiteFeeType').click()"><?php echo $this->Label->get('general.add') ?></a-->
		</div>
	</div>
</div>

<?php else : ?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.name') ?></th>
			</tr>
		</thead>
		<tbody>
		<?php 
		if (!empty($data['CensusGridYCategory'])) :
			foreach ($data['CensusGridYCategory'] as $i => $obj) : ?>
			<tr>
				<td><?php echo $obj['name'] ?></td>
			</tr>
		<?php
			endforeach;
		endif;
		?>
		</tbody>
	</table>
</div>

<?php endif ?>
