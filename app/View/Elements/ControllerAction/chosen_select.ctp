<?php
	$chosenSelectList = array();
	if (isset($data[$dataModel])) {
		foreach ($data[$dataModel] as $obj) {
			$chosenSelectData = isset($obj[$dataField]) ? $obj[$dataField] : '';
			if (!empty($chosenSelectData)) {
				$chosenSelectList[] = $chosenSelectData;
			}
		}
	}
	echo implode(', ', $chosenSelectList);
?>	
