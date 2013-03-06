<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="scheduler" class="content_wrapper">
	<?php
	echo $this->Form->create('DataProcessing', array(
		'id' => 'submitForm',
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'DataProcessing', 'action' => 'scheduler')
	));
	?>
	<h1><span>Scheduler</span></h1>
	
	<?php echo $this->Form->end(); ?>
</div>