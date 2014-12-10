<div id="footer">
	<?php 
	if ($this->Session->check('footer')) {
		echo $this->Session->read('footer') . __('Version') . ' ' . $SystemVersion;
	}
	?>
</div>
