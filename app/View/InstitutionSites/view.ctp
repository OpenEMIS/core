<?php
echo $this->Html->script('institution_site', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="site" class="content_wrapper">
	<h1>
		<span><?php echo __('Institution Site Information'); ?></span>
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
	
	<?php $obj = $data['InstitutionSite']; ?>
		
	<fieldset class="section_break">
		<legend><?php echo __('General'); ?></legend>
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
	
	<fieldset class="section_break">
		<legend><?php echo __('Area'); ?></legend>   
		<?php
		//pr($arealevel);
		$ctr = 0;
		foreach($levels as $levelid => $levelName){
			$areaVal = array('id'=>'0','name'=>'a');
			foreach($arealevel as $arealevelid => $arrval){
				if($arrval['level_id'] == $levelid) {
					$areaVal = $arrval;
					continue;
				}
			}
			echo '<div class="row">
						<div class="label">'.__($levelName).'</div>
						<div class="value" value="'.$areaVal['id'].'" name="area_level_'.$ctr.'" type="select">'.($areaVal['name']=='a'?'':$areaVal['name']).'</div>
					</div>';
			$ctr++;
		}
		?>
	</fieldset>
	
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
	
    <?php if(is_numeric($obj['latitude']) && is_numeric($obj['longitude']) != ''){ ?>
	<fieldset class="section_break" id="googlemap" style="padding-top: 10px;">
		<legend><?php echo __(''); ?>Map</legend>
		<div style="float:right">
			<iframe width="650" height="250" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="https://maps.google.com/maps?q=<?php echo $obj['latitude']; ?>,+<?php echo $obj['longitude']; ?>+(<?php echo $data['Institution']['name'].'-'.$obj['name']; ?>)&amp;hl=en&amp;ie=UTF8&amp;t=m&amp;ll=<?php echo $obj['latitude']; ?>,<?php echo $obj['longitude']; ?>&amp;spn=0.17081,0.44632&amp;z=11&amp;iwloc=&amp;output=embed"></iframe><br /><small><a href="https://maps.google.com/maps?q=<?php echo $obj['latitude']; ?>,+<?php echo $obj['longitude']; ?>+(<?php echo $data['Institution']['name'].'-'.$obj['name']; ?>)&amp;hl=en&amp;ie=UTF8&amp;t=m&amp;ll=<?php echo $obj['latitude']; ?>,<?php echo $obj['longitude']; ?>&amp;spn=0.17081,0.44632&amp;z=11&amp;iwloc=&amp;source=embed" style="color:#0000FF;text-align:left">View Larger Map</a></small>
		</div>
	</fieldset>
    <?php } ?>
</div>