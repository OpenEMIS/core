<?php
	if(isset($obj[$model]['is_mandatory']) && $obj[$model]['is_mandatory'] == 1) {
		$mandatoryText = __('This field is mandatory.');

		echo ' <i class="fa fa-exclamation-circle" data-toggle="tooltip" data-placement="top" title="' . $mandatoryText . '"></i>';
	}
?>
