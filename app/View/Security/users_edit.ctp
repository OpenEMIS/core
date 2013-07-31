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
		<span><?php echo __('User Details'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'usersView', $data['id']), array('class' => 'divider')); ?>
	</h1>
	
	<?php
	echo $this->Form->create('SecurityUser', array(
		'url' => array('controller' => 'Security', 'action' => 'usersEdit', $data['id']),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default')
	));
	echo $this->Form->hidden('id', array('value' => $data['id']));
	?>
		
	<fieldset class="section_break">
		<legend><?php echo __('Login'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Username'); ?></div>
			<div class="value"><?php echo $data['username']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('New Password'); ?></div>
			<div class="value"><?php echo $this->Form->input('new_password', array('type' => 'password', 'autocomplete' => 'off')); ?></div>
			<?php echo $this->Form->input('password', array('class' => 'none')); ?>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Retype Password'); ?></div>
			<div class="value"><?php echo $this->Form->input('retype_password', array('type' => 'password')); ?></div>
		</div>
		<?php if($data['super_admin'] == 0) { ?>
		<div class="row">
			<div class="label"><?php echo __('Status'); ?></div>
			<div class="value"><?php echo $this->Form->input('status', array('options' => $statusOptions, 'value' => $data['status'])); ?></div>
		</div>
		<?php } ?>
		<div class="row">
			<div class="label"><?php echo __('Last Login'); ?></div>
			<div class="value">
			<?php 
				if(!is_null($data['last_login'])) {
					echo $this->Utility->formatDate($data['last_login']) . ' ' . date('H:i:s', strtotime($data['last_login']));
				} else {
					echo '<i>' . __('Not login yet') . '</i>';
				}
			?>
			</div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('General'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Identification No'); ?></div>
			<div class="value"><?php echo $this->Form->input('identification_no', array('value' => $data['identification_no'])); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('First Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('first_name', array('value' => $data['first_name'])); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Last Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('last_name', array('value' => $data['last_name'])); ?></div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Contact'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Telephone'); ?></div>
			<div class="value"><?php echo $this->Form->input('telephone', array('value' => $data['telephone'])); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Email'); ?></div>
			<div class="value"><?php echo $this->Form->input('email', array('value' => $data['email'])); ?></div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Groups'); ?></legend>
		<div class="table full_width">
			<div class="table_head">
				<div class="table_cell" style="width: 200px;"><?php echo __('Group'); ?></div>
				<div class="table_cell"><?php echo __('Role'); ?></div>
			</div>
			
			<div class="table_body">
				<?php foreach($data['groups'] as $group) { ?>
					<div class="table_row">
						<div class="table_cell"><?php echo $group['security_group_name']; ?></div>
						<div class="table_cell"><?php echo $group['security_role_name']; ?></div>
					</div>
				<?php } ?>
			</div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Access'); ?></legend>
		<?php echo $this->element('alert'); ?>
		<fieldset class="section_group" id="search">
			<legend><?php echo __('Search'); ?></legend>
		
			<div class="row">
				<?php
				echo $this->Form->input('table_name', array(
					'id' => 'module',
					'style' => 'width: 269px',
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
				<div class="table_cell" style="width: 200px;"><?php echo __('Identification No'); ?></div>
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
	</fieldset>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'usersView'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div>