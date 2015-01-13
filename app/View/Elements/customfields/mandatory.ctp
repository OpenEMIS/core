<?php
	if(isset($obj[$model]['is_mandatory']) && $obj[$model]['is_mandatory'] == 1) {
		echo " " . "<i class='fa fa-exclamation-circle' data-toggle='tooltip' data-placement='top' title='This field is mandatory'></i>";
	}
?>