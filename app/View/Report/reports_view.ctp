<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Custom Report'));

$this->start('contentActions');
$isSharedReport = $data['ReportTemplate']['security_user_id'] == 0;
echo $this->Html->link(__('List'), array('action' => 'index'), array('class' => 'divider'));
if (($isSharedReport && $_accessControl->check('Report', 'sharedReportDelete')) || (!$isSharedReport && $_accessControl->check('Report', 'reportsDelete'))) {
	echo $this->Html->link(__('Delete'), array('action' => 'reportsDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
$this->end();

$this->assign('contentId', 'report');

$this->start('contentBody');

echo $this->Form->create('Report', array(
	'target' => 'blank',
	'url' => array('controller' => 'Report', 'action' => 'reportsWizard', 'load', $data['ReportTemplate']['id']),
	'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
));
?>
<div class="row">
	<label class="col-md-3"><?php echo __('Name'); ?></label>
	<div class="col-md-4"><?php echo $data['ReportTemplate']['name']; ?></div>
</div>

<div class="row">
	<label class="col-md-3"><?php echo __('Description'); ?></label>
	<div class="col-md-4"><?php echo $data['ReportTemplate']['description']; ?></div>
</div>

<div class="row">
	<label class="col-md-3"><?php echo __('Format'); ?></label>
	<div class="col-md-4">
		<?php 
		echo $this->Form->input('Output', array('label' => false, 'class' => 'form-control', 'options' => $outputOptions));
		?>
	</div>
</div>
<?php
// security_user_id = 0 (shared report)
if (($isSharedReport && $_accessControl->check('Report', 'sharedReportRun')) || (!$isSharedReport && $_accessControl->check('Report', 'reportsWizard'))) :
	?>
	<div class="controls view_controls">
		<input type="submit" value="<?php echo __('Run'); ?>" class="btn_save btn_right" />
	</div>
<?php 
endif;
echo $this->Form->end();
$this->end(); 
?>
