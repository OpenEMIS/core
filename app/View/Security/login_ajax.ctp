
<?php
echo $this->Form->create('SecurityUser', array(
	'id' => 'ajax_login',
	'url' => array('controller' => 'Security', 'action' => 'login'),
	'inputDefaults' => array('label' => false, 'div' => false)
));
?>

<div class="field_row"><?php echo $message; ?></div>

<div class="field_row field_input">
	<div class="field_name"><?php echo __('Username'); ?></div>
	<div class="field_value"><?php echo $this->Form->input('username'); ?></div>
</div>
<div class="field_row field_input field_last">
	<div class="field_name"><?php echo __('Password'); ?></div>
	<div class="field_value"><?php echo $this->Form->input('password'); ?></div>
</div>

<?php echo $this->Form->end(); ?>
