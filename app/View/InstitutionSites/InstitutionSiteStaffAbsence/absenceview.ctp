<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('institution_site', false);
echo $this->Html->script('institution_attendance', false);

echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Absence') . ' - ' . __('Staff'));

$this->start('contentActions');
echo $this->Html->link(__('List'), array('action' => 'attendanceStaffAbsence'), array('class' => 'divider'));
if ($_edit) {
	echo $this->Html->link(__('Edit'), array('action' => 'attendanceStaffAbsenceEdit', $absenceId), array('class' => 'divider'));
}
if ($_delete) {
	echo $this->Html->link(__('Delete'), array('action' => 'attendanceStaffAbsenceDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}

$this->end();

$this->start('contentBody');

$staff = $obj['Staff'];
$staffIdName = $this->Model->getName($staff, array('openEmisId'=>true));

$filesList = '';
foreach($attachments AS $objItem){
	$fileName = $objItem['InstitutionSiteStaffAbsenceAttachment']['file_name'];
	$fileId = $objItem['InstitutionSiteStaffAbsenceAttachment']['id'];
	$linkOptions = array('action' => 'attendanceStaffAttachmentsDownload', $fileId);
	$filesList .= '<div>'.$this->Html->link($fileName, $linkOptions).'</div>';
}

$dataModifiedUser = $obj['ModifiedUser'];
$dataCreatedUser = $obj['CreatedUser'];
?>

<div id="staffAbsenceAdd" class="">
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('InstitutionSite.id_name'); ?></div>
		<div class="col-md-6"><?php echo $staffIdName; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteStaffAbsence.first_date_absent'); ?></div>
		<div class="col-md-6"><?php echo $this->Utility->formatDate($obj['InstitutionSiteStaffAbsence']['first_date_absent']); ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteStaffAbsence.full_day_absent'); ?></div>
		<div class="col-md-6"><?php echo $obj['InstitutionSiteStaffAbsence']['full_day_absent']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteStaffAbsence.last_date_absent'); ?></div>
		<div class="col-md-6"><?php echo $this->Utility->formatDate($obj['InstitutionSiteStaffAbsence']['last_date_absent']); ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteStaffAbsence.start_time_absent'); ?></div>
		<div class="col-md-6"><?php echo $obj['InstitutionSiteStaffAbsence']['start_time_absent']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteStaffAbsence.end_time_absent'); ?></div>
		<div class="col-md-6"><?php echo $obj['InstitutionSiteStaffAbsence']['end_time_absent']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteStaffAbsence.reason'); ?></div>
		<div class="col-md-6"><?php echo $obj['StaffAbsenceReason']['name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('general.type'); ?></div>
		<div class="col-md-6"><?php echo $obj['InstitutionSiteStaffAbsence']['absence_type']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('general.comment'); ?></div>
		<div class="col-md-6"><?php echo $obj['InstitutionSiteStaffAbsence']['comment']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('general.attachments'); ?></div>
		<div class="col-md-6"><?php echo $filesList; ?></div>
	</div>
	<div class="row">
        <div class="col-md-3"><?php echo __('Modified by'); ?></div>
        <div class="col-md-6"><?php echo trim($dataModifiedUser['first_name'] . ' ' . $dataModifiedUser['last_name']); ?></div>
    </div>
    <div class="row">
        <div class="col-md-3"><?php echo __('Modified on'); ?></div>
        <div class="col-md-6"><?php echo $obj['InstitutionSiteStaffAbsence']['modified']; ?></div>
    </div>

    <div class="row">
        <div class="col-md-3"><?php echo __('Created by'); ?></div>
        <div class="col-md-6"><?php echo trim($dataCreatedUser['first_name'] . ' ' . $dataCreatedUser['last_name']); ?></div>
    </div>

    <div class="row">
        <div class="col-md-3"><?php echo __('Created on'); ?></div>
        <div class="col-md-6"><?php echo $obj['InstitutionSiteStaffAbsence']['created']; ?></div>
    </div>
</div>
<?php
$this->end();
?>