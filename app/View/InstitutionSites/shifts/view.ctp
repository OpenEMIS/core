<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Shift'));

$dataShift = $shiftObj['InstitutionSiteShift'];
$dataAcademicPeriod = $shiftObj['AcademicPeriod'];
$dataLocationSite = $shiftObj['InstitutionSite'];
$dataModifiedUser = $shiftObj['ModifiedUser'];
$dataCreatedUser = $shiftObj['CreatedUser'];

$this->start('contentActions');
echo $this->Html->link(__('List'), array('action' => 'shifts'), array('class' => 'divider'));
if ($_edit) {
	echo $this->Html->link(__('Edit'), array('action' => 'shiftsEdit', $dataShift['id']), array('class' => 'divider'));
}
if ($_delete) {
	echo $this->Html->link(__('Delete'), array('action' => 'shiftsDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
$this->end();

$this->start('contentBody');
?>

<div class="row">
	<div class="col-md-3"><?php echo __('Shift Name'); ?></div>
	<div class="col-md-6"><?php echo $dataShift['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Academic Period'); ?></div>
	<div class="col-md-6"><?php echo $dataAcademicPeriod['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Start Time'); ?></div>
	<div class="col-md-6"><?php echo $dataShift['start_time']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('End Time'); ?></div>
	<div class="col-md-6"><?php echo $dataShift['end_time']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Location'); ?></div>
	<div class="col-md-6"><?php echo $dataLocationSite['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Modified by'); ?></div>
	<div class="col-md-6"><?php echo trim($dataModifiedUser['first_name'] . ' ' . $dataModifiedUser['last_name']); ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Modified on'); ?></div>
	<div class="col-md-6"><?php echo $dataShift['modified']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Created by'); ?></div>
	<div class="col-md-6"><?php echo trim($dataCreatedUser['first_name'] . ' ' . $dataCreatedUser['last_name']); ?></div>
</div>

<div class="row">
	<div class="col-md-3"><?php echo __('Created on'); ?></div>
	<div class="col-md-6"><?php echo $dataShift['created']; ?></div>
</div>

<?php $this->end(); ?>
