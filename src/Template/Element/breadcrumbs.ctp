<ul class="breadcrumb panel-breadcrumb">
	<?php
	foreach($paths as $i => $item) {
		$nameKey = (isset($nameKey))? $nameKey: 'name';
		$idKey = (isset($idKey))? $idKey: 'id';
		$itemName = ($item[$model][$idKey]) == -1 ? __('World') : $item[$model][$nameKey];	
		if($i == count($paths)-1) {
			echo '<li class="active">' . $itemName . '</li>';
		} else {
			echo '<li>' . $this->Html->link($itemName, array('action' => $this->action, 'parent' => $item[$model][$idKey])) . '</li>';
		}
	}
	?>
</ul>