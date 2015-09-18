<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('panelBody');
?>
<div class="wrapper panel panel-body">
	<p>The <a href="http://www.openemis.org" target="blank">OpenEMIS</a> initiative aims to deploy a high-quality Education Management Information System (EMIS) designed to collect and report data on schools, students, teachers and staff. The system was conceived by UNESCO to be a royalty-free system that can be easily customized to meet the specific needs of member countries.</p>
	<h5><strong><?php echo __('Contact'); ?></strong></h5>
	<p><strong><?php echo __('Telephone'); ?>:</strong> +65 6659 6068<br>
	<strong><?php echo __('Email'); ?>:</strong> <u><a href="mailto:support@openemis.org">support@openemis.org</a></u><br>
	<strong><?php echo __('Address'); ?>:</strong> 18 Sin Ming Lane #06-38 Midview City Singapore 573960 </p>
</div>
<?php $this->end() ?>