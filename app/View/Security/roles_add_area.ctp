<?php
$model = $type==='areas' ? 'SecurityRoleArea' : 'SecurityRoleInstitutionSite';
$fieldName = sprintf('data[%s][%s][%%s]', $model, $order);
?>

<div class="table_row">
	<?php
	echo $this->Form->hidden('order', array('id' => 'order', 'value' => $order));
	echo $this->Form->hidden('security_role_id', array('name' => sprintf($fieldName, 'security_role_id'), 'value' => $roleId));
	?>
	<div class="table_cell">
		<?php
		echo $this->Form->input($type==='areas' ? 'area_level' : 'institution', array(
			'class' => 'full_width',
			'options' => $levelOptions,
			'default' => key($levelOptions),
			'onchange' => sprintf('security.loadOptionList(this, "%s")', $type),
			'div' => false,
			'label' => false
		));
		?>
	</div>
	<div class="table_cell">
		<?php
		$id = $type==='areas' ? 'area_id' : 'institution_site_id';
		echo $this->Form->input($id, array(
			'name' => sprintf($fieldName, $id),
			'class' => 'full_width ' . $id,
			'options' => $nameOptions,
			'default' => key($nameOptions),
			'div' => false,
			'label' => false
		));
		?>
	</div>
	<div class="table_cell"><span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="jsTable.doRemove(this)"></span></div>
</div>