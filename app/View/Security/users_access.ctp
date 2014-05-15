<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->css('webkit_scrollbar', 'stylesheet', array('inline' => false));

echo $this->Html->script('security', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="users" class="content_wrapper edit details">
	<h1>
		<span><?php echo __('User Access'); ?></span>
		<?php echo $this->Html->link(__('Back'), array('action' => 'usersEdit', $data['id']), array('class' => 'divider')); ?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<?php
	echo $this->Form->create('SecurityUserAccess', array(
		'url' => array('controller' => 'Security', 'action' => 'usersAccess'),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default')
	));
	echo $this->Form->hidden('security_user_id', array('value' => $data['id']));
	echo $this->Form->hidden('table_id', array('id' => 'TableId', 'value' => 0));
	?>
	
	<fieldset class="section_group" id="search">
		<legend><?php echo __('Search'); ?></legend>
	
		<div class="row">
			<?php
			echo $this->Form->input('table_name', array(
				'id' => 'module',
				'class' => 'module',
				'options' => $moduleOptions,
				'onchange' => '$("#search .table_body").empty()'
			));
			?>
		</div>
		
		<div class="row">
			<div class="search_wrapper">
				<?php 
					echo $this->Form->input('SearchField', array(
						'id' => 'SearchField',
						'label' => false,
						'div' => false,
						'class' => 'default',
						'placeholder' => __('Identification No, First Name or Last Name')
					));
				?>
				<span class="icon_clear" onClick="$('#SearchField').val('')">X</span>
			</div>
			<span class="left icon_search" url="Security/usersSearch/2" onClick="Security.usersSearch(this)"></span>
		</div>
		
		<div class="table_scrollable">
			<div class="table table_header">
				<div class="table_head">
					<div class="table_cell cell_id_no"><?php echo __('Identification No'); ?></div>
					<div class="table_cell"><?php echo __('First Name'); ?></div>
					<div class="table_cell"><?php echo __('Last Name'); ?></div>
					<div class="table_cell cell_icon_action"></div>
				</div>
			</div>
			<div class="list_wrapper hidden" limit="4" style="height: 109px;">
				<div class="table" url="Security/usersAddAccess/<?php echo $data['id']; ?>">
					<div class="table_body"></div>
				</div>
			</div>
		</div>
	</fieldset>
	
	<div class="table full_width">
		<div class="table_head">
			<div class="table_cell" style="width: 200px;"><?php echo __('OpenEMIS ID'); ?></div>
			<div class="table_cell"><?php echo __('Name'); ?></div>
			<div class="table_cell cell_module"><?php echo __('Module'); ?></div>
			<div class="table_cell cell_icon_action"></div>
		</div>
		
		<div class="table_body">
			<?php 
				foreach($data['access'] as $row) {
					$obj = $row['SecurityUserAccess'];
					$userId = $obj['security_user_id'];
					$id = $obj['table_id'];
					$name = $obj['table_name'];
					$params = sprintf('%s/%s/%s/', $userId, $id, $name);
			?>
				<div class="table_row">
					<div class="table_cell"><?php echo $obj['identification_no']; ?></div>
					<div class="table_cell"><?php echo $obj['name']; ?></div>
					<div class="table_cell"><?php echo $obj['table_name']; ?></div>
					<div class="table_cell">
						<span class="icon_delete" url="Security/usersDeleteAccess/<?php echo $params; ?>" onclick="Security.removeAccessUser(this)"></span>
					</div>
				</div>
			<?php } ?>
		</div>
	</div>
	<?php echo $this->Form->end(); ?>
</div>