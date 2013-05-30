<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="site_history" class="content_wrapper">
	<h1>
		<span><?php echo __('Institution Site History'); ?></span>
		<?php echo $this->Html->link(__('Details'), array('action' => 'view'), array('class' => 'divider')); ?>
	</h1>
	<?php echo $this->element('alert'); ?>

    <?php if(!empty($data2)) : ?>
	<fieldset class="section_break">
		<legend><?php echo __('General'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Site Name'); ?></div>
			<div class="value">
				<span style="margin-left: 8px;"><?php echo $data['InstitutionSite']['name']; ?></span>
				<?php if(@sizeof($data2['InstitutionSiteHistory_name'])>0){ // && ( sizeof($data2['InstitutionSiteHistory_name']) != 1 && array_key_exists($data['InstitutionSite']['name'], $data2['InstitutionSiteHistory_name']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['InstitutionSiteHistory_name'] as $val => $time):?>
						<?php if($ctr == 1 && $data['InstitutionSite']['name'] == $val) continue; ?>
						<div class="table_row">
							<div class="table_cell cell_value"><?php echo $val; ?></div>
							<div class="table_cell"><?php echo $time; ?></div>
						</div>
						<?php $ctr++; endforeach;?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Site Code'); ?></div>
			<div class="value">
				<span style="margin-left: 8px;"><?php echo $data['InstitutionSite']['code']; ?></span>
				<?php if(@sizeof($data2['InstitutionSiteHistory_code'])>0){ // && ( sizeof($data2['InstitutionSiteHistory_code']) != 1 && array_key_exists($data['InstitutionSite']['name'], $data2['InstitutionSiteHistory_code']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['InstitutionSiteHistory_code'] as $val => $time):?>
						<?php if($ctr == 1 && $data['InstitutionSite']['code'] == $val) continue; ?>
						<div class="table_row">
							<div class="table_cell cell_value"><?php echo $val; ?></div>
							<div class="table_cell"><?php echo $time; ?></div>

						</div>
						<?php $ctr++; endforeach;?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Type'); ?></div>
			<div class="value"><?php //pr($data);?>
				<span style="margin-left: 8px;"><?php echo $data['InstitutionSiteType']['name']; ?></span>
				<?php if(@sizeof($data2['InstitutionSiteType_name'])>0){ // && ( sizeof($data2['InstitutionSiteType_name']) != 1 && array_key_exists($data['InstitutionSiteType']['name'], $data2['InstitutionSiteType_name']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['InstitutionSiteType_name'] as $val => $time):?>
						<?php if($ctr == 1 && $data['InstitutionSiteType']['name'] == $val) continue; ?>
						<div class="table_row">
							<div class="table_cell cell_value"><?php echo $val; ?></div>
							<div class="table_cell"><?php echo $time; ?></div>
						</div>
						<?php $ctr++; endforeach;?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Ownership'); ?></div>
			<div class="value">
				<span style="margin-left: 8px;"><?php echo $data['InstitutionSiteOwnership']['name']; ?></span>
				<?php if(@sizeof($data2['InstitutionSiteOwnership_name'])>0){ // && ( sizeof($data2['InstitutionSiteOwnership_name']) != 1 && array_key_exists($data['InstitutionSiteOwnership']['name'], $data2['InstitutionSiteOwnership_name']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['InstitutionSiteOwnership_name'] as $val => $time):?>
						<?php if($ctr == 1 && $data['InstitutionSiteOwnership']['name'] == $val) continue; ?>
						<div class="table_row">
							<div class="table_cell cell_value"><?php echo $time; ?></div>
							<div class="table_cell"><?php echo $time; ?></div>
				
						</div>
						<?php $ctr++; endforeach;?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Status'); ?></div>
			<div class="value">
				<span style="margin-left: 8px;"><?php echo $data['InstitutionSiteStatus']['name']; ?></span>
				<?php if(@sizeof($data2['InstitutionSiteStatus_name'])>0){ // && ( sizeof($data2['InstitutionSiteStatus_name']) != 1 && array_key_exists($data['InstitutionSiteStatus']['name'], $data2['InstitutionSiteStatus_name']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['InstitutionSiteStatus_name'] as $val => $time):?>
						<?php if($ctr == 1 && $data['InstitutionSiteStatus']['name'] == $val) continue; ?>
						<div class="table_row">
							<div class="table_cell cell_value"><?php echo $val; ?></div>
							<div class="table_cell"><?php echo $time; ?></div>

						</div>
						<?php $ctr++; endforeach;?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Date Opened'); ?></div>
			<div class="value">
				<span style="margin-left: 8px;"><?php echo $this->Utility->formatDate($data['InstitutionSite']['date_opened']); ?></span>
				<?php if(@sizeof($data2['date_opened'])>0){ // && ( sizeof($data2['date_opened']) != 1 && array_key_exists($data['Institution']['date_opened'], $data2['date_opened']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['date_opened'] as $val => $time):?>
						<?php if($ctr == 1 && $data['InstitutionSite']['date_opened'] == $val) continue; ?>
						<div class="table_row">
							<div class="table_cell cell_value"><?php echo $this->Utility->formatDate($val); ?></div>
							<div class="table_cell"><?php echo $time; ?></div>
						</div>
						<?php $ctr++; endforeach;?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="row last">
			<div class="label"><?php echo __('Date Closed'); ?></div>
			<div class="value">
				<span style="margin-left: 8px;"><?php echo $this->Utility->formatDate($data['InstitutionSite']['date_closed']); ?></span>
				<?php if(@sizeof($data2['date_opened'])>0){ // && ( sizeof($data2['date_opened']) != 1 && array_key_exists($data['Institution']['date_opened'], $data2['date_opened']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['date_closed'] as $val => $time):?>
						<?php if($ctr == 1 && $data['InstitutionSite']['date_closed'] == $val) continue; ?>
						<div class="table_row">
							<div class="table_cell cell_value"><?php echo $this->Utility->formatDate($val);?></div>
							<div class="table_cell"><?php echo $time; ?></div>
						</div>
						<?php $ctr++; endforeach;?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Area'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Level'); ?></div>
			<div class="value">
				<span style="margin-left: 8px;"><?php echo $data['Area']['name']; ?></span>
				<?php if(@sizeof($data2['Area_name'])>0){ // && ( sizeof($data2['Area_name']) != 1 && array_key_exists($data['Area']['name'], $data2['Area_name']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['Area_name'] as $val => $time):?>
						<?php if($ctr == 1 && $data['Area']['name'] == $val) continue; ?>
						<div class="table_row">
							<div class="table_cell cell_value"><?php echo $val; ?></div>
							<div class="table_cell"><?php echo $time; ?></div>

						</div>
						<?php $ctr++; endforeach;?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Location'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Address'); ?></div>
			<div class="value" style="width:400px;">
				<span style="margin-left: 8px;"><?php echo $data['InstitutionSite']['address']; ?></span>
				<?php if(@sizeof($data2['address'])>0){ // && ( sizeof($data2['address']) != 1 && array_key_exists($data['InstitutionSite']['address'], $data2['address']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['address'] as $val => $time):?>
						<?php if($ctr == 1 && $data['InstitutionSite']['address'] == $val) continue; ?>
						<div class="table_row">
							<div class="table_cell cell_value"><?php echo $val; ?></div>
							<div class="table_cell"><?php echo $time; ?></div>
						</div>
						<?php $ctr++; endforeach;?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Postal Code'); ?></div>
			<div class="value">
				<span style="margin-left: 8px;"><?php echo $data['InstitutionSite']['postal_code']; ?></span>
				<?php if(@sizeof($data2['postal_code'])>0){ // && ( sizeof($data2['postal_code']) != 1 && array_key_exists($data['InstitutionSite']['postal_code'], $data2['postal_code']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['postal_code'] as $val => $time):?>
						<?php if($ctr == 1 && $data['InstitutionSite']['postal_code'] == $val) continue; ?>
						<div class="table_row">
							<div class="table_cell cell_value"><?php echo $val; ?></div>
							<div class="table_cell"><?php echo $time; ?></div>

						</div>
						<?php $ctr++; endforeach;?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Locality'); ?></div>
			<div class="value">
				<span style="margin-left: 8px;"><?php echo $data['InstitutionSiteLocality']['name']; ?></span>
				 <?php if(@sizeof($data2['InstitutionSiteLocality_name'])>0){ // && ( sizeof($data2['InstitutionSiteLocality_name']) != 1 && array_key_exists($data['InstitutionSiteLocality']['name'], $data2['InstitutionSiteLocality_name']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['InstitutionSiteLocality_name'] as $val => $time):?>
						<?php if($ctr == 1 && $data['InstitutionSiteLocality']['name'] == $val) continue; ?>
						<div class="table_row">
							<div class="table_cell cell_value"><?php echo $val; ?></div>
							<div class="table_cell"><?php echo $time; ?></div>

						</div>
						<?php $ctr++; endforeach;?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Longitude'); ?></div>
			<div class="value">
				<span style="margin-left: 8px;"><?php echo $data['InstitutionSite']['longitude']; ?></span>
				<?php if(@sizeof($data2['longitude'])>0){ // && ( sizeof($data2['longitude']) != 1 && array_key_exists($data['InstitutionSite']['longitude'], $data2['longitude']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['longitude'] as $val => $time):?>
						<?php if($ctr == 1 && $data['InstitutionSite']['longitude'] == $val) continue; ?>
						<div class="table_row">
							<div class="table_cell cell_value"><?php echo $val; ?></div>
							<div class="table_cell"><?php echo $time; ?></div>
						</div>
						<?php $ctr++; endforeach;?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Latitude'); ?></div>
			<div class="value">
				<span style="margin-left: 8px;"><?php echo $data['InstitutionSite']['latitude']; ?></span>
				<?php if(@sizeof($data2['latitude'])>0){ // && ( sizeof($data2['latitude']) != 1 && array_key_exists($data['InstitutionSite']['latitude'], $data2['latitude']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['latitude'] as $val => $time):?>
						<?php if($ctr == 1 && $data['InstitutionSite']['latitude'] == $val) continue; ?>
						<div class="table_row">
							<div class="table_cell cell_value"><?php echo $val; ?></div>
							<div class="table_cell"><?php echo $time; ?></div>

						</div>
						<?php $ctr++; endforeach;?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Contact'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Contact Person '); ?></div>
			<div class="value">
				<span style="margin-left: 8px;"><?php echo $data['InstitutionSite']['contact_person']; ?></span>
				<?php if(@sizeof($data2['contact_person'])>0){ // && ( sizeof($data2['contact_person']) != 1 && array_key_exists($data['InstitutionSite']['contact_person'], $data2['contact_person']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['contact_person'] as $val => $time):?>
						<?php if($ctr == 1 && $data['InstitutionSite']['contact_person'] == $val) continue; ?>
						<div class="table_row">
							<div class="table_cell cell_value"><?php echo $val; ?></div>
							<div class="table_cell"><?php echo $time; ?></div>

						</div>
						<?php $ctr++; endforeach;?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Telephone'); ?></div>
			<div class="value">
				<span style="margin-left: 8px;"><?php echo $data['InstitutionSite']['telephone']; ?></span>
				<?php if(@sizeof($data2['telephone'])>0){ // && ( sizeof($data2['fax']) != 1 && array_key_exists($data['InstitutionSite']['telephone'], $data2['telephone']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['telephone'] as $val => $time):?>
						<?php if($ctr == 1 && $data['InstitutionSite']['telephone'] == $val) continue; ?>
						<div class="table_row">
							<div class="table_cell cell_value"><?php echo $val; ?></div>
							<div class="table_cell"><?php echo $time; ?></div>

						</div>
						<?php $ctr++; endforeach;?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Fax'); ?></div>
			<div class="value">
				<span style="margin-left: 8px;"><?php echo $data['InstitutionSite']['fax']; ?></span>
				<?php if(@sizeof($data2['fax'])>0){ // && ( sizeof($data2['fax']) != 1 && array_key_exists($data['InstitutionSite']['fax'], $data2['fax']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['fax'] as $val => $time):?>
						<?php if($ctr == 1 && $data['InstitutionSite']['fax'] == $val) continue; ?>
						<div class="table_row">
							<div class="table_cell cell_value"><?php echo $val; ?></div>
							<div class="table_cell"><?php echo $time; ?></div>

						</div>
						<?php $ctr++; endforeach;?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Email'); ?></div>
			<div class="value">
				<span style="margin-left: 8px;"><?php echo $data['InstitutionSite']['email']; ?></span>
				<?php if(@sizeof($data2['email'])>0){ // && ( sizeof($data2['email']) != 1 && array_key_exists($data['InstitutionSite']['email'], $data2['email']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['email'] as $val => $time):?>
						<?php if($ctr == 1 && $data['InstitutionSite']['email'] == $val) continue; ?>
						<div class="table_row">
							<div class="table_cell cell_value"><?php echo $val; ?></div>
							<div class="table_cell"><?php echo $time; ?></div>

						</div>
						<?php $ctr++; endforeach;?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Website'); ?></div>
			<div class="value">
				<span style="margin-left: 8px;"><?php echo $data['InstitutionSite']['website']; ?></span>
				 <?php if(@sizeof($data2['website'])>0){ // && ( sizeof($data2['website']) != 1 && array_key_exists($data['InstitutionSite']['website'], $data2['website']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['website'] as $val => $time):?>
						<?php if($ctr == 1 && $data['InstitutionSite']['website'] == $val) continue; ?>
						<div class="table_row">
							<div class="table_cell cell_value"><?php echo $val; ?></div>
							<div class="table_cell"><?php echo $time; ?></div>

						</div>
						<?php $ctr++; endforeach;?>
					</div>
				</div>
				 <?php } ?>
			</div>
		</div>
	</fieldset>
    <?php endif; ?>
</div>