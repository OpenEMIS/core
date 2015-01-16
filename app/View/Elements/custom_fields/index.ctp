<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	if(isset($selectedParent)) {
		if ($_add) {
		    echo $this->Html->link(__('Add'), array('action' => 'add', 'module' => $selectedModule, 'parent' => $selectedParent), array('class' => 'divider'));
		}
		if ($_edit) {
		    echo $this->Html->link(__('Reorder'), array('action' => 'reorder', $selectedParent, 'module' => $selectedModule, 'parent' => $selectedParent), array('class' => 'divider'));
		    echo $this->Html->link(__('Preview'), array('action' => 'preview', $selectedParent, 'module' => $selectedModule, 'parent' => $selectedParent), array('class' => 'divider'));
		}
	}
$this->end();

$this->start('contentBody');
	echo $this->element('/custom_fields/controls');
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th class="cell_visible"><?php echo __('Visible'); ?></th>
				<th><?php echo __('Name'); ?></th>
				<th><?php echo __('Field Type'); ?></th>
				<th><?php echo __('Mandatory'); ?></th>
				<th><?php echo __('Unique'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if(isset($data)) : ?>
				<?php foreach ($data as $obj) : ?>
					<tr>
						<td class="cell_visible"><?php echo $this->Utility->checkOrCrossMarker($obj[$Custom_Field]['visible']==1); ?></td>
						<td><?php echo $this->Html->link($obj[$Custom_Field]['name'], array('action' => 'view', $obj[$Custom_Field]['id'], 'module' => $selectedModule, 'parent' => $selectedParent)) ?></td>
						<td><?php echo $fieldTypeOptions[$obj[$Custom_Field]['type']] ?></td>
						<td class="cell_visible">
							<?php
								$arrMandatory = array(2,5,6); //Text, Textarea, number
								if(in_array($obj[$Custom_Field]['type'], $arrMandatory)) {
									echo $this->Utility->checkOrCrossMarker($obj[$Custom_Field]['is_mandatory']==1);
								} else {
									echo "-";
								}
							?>
						</td>
						<td class="cell_visible">
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
</div>

<?php $this->end(); ?>