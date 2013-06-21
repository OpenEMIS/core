<?php
$model = sprintf('SecurityGroupUser.%s.id', $index);
?>

<div class="table_row">
	<div class="table_cell">
		<?php echo $this->Form->hidden($model, array('value' => 0)) ; ?>
		<div class="search_wrapper">
			<?php 
				echo $this->Form->input('SearchField', array(
					'label' => false,
					'div' => false,
					'class' => 'search_field',
					'style' => 'width: 220px'
				));
			?>
		</div>
		<span class="left icon_search" url="Security/usersSearch/" onclick="Security.usersSearch(this)"></span>
	</div>
	<div class="table_cell name"></div>
	<div class="table_cell"><span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="jsTable.doRemove(this)"></span></div>
</div>