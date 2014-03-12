<?php echo $this->element('breadcrumb'); ?>
<?php echo $this->Html->script('app.date', false); ?>

<div id="identity" class="content_wrapper edit add">
     <h1>
        <span><?php echo __('Identities'); ?></span>
        <?php 
        if ($_edit && !$WizardMode) {
            echo $this->Html->link(__('Back'), array('action' => 'identitiesView', $id), array('class' => 'divider'));
        }
        ?>
    </h1>
	<?php
	echo $this->Form->create('StudentIdentity', array(
		'url' => array('controller' => 'Students', 'action' => 'identitiesEdit'),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>
    <?php $obj = @$this->request->data['StudentIdentity']; ?>
	<?php echo $this->Form->input('StudentIdentity.id');?>
	 <div class="row">
        <div class="label"><?php echo __('Type'); ?></div>
        <div class="value"><?php echo $this->Form->input('identity_type_id', array('empty'=>__('--Select--'),'options'=>$identityTypeOptions, 'default'=>$obj['identity_type_id'])); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Number'); ?></div>
        <div class="value"><?php echo $this->Form->input('number'); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Issue Date'); ?></div>
         <div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'issue_date', array('desc' => true,'value' => $obj['issue_date'])); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Expiry Date'); ?></div>
         <div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'expiry_date', array('desc' => true,'yearAdjust'=>5,'value' => $obj['expiry_date'])); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Issue Location'); ?></div>
        <div class="value"><?php echo $this->Form->input('issue_location'); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Comments'); ?></div>
        <div class="value"><?php echo $this->Form->input('comments', array('type'=>'textarea')); ?></div>
    </div>

	<div class="controls view_controls">
	    <?php if(!$WizardMode){ ?>
        <input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
        <input type="button" value="<?php echo __("Cancel"); ?>" class="btn_cancel btn_left" url="Students/identities" onclick="jsForm.goto(this)"/>
        <?php }else{?>
            <?php 
                if(!$mandatory){
                echo $this->Form->hidden('nextLink', array('value'=>$nextLink)); 
                echo $this->Form->submit('Skip', array('div'=>false, 'name'=>'submit','class'=>"btn_save btn_right"));
                } 
            echo $this->Form->submit('Next', array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_right",'onclick'=>"return Config.checkValidate();")); 
      } ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>
