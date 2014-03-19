<?php echo $this->element('breadcrumb'); ?>
<?php echo $this->Html->script('app.date', false); ?>

<div id="comment" class="content_wrapper edit add">
     <h1>
        <span><?php echo __('Comments'); ?></span>
        <?php 
           if(!$WizardMode){ 
            if ($_edit && !$WizardMode) {
                echo $this->Html->link(__('Back'), array('action' => 'commentsView', $id), array('class' => 'divider'));
            }
        }
        ?>
    </h1>
	<?php
	echo $this->Form->create('StudentComment', array(
		'url' => array('controller' => 'Students', 'action' => 'commentsEdit'),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>
        <?php $obj = @$this->request->data['StudentComment']; ?>
	<?php echo $this->Form->input('StudentComment.id');?>
    
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
    <div class="controls">
		 <?php if(!$WizardMode){ ?>
        <input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/> 
        <?php echo $this->Html->link(__('Cancel'), array('action' => 'commentsView',$id), array('class' => 'btn_cancel btn_left')); ?>
        <?php }else{?>
            <?php 
                echo $this->Form->submit(__('Previous'), array('div'=>false, 'name'=>'submit','class'=>"btn_save btn_cancel_button btn_right"));
                if($mandatory!='1'){
                echo $this->Form->submit(__('Skip'), array('div'=>false, 'name'=>'submit','class'=>"btn_save btn_right"));
                } 
             if(!$wizardEnd){
                echo $this->Form->submit(__('Next'), array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_left",'onclick'=>"return Config.checkValidate();")); 
            }else{
                echo $this->Form->submit(__('Finish'), array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_left",'onclick'=>"return Config.checkValidate();")); 
            } 
      } ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>
