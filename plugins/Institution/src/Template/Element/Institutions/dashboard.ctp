<?php echo $this->Html->script('doughnut', ['block' => true]); ?>
<div class="overview-wrapper alert overview-box">
	<a data-dismiss="alert" href="#" aria-hidden="true" class="close">×</a>
	<div class="data-section">
		<i class="kd-institutions icon"></i>
		<div class="data-field">
			<h4>Total Institutions:</h4>
			<h1 class="data-header">
			<?= $institutionCount ?>
			</h1>
		</div>
	</div>
	<script>
		function getRandomColor() {
		    var letters = '0123456789ABCDEF'.split('');
		    var color = '#';
		    for (var i = 0; i < 6; i++ ) {
		        color += letters[ Math.floor(Math.random() * 16) ];
		    }
	    	return color;
		}
	</script>
	<?php
	$doughnut = $this->element('doughnut');

	// Start for loop for items in institutionSiteArray
	foreach ($institutionSiteArray as $key => $value) {
	?>
	<div class="data-section">
	<?php echo $doughnut ?>	
		<div class="data-field">
			<h4><?=ucfirst($key)?></h4>
			<?php
				foreach($institutionSiteArray[$key] as $siteCount){
					echo '<div class=data type="'.$key.'" data-key="'.$siteCount['institution_site_'.$key]['name'].'" data-value="'.$siteCount['count'].'" ><div class="data-pink"></div><strong>'.$siteCount['institution_site_'.$key]['name'].':</strong> '.$siteCount['count'].'</div>';
				}
			?>
		</div>
	</div>
	
	<?php
	// End For loop for items in institutionSiteArray
	}
	?>
</div>
