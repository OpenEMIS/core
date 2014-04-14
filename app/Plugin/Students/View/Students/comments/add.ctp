<?php
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'commentsAdd'));
echo $this->Form->create($model, $formOptions);
echo $this->FormUtility->datepicker('comment_date', array('id' => 'CommentDate'));
echo $this->Form->input('title');
echo $this->Form->input('comment', array('type' => 'textarea'));

if($WizardMode) {
echo '<div class="add_more_controls">';
echo $this->Form->submit(__('Add More'), array('div'=>false, 'name'=>'submit','class'=>"btn_save btn_right"));
echo '</div>';
}

//echo '<div class="form-group">';
//echo '<div class="col-md-offset-4">';

if(!$WizardMode) {
        echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'comments')));
	
} else {
	echo $this->Form->submit(__('Previous'), array('div'=>false, 'name'=>'submit','class'=>"btn_save btn_right"));
	if(!$wizardEnd) {
		echo $this->Form->submit(__('Next'), array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_right")); 
	} else {
		echo $this->Form->submit(__('Finish'), array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_right")); 
	} 
	if($mandatory!='1' && !$wizardEnd){
		echo $this->Form->submit(__('Skip'), array('div'=>false, 'name'=>'submit','class'=>"btn_cancel btn_cancel_button btn_left"));
	}
}

//echo '</div>';
//echo '</div>';
echo $this->Form->end();
$this->end();
?>
