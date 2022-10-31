<?php
namespace ControllerAction\Model\Traits;

trait PickerTrait {
	protected function convertForDatePicker($data) {
		$format = 'Y-m-d';
		// to handle both d-m-y and d-m-Y because datepicker and cake doesnt validate
		$dateObj = date_create_from_format("d-m-Y",$data);
		if ($dateObj === false) {
			$dateObj = date_create_from_format("d/m/Y",$data);
		}
		if ($dateObj !== false) {
			return $dateObj->format($format);
		} else {
			// failure
			return null;
		}
	}

	protected function convertForTimePicker($data) {
		$format = 'H:i:s';
		return (!empty($data))? date($format, strtotime($data)): null;
	}
}
