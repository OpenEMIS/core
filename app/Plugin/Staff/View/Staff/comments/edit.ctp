<?php /*
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

	 <div class="controls">
		 <?php if(!$WizardMode){ ?>
        <input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
        <?php echo $this->Html->link(__('Cancel'), array('action' => 'commentsView',$id), array('class' => 'btn_cancel btn_left')); ?>
        <?php }else{?>
            <?php 
                echo $this->Form->submit(__('Previous'), array('div'=>false, 'name'=>'submit','class'=>"btn_save btn_right"));
                if(!$wizardEnd){
                    echo $this->Form->submit(__('Next'), array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_right",'onclick'=>"return Config.checkValidate();")); 
                }else{
                    echo $this->Form->submit(__('Finish'), array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_right",'onclick'=>"return Config.checkValidate();")); 
                }
                if($mandatory!='1' && !$wizardEnd){
                    echo $this->Form->submit(__('Skip'(, array('div'=>false, 'name'=>'submit','class'=>"btn_cancel btn_cancel_button btn_left"));
                } 
      } ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>
 * 
 */?>
<?php

echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
if ($_edit && !$WizardMode) {
    echo $this->Html->link($this->Label->get('general.back'), array('action' => 'commentsView', $this->data[$model]['id']), array('class' => 'divider'));
}

$this->end();

$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'commentsEdit'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->FormUtility->datepicker('comment_date', array('id' => 'CommentDate', 'data-date' => $this->data[$model]['comment_date']));
echo $this->Form->input('title');
echo $this->Form->input('comment', array('type' => 'textarea'));

echo $this->FormUtility->getFormWizardButtons(array(
    'cancelURL' => array('action' => 'commentsView', $this->data[$model]['id']),
    'WizardMode' => $WizardMode,
    'WizardEnd' => isset($wizardEnd) ? $wizardEnd : NULL,
    'WizardMandatory' => isset($mandatory) ? $mandatory : NULL
));
echo $this->Form->end();
$this->end();
?>
