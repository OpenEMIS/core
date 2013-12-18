<?php echo $this->element('breadcrumb'); ?>
<?php echo $this->Html->script('app.date', false); ?>

<div id="contact" class="content_wrapper edit add">
     <h1>
        <span><?php echo __('Contacts'); ?></span>
        <?php 
        if ($_edit) {
            echo $this->Html->link(__('Back'), array('action' => 'contactsView', $id), array('class' => 'divider'));
        }
        ?>
    </h1>
	<?php
	echo $this->Form->create('StudentContact', array(
		'url' => array('controller' => 'Students', 'action' => 'contactsEdit', $id, $selectedContactOptions),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>
    <?php $obj = @$this->request->data['StudentContact']; ?>
	<?php echo $this->Form->input('StudentContact.id');?>

    <div class="row">
        <div class="label"><?php echo __('Type'); ?></div>
        <div class="value">
            <?php
                echo $this->Form->input('contact_option_id', array(
                    'options' => $contactOptions,
                    'default' => $selectedContactOptions,
                    'url' => sprintf('/%s/%s/%s', $this->params['controller'], $this->params['action'], $id),
                    'onchange' => 'jsForm.change(this)'
                ));
            ?>
        </div>
    </div>
    
    <div class="row select_row">
        <div class="label"><?php echo __('Description'); ?></div>
        <div class="value">
            <?php
                echo $this->Form->input('contact_type_id', array(
                    'options' => $contactTypeOptions,
                ));
            ?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Value'); ?></div>
        <div class="value">
            <?php echo $this->Form->input('value'); ?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Preferred'); ?></div>
        <div class="value"><?php echo $this->Form->input('preferred', array('options'=>array('1'=>'Yes', '0'=>'No'))); ?></div>
    </div>

	<div class="controls view_controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'contactsView', $id), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>
