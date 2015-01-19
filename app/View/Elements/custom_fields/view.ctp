<div class="row">
	<div class="col-md-3"><?php echo __('Name'); ?></div>
	<div class="col-md-6"><?php echo $data[$Custom_Parent]['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Field Name'); ?></div>
	<div class="col-md-6"><?php echo $data[$Custom_Field]['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Field Type'); ?></div>
	<div class="col-md-6"><?php echo $fieldTypeOptions[$data[$Custom_Field]['type']]; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Mandatory'); ?></div>
	<div class="col-md-6"><?php echo $mandatoryOptions[$data[$Custom_Field]['is_mandatory']]; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Unique'); ?></div>
	<div class="col-md-6"><?php echo $uniqueOptions[$data[$Custom_Field]['is_unique']]; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Visible'); ?></div>
	<div class="col-md-6"><?php echo $visibleOptions[$data[$Custom_Field]['visible']]; ?></div>
</div>
<?php if ($data[$Custom_Field]['type'] == 3 || $data[$Custom_Field]['type'] == 4) : ?>
	<?php if (sizeof($data[$Custom_FieldOption]) > 0) : ?>
		<div class="row">
			<div class="col-md-3"><?php echo __('Options'); ?></div>
			<div class="col-md-6">
				<table class="table table-striped table-hover table-bordered table-checkable table-input">
					<thead>
						<tr>
							<th class="checkbox-column"></th>
							<th><?php echo __('Value'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
							foreach ($data[$Custom_FieldOption] as $i => $obj) :
						?>
							<tr>
								<td class="checkbox-column center"><?php echo $this->Utility->checkOrCrossMarker($obj['visible']) ?></td>
								<td><?php echo $obj['value'];?></td>
							</tr>
						<?php
							endforeach;
						?>
					</tbody>
				</table>
			</div>
		</div>
	<?php endif ?>
<?php endif ?>
<?php if ($data[$Custom_Field]['type'] == 7) : ?>
	<?php if (sizeof($data[$Custom_TableColumn]) > 0 || sizeof($data[$Custom_TableRow]) > 0) : ?>
		<div class="row">
			<fieldset class="section_group">
				<legend><?php echo $data[$Custom_Field]['name']; ?></legend>
				<table class="table table-striped table-hover table-bordered">
					<thead>
						<tr>
							<th></th>
							<?php
							if(isset($data[$Custom_TableColumn])) :
								foreach ($data[$Custom_TableColumn] as $i => $obj) {
									if($obj['visible'] == 1) :
							?>
									<th><?php echo $obj['name']; ?></th>
							<?php
									endif;
								}
							endif;
							?>
						</tr>
					</thead>
					<tbody>
						<?php
						if(isset($data[$Custom_TableRow])) :
							foreach ($data[$Custom_TableRow] as $i => $obj) {
								if($obj['visible'] == 1) :
						?>
								<tr>
									<td><?php echo $obj['name']; ?></td>
									<?php
									if(isset($data[$Custom_TableColumn])) :
										foreach ($data[$Custom_TableColumn] as $j => $obj) {
											if($obj['visible'] == 1) :
									?>
											<td></td>
									<?php
											endif;
										}
									endif;
									?>
								</tr>
						<?php
								endif;
							}
						endif;
						?>
					</tbody>
				</table>
			</fieldset>
		</div>
	<?php endif ?>
<?php endif ?>
<div class="row">
	<div class="col-md-3"><?php echo __('Modified by'); ?></div>
	<div class="col-md-6"><?php echo $data['ModifiedUser']['first_name'] . " " . $data['ModifiedUser']['last_name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Modified on'); ?></div>
	<div class="col-md-6"><?php echo $data[$Custom_Field]['modified']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Created by'); ?></div>
	<div class="col-md-6"><?php echo $data['CreatedUser']['first_name'] . " " . $data['ModifiedUser']['last_name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Created on'); ?></div>
	<div class="col-md-6"><?php echo $data[$Custom_Field]['created']; ?></div>
</div>