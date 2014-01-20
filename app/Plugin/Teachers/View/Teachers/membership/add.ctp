<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Staff/js/memberships', false);
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="membership" class="content_wrapper edit add" url="Teachers/ajax_find_membership/" >
	<h1>
		<span><?php echo __($subheader); ?></span>
		<?php
		if ($_edit) {
            echo $this->Html->link(__('Back'), array('action' => 'membership'), array('class' => 'divider'));
        }
		?>
	</h1>
	</h1>
	
	<?php
	echo $this->Form->create($modelName, array(
		'url' => array('controller' => 'Teachers', 'action' => 'membership_add', 'plugin'=>'Teachers'),
		'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>

	<?php if(!empty($this->data[$modelName]['id'])){ echo $this->Form->input('id', array('type'=> 'hidden')); } ?>
	<div class="row">
        <div class="label"><?php echo __('Issue Date'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('issue_date', array('type' => 'date', 'dateFormat' => 'DMY', 'before' => '<div class="left">', 'after' => '</div>','class'=>false)) 
		?>
        </div>
    </div>
     <div class="row">
        <div class="label"><?php echo __('Name'); ?></div>
        <div class="value">
 			<?php echo $this->Form->input('membership', array('id' => 'searchMembership', 'class'=>'default membership', 'placeholder' => __('Membership Name')));?>
        </div>
    </div>
     <div class="row">
        <div class="label"><?php echo __('Expiry Date'); ?></div>
        <div class="value">
    	<?php 
			echo $this->Form->input('expiry_date', array('type' => 'date', 'empty'=>true, 'dateFormat' => 'DMY', 'before' => '<div class="left">', 'after' => '</div>','class'=>false)) 
		?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Comment'); ?></div>
        <div class="value"><?php echo $this->Form->input('comment', array('type'=> 'textarea'));?></div>
    </div>
	
	<div class="controls view_controls">
		<input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'membership'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>