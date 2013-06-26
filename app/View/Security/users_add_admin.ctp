<?php
$model = sprintf('SecurityGroupUser.%s.security_user_id', $index);
?>

<div class="table_row">
	<?php echo $this->Form->hidden($model, array('id' => 'UserId', 'value' => 0)) ; ?>
	<div class="table_cell">
		<div class="input_wrapper">
			<?php 
				echo $this->Form->input('search', array(
					'label' => false,
					'div' => false,
					'onblur' => 'Security.usersSearch(this)',
					'url' => 'Security/usersSearch'
				));
			?>
		</div>
	</div>
	<div class="table_cell name"></div>
	<div class="table_cell"><span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="jsTable.doRemove(this)"></span></div>
</div>