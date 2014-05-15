<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>
<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get('Database.backup'));
$this->start('contentActions');
$this->end();

$this->assign('contentId', 'indicators');
$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>

<?php
echo $this->Form->create('Database', array(
	'id' => 'submitForm',
	'inputDefaults' => array('label' => false, 'div' => false),	
	'url' => array('plugin' => 'Database', 'controller' => 'Database', 'action' => 'backup'),
	'onsubmit' => 'return jsForm.isSubmitDisabled(this)'
));
?>

<p><?php echo __('Click the Generate button to create a restore point.'); ?></p> 


<p><?php echo __('All data will be backed up except for following tables:'); ?></p>
<ul>
	<li><?php echo __('Security Users, Roles, and Functions'); ?></li>
	<li><?php echo __('System Configuration Values'); ?></li>
	<li><?php echo __('Security'); ?></li>
</ul>

<p><?php echo __('Please note that it may take sometime for the backup process to finish.'); ?></p>

<div class="controls">
	<input type="submit" value="<?php echo __('Generate'); ?>" class="btn_save <?php echo ($isBackupRunning)?"btn_disabled":"";?>" />
</div>

<?php echo $this->Form->end(); ?> 
<?php $this->end(); ?>  