<?php /*
  <?php echo $this->element('breadcrumb'); ?>
  <?php echo $this->Html->script('app.date', false); ?>

  <div id="employment" class="content_wrapper edit add">
  <h1>
  <span><?php echo __('Employment'); ?></span>
  <?php
  if ($_edit) {
  echo $this->Html->link(__('Back'), array('action' => 'employments'), array('class' => 'divider'));
  }
  ?>
  </h1>

  <?php

  echo $this->Form->create('StaffEmployment', array(
  'url' => array('controller' => 'Staff', 'action' => 'employmentsAdd'),
  'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
  ));
  ?>
  <div class="row">
  <div class="label"><?php echo __('Type'); ?></div>
  <div class="value"><?php echo $this->Form->input('employment_type_id', array('empty'=>__('--Select--'),'options'=>$employmentTypeOptions)); ?></div>
  </div>
  <div class="row">
  <div class="label"><?php echo __('Date'); ?></div>
  <div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'employment_date',array('desc' => true)); ?></div>
  </div>
  <div class="row">
  <div class="label"><?php echo __('Comment'); ?></div>
  <div class="value">
  <?php echo $this->Form->input('comment', array('type'=>'textarea')); ?>
  </div>
  </div>
  <div class="controls view_controls">
  <input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
  <?php echo $this->Html->link(__('Cancel'), array('action' => 'employments'), array('class' => 'btn_cancel btn_left')); ?>
  </div>
  <?php echo $this->Form->end(); ?>
  </div>
 * 
 */ ?>

<?php

echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_edit) {
	if (!empty($this->data[$model]['id'])) {
		$redirectAction = array('action' => 'employmentsView', $this->data[$model]['id']);
	} else {
		$redirectAction = array('action' => 'employments');
	}
	echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));
}
$this->end();
$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin' => 'Staff'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->Form->input('employment_type_id', array('options' => $employmentTypeOptions));
echo $this->FormUtility->datepicker('employment_date');
echo $this->Form->input('comment');
echo $this->FormUtility->getFormButtons(array('cancelURL' => $redirectAction));
echo $this->Form->end();
$this->end();
?>