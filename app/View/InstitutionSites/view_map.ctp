<?php $obj = $data['InstitutionSite']; ?>
<?php if(is_numeric($obj['latitude']) && is_numeric($obj['longitude']) != ''){ ?>
<fieldset class="section_break" id="googlemap" style="padding-top: 10px;">
	<legend><?php echo __('Map'); ?></legend>
	<div style="float:right">
		<iframe width="650" height="250" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="https://maps.google.com/maps?q=<?php echo $obj['latitude']; ?>,+<?php echo $obj['longitude']; ?>+(<?php echo $obj['name']; ?>)&amp;hl=en&amp;ie=UTF8&amp;t=m&amp;ll=<?php echo $obj['latitude']; ?>,<?php echo $obj['longitude']; ?>&amp;spn=0.17081,0.44632&amp;z=11&amp;iwloc=&amp;output=embed"></iframe><br /><small><a href="https://maps.google.com/maps?q=<?php echo $obj['latitude']; ?>,+<?php echo $obj['longitude']; ?>+(<?php echo $obj['name']; ?>)&amp;hl=en&amp;ie=UTF8&amp;t=m&amp;ll=<?php echo $obj['latitude']; ?>,<?php echo $obj['longitude']; ?>&amp;spn=0.17081,0.44632&amp;z=11&amp;iwloc=&amp;source=embed" style="color:#0000FF;text-align:left">View Larger Map</a></small>
	</div>
</fieldset>
<?php } ?>