<?php
	echo $this->element('/custom_fields/controls');	
?>

<table class="table table-striped table-hover table-bordered">
	<thead>
		<tr>
			<th class="cell-visible"><?php echo __('Visible'); ?></th>
			<th><?php echo __('Name'); ?></th>
			<th><?php echo __('Field Type'); ?></th>
			<th class="cell-mandatory"><?php echo __('Mandatory'); ?></th>
			<th class="cell-unique"><?php echo __('Unique'); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php if(isset($data)) : ?>
			<?php foreach ($data as $obj) : ?>
				<tr>
					<td class="center"><?php echo $this->Utility->checkOrCrossMarker($obj[$Custom_Field]['visible']==1); ?></td>
					<td><?php echo $this->Html->link($obj[$Custom_Field]['name'], array('action' => 'view', $obj[$Custom_Field]['id'], 'module' => $selectedModule, 'group' => $selectedGroup)) ?></td>
					<td><?php echo $fieldTypeOptions[$obj[$Custom_Field]['type']] ?></td>
					<td class="center">
						<?php
							$arrMandatory = array(2,5,6); //Text, Textarea, number
							if(in_array($obj[$Custom_Field]['type'], $arrMandatory)) {
								echo $this->Utility->checkOrCrossMarker($obj[$Custom_Field]['is_mandatory']==1);
							} else {
								echo "-";
							}
						?>
					</td>
					<td class="center">
						<?php
							$arrUnique = array(2,6); //Text, number
							if(in_array($obj[$Custom_Field]['type'], $arrUnique)) {
								echo $this->Utility->checkOrCrossMarker($obj[$Custom_Field]['is_unique']==1);
							} else {
								echo "-";
							}								
						?>
					</td>
				</tr>
			<?php endforeach ?>
		<?php endif ?>
	</tbody>
</table>