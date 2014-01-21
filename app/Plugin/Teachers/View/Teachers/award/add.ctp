<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Staff/js/awards', false);
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="award" class="content_wrapper edit add" selectAwardUrl="Teachers/ajax_find_award/" >
	<h1>
		<span><?php echo __($subheader); ?></span>
		<?php
		
            echo $this->Html->link(__('Back'), array('action' => 'award'), array('class' => 'divider'));
        
		?>
	</h1>
	</h1>
	
	<?php
	echo $this->Form->create($modelName, array(
		'url' => array('controller' => 'Teachers', 'action' => 'awardAdd', 'plugin'=>'Teachers'),
		'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>

	<?php if(!empty($this->data[$modelName]['id'])){ echo $this->Form->input('id', array('type'=> 'hidden')); } ?>
	<div class="row">
        <div class="label"><?php echo __('Issue Date'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('issue_date', array('type' => 'date', 'dateFormat' => 'DMY', 'before' => '<div class="left">', 'after' => '</div>','class'=>false)); 
		?>
        </div>
    </div>
     <div class="row">
        <div class="label"><?php echo __('Name'); ?></div>
        <div class="value">
 			<?php echo $this->Form->input('award', array('id' => 'searchAward', 'class'=>'default award', 'placeholder' => __('Award Name')));?>
        </div>
    </div>
     <div class="row">
        <div class="label"><?php echo __('Issuer'); ?></div>
        <div class="value">
        	<?php echo $this->Form->input('issuer', array('id' => 'searchIssuer', 'class'=>'default issuer', 'placeholder' => __('Issuer')));?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Comment'); ?></div>
        <div class="value"><?php echo $this->Form->input('comment', array('type'=> 'textarea'));?></div>
    </div>
	
	<div class="controls view_controls">
		<input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'award'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>