<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Overview'));
$this->start('contentActions');
	if ($_edit) {
		echo $this->Html->link(__('Edit'), array('action' => 'edit'), array('class' => 'divider'));
	}
	if ($_delete) {
		echo $this->Html->link(__('Delete'), array('action' => 'delete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
	}
	if ($_execute) {
		echo $this->Html->link($this->Label->get('general.export'), array('action' => 'excel'), array('class' => 'divider'));
	}
	echo $this->Html->link(__('History'), array('action' => 'history'),	array('class' => 'divider'));
$this->end();

$this->start('contentBody');
$userObj = $data['SecurityUser'];
?>

<fieldset class="section_break">
	<legend><?php echo __('Information'); ?></legend>
	<?php
		$src = $this->Image->getBase64($userObj['photo_name'], $userObj['photo_content']);
		if (is_null($src)) {
			$src = $this->webroot . 'Students/img/default_student_profile.jpg';
		}
	?>
	<img src="<?php echo $src ?>" class="profile-image" alt="90x115" />
	<div class="row">
		<div class="col-md-3"><?php echo __('OpenEMIS ID'); ?></div>
		<div class="col-md-6"><?php echo $userObj['openemis_no']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('First Name'); ?></div>
		<div class="col-md-6"><?php echo $userObj['first_name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Middle Name'); ?></div>
		<div class="col-md-6"><?php echo $userObj['middle_name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Third Name'); ?></div>
		<div class="col-md-6"><?php echo $userObj['third_name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Last Name'); ?></div>
		<div class="col-md-6"><?php echo $userObj['last_name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Preferred Name'); ?></div>
		<div class="col-md-6"><?php echo $userObj['preferred_name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Gender'); ?></div>
		<div class="col-md-6"><?php echo $this->Utility->formatGender($userObj); ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Date Of Birth'); ?></div>
		<div class="col-md-6"><?php echo $this->Utility->formatDate($userObj['date_of_birth']); ?></div>
	</div>
</fieldset>

<fieldset class="section_break">
	<legend><?php echo __('Address'); ?></legend>
	<div class="row">
		<div class="col-md-3"><?php echo __('Address'); ?></div>
		<div class="col-md-6 address"><?php echo nl2br($userObj['address']); ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Postal Code'); ?></div>
		<div class="col-md-6"><?php echo $userObj['postal_code']; ?></div>
	</div>
</fieldset>

<?php if ($userObj['address_area_id']>0) : ?>
</fieldset>
	<fieldset class="section_break">
	<legend><?php echo __('Address Area'); ?></legend>
	<?php echo $this->FormUtility->areas($userObj['address_area_id'], 'AreaAdministrative'); ?>
</fieldset>
<?php endif ?>

<?php if ($userObj['birthplace_area_id']>0) : ?>
<fieldset class="section_break">
	<legend><?php echo __('Birth Place Area'); ?></legend>
	<?php echo $this->FormUtility->areas($userObj['birthplace_area_id'], 'AreaAdministrative'); ?>
</fieldset>
<?php endif ?>

<?php $this->end(); ?>
