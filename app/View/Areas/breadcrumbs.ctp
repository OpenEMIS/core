<ul class="breadcrumb">
	<?php
	foreach($paths as $i => $item) {
		if($model == 'AreaAdministrative') {
			$itemName = ($item[$model]['parent_id']) == -1 ? __('All') : $item[$model]['name'];
		} else {
			$itemName = $item[$model]['name'];
		}
		if($i == count($paths)-1) {
			echo '<li class="active">' . $itemName . '</li>';
		} else {
			echo '<li>' . $this->Html->link($itemName, array('action' => $this->action, 'parent' => $item[$model]['id'])) . '</li>';
		}
	}
	?>
</ul>