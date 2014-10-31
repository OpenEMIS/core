<div class="footer" lang="en" dir="ltr">
	<div class="language">
		<?php 
		if($this->Session->check('footer')){
			echo $this->Session->read('footer') . __('Version') . ' ' . $SystemVersion;
		}
		?>
	</div>
</div>