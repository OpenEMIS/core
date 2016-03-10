<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('panelBody');
?>

	<!-- Partners -->
	<div class="about-wrapper">
		<!-- Partners -->
		<div class="about-container">	
			<div id="partners">
				<?php foreach ($data as $key => $value): ?>
					<?php 
						$src = $value->file_content;
						$src = base64_encode(stream_get_contents($src));
						echo '<div class="partner-logo"><img src="data:image/jpeg;base64,'.$src.'" /></div>';
					 ?>
				<?php endforeach ?>
			</div>
		</div>	
	</div>

<?php $this->end() ?>