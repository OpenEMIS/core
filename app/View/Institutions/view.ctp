<?php
echo $this->Html->script('institution', false);
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="institution" class="content_wrapper">
	<h1>
		<span><?php echo __('Institution Information'); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'edit'), array('class' => 'divider'));
		}
		if($_delete) {
			echo $this->Html->link(__('Delete'), array('action' => 'delete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
		}
		echo $this->Html->link(__('History'), array('action' => 'history'),	array('class' => 'divider')); 
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<?php $obj = $data['Institution']; ?>
	
	<fieldset class="section_break">
		<legend><?php echo __('General'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Institution Name'); ?></div>
			<div class="value"><?php echo $obj['name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Institution Code'); ?></div>
			<div class="value"><?php echo $obj['code']; ?></div>
		</div>
        <div class="row">
			<div class="label"><?php echo __('Sector'); ?></div>
			<div class="value"><?php echo $data['InstitutionSector']['name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Provider'); ?></div>
			<div class="value"><?php echo $data['InstitutionProvider']['name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Status'); ?></div>
			<div class="value"><?php echo $data['InstitutionStatus']['name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Date Opened'); ?></div>
			<div class="value"><?php echo $this->Utility->formatDate($obj['date_opened']); ?></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('Date Closed'); ?></div>
			<div class="value"><?php echo $this->Utility->formatDate($obj['date_closed']); ?></div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Location'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Address'); ?></div>
			<div class="value" class="address" style="width:400px"><?php echo nl2br($obj['address']); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Postal Code'); ?></div>
			<div class="value"><?php echo $obj['postal_code']; ?></div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Contact'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Contact Person'); ?></div>
			<div class="value"><?php echo $obj['contact_person']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Telephone'); ?></div>
			<div class="value"><?php echo $obj['telephone']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Fax'); ?></div>
			<div class="value"><?php echo $obj['fax']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Email'); ?></div>
			<div class="value"><?php echo $obj['email']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Website'); ?></div>
			<div class="value"><?php echo $obj['website']; ?></div>
		</div>
	</fieldset>
</div>