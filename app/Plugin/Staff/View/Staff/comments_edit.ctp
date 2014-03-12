<?php echo $this->element('breadcrumb'); ?>
<?php echo $this->Html->script('app.date', false); ?>

<div id="comment" class="content_wrapper edit add">
     <h1>
        <span><?php echo __('Comments'); ?></span>
        <?php 
        if ($_edit && !$WizardMode) {
            echo $this->Html->link(__('Back'), array('action' => 'commentsView', $id), array('class' => 'divider'));
        }
        ?>
    </h1>
	<?php
	echo $this->Form->create('StaffComment', array(
		'url' => array('controller' => 'Staff', 'action' => 'commentsEdit'),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>
    <?php $obj = @$this->request->data['StaffComment']; ?>
	<?php echo $this->Form->input('StaffComment.id');?>
    
     <div class="row">
        <div class="label"><?php echo __('Date'); ?></div>
         <div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'comment_date', array('desc' => true,'value' => $obj['comment_date'])); ?></div>
    </div>
	 <div class="row">
        <div class="label"><?php echo __('Title'); ?></div>
        <div class="value"><?php echo $this->Form->input('title'); ?></div>
    </div>
     <div class="row">
        <div class="label"><?php echo __('Comment'); ?></div>
        <div class="value"><?php echo $this->Form->input('comment', array('type'=>'textarea')); ?></div>
    </div>

	<div class="controls view_controls">
		 <?php if(!$WizardMode){ ?>
        <input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
        <?php echo $this->Html->link(__('Cancel'), array('action' => 'commentsView',$id), array('class' => 'btn_cancel btn_left')); ?>
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
