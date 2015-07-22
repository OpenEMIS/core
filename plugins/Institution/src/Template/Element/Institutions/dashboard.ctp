<div class="overview-wrapper alert overview-box">
	<a data-dismiss="alert" href="#" aria-hidden="true" class="close">×</a>
	<div class="data-section">
		<i class="kd-institutions icon"></i>
		<div class="data-field">
			<h4>Total Institutions:</h4>
			<h1 class="data-header"><?= $institutionSiteArray['count'] ?></h1>
		</div>
	</div>

	<div class="data-section">
		<div class="data-field">
			<?php echo $this->element('doughnut') ?>
			<h4>Types</h4>
			<?php
				foreach($institutionSiteArray['type'] as $siteCount){
					echo '<p><div class="data-blue"></div><strong>'.$siteCount['institution_site_type']['name'].':</strong> '.$siteCount['count'].'</p>';
				}
			?>
		</div>
	</div>

	<div class="data-section">
		<div class="data-field">
			<?php echo $this->element('doughnut') ?>
			<h4>Types</h4>
			<?php
				foreach($institutionSiteArray['sector'] as $siteCount){
					echo '<p><div class="data-blue"></div><strong>'.$siteCount['institution_sector_type']['name'].':</strong> '.$siteCount['count'].'</p>';
				}
			?>
		</div>
	</div>

	<div class="data-section data-field">
		<h4>Total Male Students</h4>
		<h1 class="data-header">200, 000</h1>
	</div>
</div>
