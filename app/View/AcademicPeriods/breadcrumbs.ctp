<ul class="breadcrumb">
	<?php
	foreach($paths as $i => $item) {
		if($i == count($paths)-1) {
			echo '<li class="active">' . $item[$model]['name'] . '</li>';
		} else {
			echo '<li>' . $this->Html->link($item[$model]['name'], array('action' => $this->action, 'parent' => $item[$model]['id'])) . '</li>';
		}
	}
	?>
</ul>