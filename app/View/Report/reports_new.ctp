<?php
echo $this->Html->script('report/index', false);
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="report" class="content_wrapper index">
	<h1>
		<span><?php echo __('Custom Reports'); ?></span>
		<?php
		echo $this->Html->link(__('Back'), array('action' => 'index'), array('class' => 'divider'));
		?>
	</h1>
	
	<?php
	echo $this->Form->create('Report', array(
		'url' => array('controller' => 'Report', 'action' => 'index'), 
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')));
	/*
    echo $this->Form->create('Reports', array(
        'url' => array('controller' => 'Students', 'action' => 'commentsAdd'),
        'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
    ));
	*/
	
	echo $this->Form->input('new', array('type'=>'hidden', 'value'=>'1'));   
    ?>

    <div class="row">
		<div class="label"><?php echo __('Model'); ?></div>
		<div class="value">
			<?php echo $this->Form->input('model', array('options' => $models)); ?>
		</div>
    </div>
    
    <div class="controls view_controls">
        <input type="submit" value="<?php echo __('Next'); ?>" class="btn_save" />
    </div>
    <?php echo $this->Form->end(); ?>
</div>