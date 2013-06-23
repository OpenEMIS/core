<?php
$model = $type==='areas' ? 'SecurityGroupArea' : 'SecurityGroupInstitutionSite';
$fieldName = sprintf('%s.%s.', $model, $index);
?>

<div class="table_row">
	<div class="table_cell">
		<?php
		echo $this->Form->input($fieldName.'parent_id', array(
			'div' => false,
			'label' => false,
			'class' => 'full_width',
			'options' => $levelOptions,
			'onchange' => 'Security.groupsLoadValueOptions(this)',
			'url' => 'Security/groupsAddAccessOptions/' . $type
		));
		?>
	</div>
	<div class="table_cell">
		<?php
		$id = $type==='areas' ? 'area_id' : 'institution_site_id';
		echo $this->Form->input($fieldName.$id, array(
			'div' => false,
			'label' => false,
			'class' => 'full_width value_id',
			'options' => $valueOptions
		));
		?>
	</div>
	<div class="table_cell"><span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="jsTable.doRemove(this)"></span></div>
</div>