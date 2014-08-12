<?php /*
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="users" class="content_wrapper edit details">
	<h1>
		<span><?php echo __('My Details'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'details'), array('class' => 'divider')); ?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<?php
	echo $this->Form->create('SecurityUser', array(
		'url' => array('controller' => 'Home', 'action' => 'detailsEdit'),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default')
	));
	echo $this->Form->hidden('id', array('value' => $data['id']));
	?>
	
	<fieldset class="section_break">
		<legend><?php echo __('General'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Username'); ?></div>
			<div class="value"><?php echo $data['username']; ?></div>
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
	
	<div class="controls view_controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'details'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div> 
 * 
 */?>


<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentId', 'users');
$this->assign('contentHeader', $header);
$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => 'details'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'detailsEdit'));
echo $this->Form->create('SecurityUser', $formOptions);
echo $this->Form->hidden('id', array('value' => $data['id']));
?>
<fieldset class="section_break">
	<legend><?php echo __('General'); ?></legend>
	<?php
	echo $this->Form->input('username', array('value' => $data['username'], 'disabled' => 'disabled'));
	echo $this->Form->input('first_name', array('value' => $data['first_name']));
	echo $this->Form->input('last_name', array('value' => $data['last_name']));
	?>
</fieldset>
<fieldset class="section_break">
	<legend><?php echo __('Contact'); ?></legend>
	<?php
	echo $this->Form->input('telephone', array('value' => $data['telephone']));
	echo $this->Form->input('email', array('value' => $data['email']));
	?>
</fieldset>
<fieldset class="section_break">
	<legend><?php echo __('Groups'); ?></legend>
	<?php
	$tableHeaders = array(__('Group'), __('Role'));
	$tableData = array();
	foreach ($data['groups'] as $group) {
		$row = array();
		$row[] = $group['security_group_name'];
		$row[] = $group['security_role_name'];
		$tableData[] = $row;
	}
	echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
	?>
</fieldset>
<?php
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'details')));
echo $this->Form->end();
$this->end();
?>