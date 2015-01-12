<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Overview'));
$this->start('contentActions');
if ($_edit) {
	echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'edit'), array('class' => 'divider'));
}
if ($_delete) {
	echo $this->Html->link($this->Label->get('general.delete'), array('action' => 'delete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
if ($_execute) {
	echo $this->Html->link($this->Label->get('general.export'), array('action' => 'excel'), array('class' => 'divider'));
}
echo $this->Html->link(__('History'), array('action' => 'history'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
$obj = $data['InstitutionSite'];
?>

<fieldset class="section_break">
	<legend><?php echo __('Information'); ?></legend>
	<div class="row">
		<div class="col-md-3"><?php echo __('Name'); ?></div>
		<div class="col-md-6" style="width: 400px;"><?php echo $obj['name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Alternative Name'); ?></div>
		<div class="col-md-6" style="width: 400px;"><?php echo $obj['alternative_name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Code'); ?></div>
		<div class="col-md-6"><?php echo $obj['code']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Provider'); ?></div>
		<div class="col-md-6"><?php echo $data['InstitutionSiteProvider']['name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Sector'); ?></div>
		<div class="col-md-6"><?php echo $data['InstitutionSiteSector']['name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Type'); ?></div>
		<div class="col-md-6"><?php echo $data['InstitutionSiteType']['name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Ownership'); ?></div>
		<div class="col-md-6"><?php echo $data['InstitutionSiteOwnership']['name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Gender'); ?></div>
		<div class="col-md-6"><?php echo $data['InstitutionSiteGender']['name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Status'); ?></div>
		<div class="col-md-6"><?php echo $data['InstitutionSiteStatus']['name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Date Opened'); ?></div>
		<div class="col-md-6"><?php echo $this->Utility->formatDate($obj['date_opened']); ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Date Closed'); ?></div>
		<div class="col-md-6"><?php echo $this->Utility->formatDate($obj['date_closed']); ?></div>
	</div>
</fieldset>
<fieldset class="section_break">
	<legend><?php echo __('Location'); ?></legend>
	<div class="row">
		<div class="col-md-3"><?php echo __('Address'); ?></div>
		<div class="col-md-6"><?php echo nl2br($obj['address']); ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Postal Code'); ?></div>
		<div class="col-md-6"><?php echo $obj['postal_code']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Locality'); ?></div>
		<div class="col-md-6"><?php echo $data['InstitutionSiteLocality']['name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Latitude'); ?></div>
		<div class="col-md-6"><?php echo $obj['latitude']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Longitude'); ?></div>
		<div class="col-md-6"><?php echo $obj['longitude']; ?></div>
	</div>
</fieldset>

<?php if ($obj['area_id'] > 0) : ?>
<fieldset class="section_break">
	<legend><?php echo __('Area'); ?></legend>
	<?php echo $this->FormUtility->areas($obj['area_id']); ?>
</fieldset>
<?php endif; ?>

<?php if ($obj['area_education_id'] > 0) : ?>
<fieldset class="section_break">
	<legend><?php echo __('Area') . ' (' . __('Education') . ')'; ?></legend>
	<?php echo $this->FormUtility->areas($obj['area_education_id'], 'AreaEducation'); ?>
</fieldset>
<?php endif; ?>

<fieldset class="section_break">
	<legend><?php echo __('Contact'); ?></legend>
	<div class="row">
		<div class="col-md-3"><?php echo __('Contact Person'); ?></div>
		<div class="col-md-6"><?php echo $obj['contact_person']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Telephone'); ?></div>
		<div class="col-md-6"><?php echo $obj['telephone']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Fax'); ?></div>
		<div class="col-md-6"><?php echo $obj['fax']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Email'); ?></div>
		<div class="col-md-6"><?php echo $obj['email']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Website'); ?></div>
		<div class="col-md-6"><?php echo $obj['website']; ?></div>
	</div>
</fieldset>
<span id="gmap"></span>
<script>
	$('#gmap').load(getRootURL() + 'InstitutionSites/viewMap/');
</script>

<?php $this->end(); ?>
