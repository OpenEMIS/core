<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="history" class="content_wrapper history">
	<h1>
		<span><?php echo __('Institution History'); ?></span>
		<?php echo $this->Html->link(__('Details'), array('action' => 'view'), array('class' => 'divider')); ?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<?php if(!empty($data2)) : ?>
	<fieldset class="section_break">
		<legend><?php echo __('General'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Institution Name'); ?></div>
			<div class="value">
				<span style="margin-left: 8px;"><?php echo $data['Institution']['name']; ?></span>
				<?php if(@sizeof($data2['History_name'])>0){ // && ( sizeof($data2['History_name']) != 1 && array_key_exists($data['Institution']['name'], $data2['History_name']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['History_name'] as $val => $time):?>
						<?php if($ctr == 1 && $data['Institution']['name'] == $val) continue; ?>
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
			<div class="label"><?php echo __('Institution Code'); ?></div>
			<div class="value">
				<span style="margin-left: 8px;"><?php echo $data['Institution']['code']; ?></span>
				<?php if(@sizeof($data2['code'])>0){ // && ( sizeof($data2['code']) != 1 && array_key_exists($data['Institution']['code'], $data2['code']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['code'] as $val => $time):?>
						<?php if($ctr == 1 && $data['Institution']['code'] == $val) continue; ?>
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
			<div class="label"><?php echo __('Sector'); ?></div>
			<div class="value">
				<span style="margin-left: 8px;"><?php echo $data['InstitutionSector']['name']; ?></span>
				<?php if(@sizeof($data2['Sector_name'])>0){ // && ( sizeof($data2['Sector_name']) != 1 && array_key_exists($data['InstitutionSector']['name'], $data2['Sector_name']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['Sector_name'] as $val => $time):?>
						<?php if($ctr == 1 && $data['InstitutionSector']['name'] == $val) continue; ?>
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
			<div class="label"><?php echo __('Provider'); ?></div>
			<div class="value">
				<span style="margin-left: 8px;"><?php echo $data['InstitutionProvider']['name']; ?></span>
				<?php if(@sizeof($data2['Provider_name'])>0){ // && ( sizeof($data2['Provider_name']) != 1 && array_key_exists($data['InstitutionProvider']['name'], $data2['Provider_name']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['Provider_name'] as $val => $time):?>
						<?php if($ctr == 1 && $data['InstitutionProvider']['name'] == $val) continue; ?>
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
			<div class="label"><?php echo __('Status'); ?></div>
			<div class="value">
				<span style="margin-left: 8px;"><?php echo $data['InstitutionStatus']['name']; ?></span>
				<?php if(@sizeof($data2['Status_name'])>0){ // && ( sizeof($data2['Status_name']) != 1 && array_key_exists($data['InstitutionStatus']['name'], $data2['Status_name']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['Status_name'] as $val => $time):?>
						<?php if($ctr == 1 && $data['InstitutionStatus']['name'] == $val) continue; ?>
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
				<span style="margin-left: 8px;"><?php echo $this->Utility->formatDate($data['Institution']['date_opened']);  ?></span>
				<?php if(@sizeof($data2['date_opened'])>0){ // && ( sizeof($data2['date_opened']) != 1 && array_key_exists($data['Institution']['date_opened'], $data2['date_opened']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['date_opened'] as $val => $time):?>
						<?php if($ctr == 1 && $data['Institution']['date_opened'] == $val) continue; ?>
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
				<span style="margin-left: 8px;"><?php echo $this->Utility->formatDate($data['Institution']['date_closed']);  ?></span>
				<?php if(@sizeof($data2['date_opened'])>0){ // && ( sizeof($data2['date_opened']) != 1 && array_key_exists($data['Institution']['date_opened'], $data2['date_opened']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['date_closed'] as $val => $time):?>
						<?php if($ctr == 1 && $data['Institution']['date_closed'] == $val) continue; ?>
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
	</fieldset>
	<fieldset class="section_break">
		<legend><?php echo __('Location'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Address'); ?></div>
			<div class="value">
				<div class="cell_value"><span style="margin-left: 8px;"><?php echo $data['Institution']['address']; ?></span></div>
				<?php if(@sizeof($data2['address'])>0){ // && ( sizeof($data2['address']) != 1 && array_key_exists($data['Institution']['address'], $data2['address']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['address'] as $val => $time){?>
						
						<?php if($ctr == 1 && trim($data['Institution']['address']) === trim($val)) continue; ?>
						<div class="table_row">
							<div class="table_cell cell_value"><?php echo $val; ?></div>
							<div class="table_cell"><?php echo $time; ?></div>
						</div>
						<?php $ctr++; }?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Postal Code'); ?></div>
			<div class="value">
				<span style="margin-left: 8px;"><?php echo $data['Institution']['postal_code']; ?></span>
				<?php if(@sizeof($data2['postal_code'])>0){ // && ( sizeof($data2['postal_code']) != 1 && array_key_exists($data['Institution']['postal_code'], $data2['postal_code']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['postal_code'] as $val => $time):?>
						<?php if($ctr == 1 && $data['Institution']['postal_code'] == $val) continue; ?>
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
				<span style="margin-left: 8px;"><?php echo $data['Institution']['contact_person']; ?></span>
				<?php if(@sizeof($data2['contact_person'])>0){ // && ( sizeof($data2['contact_person']) != 1 && array_key_exists($data['Institution']['contact_person'], $data2['contact_person']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['contact_person'] as $val => $time):?>
						<?php if($ctr == 1 && $data['Institution']['contact_person'] == $val) continue; ?>
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
				<span style="margin-left: 8px;"><?php echo $data['Institution']['telephone']; ?></span>
				<?php if(@sizeof($data2['telephone'])>0){ // && ( sizeof($data2['telephone']) != 1 && array_key_exists($data['Institution']['telephone'], $data2['telephone']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['telephone'] as $val => $time):?>
						<?php if($ctr == 1 && $data['Institution']['telephone'] == $val) continue; ?>
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
				<span style="margin-left: 8px;"><?php echo $data['Institution']['fax']; ?></span>
				<?php if(@sizeof($data2['fax'])>0){ // && ( sizeof($data2['fax']) != 1 && array_key_exists($data['Institution']['fax'], $data2['fax']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['fax'] as $val => $time):?>
						<?php if($ctr == 1 && $data['Institution']['fax'] == $val) continue; ?>
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
				<span style="margin-left: 8px;"><?php echo $data['Institution']['email']; ?></span>
				<?php if(@sizeof($data2['email'])>0){ // && ( sizeof($data2['email']) != 1 && array_key_exists($data['Institution']['email'], $data2['email']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['email'] as $val => $time):?>
						<?php if($ctr == 1 && $data['Institution']['email'] == $val) continue; ?>
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
				<span style="margin-left: 8px;"><?php echo $data['Institution']['website']; ?></span>
				<?php if(@sizeof($data2['website'])>0){ // && ( sizeof($data2['website']) != 1 && array_key_exists($data['Institution']['website'], $data2['website']) ) ) { ?>
				<div class="table" style="margin-top: 10px;">
					<div class="table_body">
						<?php $ctr = 1; foreach($data2['website'] as $val => $time):?>
						<?php if($ctr == 1 && $data['Institution']['website'] == $val) continue; ?>
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
<script type="text/javascript">
var values;
$('.table_body').each(function(i, obj){
    values = $(this).html().trim();
    if (values == null || values == '' || values == undefined) {
        $(this).parent().remove();
    }
});
</script>