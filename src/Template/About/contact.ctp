<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('panelBody');
?>
<div class="wrapper panel panel-body">
	<!-- Contact Info -->
	<div class="about-wrapper">
		<div class="about-container">
			<div class="img-logo">
				<?= $this->Html->image('openemis_logo.png');?>
			</div>	

			<div class="about-content">
				<p>The <a href="http://www.openemis.org" target="blank">OpenEMIS</a> initiative aims to deploy a high-quality Education Management Information System (EMIS) designed to collect and report data on schools, students, teachers and staff. The system was conceived by UNESCO to be a royalty-free system that can be easily customized to meet the specific needs of member countries.</p>

				<div class="about-info">
					<i class="fa fa-phone fa-lg"></i>
					<span>+65 6659 6068</span>
				</div>

				<div class="about-info">
					<i class="fa fa-envelope"></i>
					<span>
						<a href="mailto:support@openemis.org">support@openemis.org</a>
					</span>	
				</div>

				<div class="about-info">
					<i class="fa fa-map-marker fa-lg"></i>
					<span>18 Sin Ming Lane #06-38 Midview City Singapore 573960</span>
				</div>
			</div>
		</div>	
	</div>
</div>
<?php $this->end() ?>