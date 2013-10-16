<?php
echo $this->Html->script('institution_site', false);
?>


<div id="site" class="content_wrapper">
	
	
	<?php $obj = @$data['InstitutionSite']; 
        if($obj){
        ?>
	<h1>
		<span><?php echo @$data['Institution']['name'].' - '.$obj['name']; ?></span>
		
	</h1>	
	<fieldset class="section_break">
		<legend><?php echo __('Information'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Site Name'); ?></div>
			<div class="value" style="width: 400px;"><?php echo $obj['name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Site Code'); ?></div>
			<div class="value" type="text" name="code"><?php echo $obj['code']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Type'); ?></div>
			<div class="value"><?php echo $data['InstitutionSiteType']['name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Ownership'); ?></div>
			<div class="value"><?php echo $data['InstitutionSiteOwnership']['name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Status'); ?></div>
			<div class="value"><?php echo $data['InstitutionSiteStatus']['name']; ?></div>
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

	<?php if($obj['area_id']>0){ ?>
	<fieldset class="section_break">
		<legend id="area"><?php echo __('Area'); ?></legend>
		<?php echo @$this->Utility->showArea($this->Form, 'area_id',$obj['area_id'], array()); ?>
	</fieldset>
	<?php } ?>
	<?php if($obj['area_education_id']>0){ ?>
	<fieldset class="section_break">
		<legend id="education"><?php echo __('Area').' ('.__('Education').')'; ?></legend>
		<?php echo @$this->Utility->showArea($this->Form, 'area_education_id',$obj['area_education_id'], array()); ?>
	</fieldset>
	<?php } ?>
	<fieldset class="section_break">
		<legend><?php echo __('Location'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Address'); ?></div>
			<div class="value address" ><?php echo nl2br($obj['address']); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Postal Code'); ?></div>
			<div class="value" type="text" name="postal_code"><?php echo $obj['postal_code']; ?></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('Locality'); ?></div>
			<div class="value"><?php echo $data['InstitutionSiteLocality']['name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Latitude'); ?></div>
			<div class="value"><?php echo $obj['latitude']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Longitude'); ?></div>
			<div class="value"><?php echo $obj['longitude']; ?></div>
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
        <span id="gmap"></span>
	<script>
		$('#gmap').load(getRootURL()+'InstitutionSites/viewMap/<?php echo @$obj['id']; ?>');
	</script>
        <?php  }else{ ?>
        <?php echo __('Information not available'); ?>
        
        <?php } ?>
    
</div>