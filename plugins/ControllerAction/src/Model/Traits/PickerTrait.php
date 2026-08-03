<?php
namespace ControllerAction\Model\Traits;

trait PickerTrait {
	// ROOT CAUSE FIX: this used to only ever try 'd-m-Y' and 'd/m/Y', completely ignoring the
	// system's actual configured Date Format (Administration > System Setup > System
	// Configurations). Since this runs for every 'date'-typed column in every table (wired up
	// automatically by ControllerAction.DatePicker in App\Model\Table\AppTable::initialize()),
	// any date format other than those two silently failed to convert here, then failed Cake's own
	// strict 'Y-m-d'-only marshal(), producing a null date - crashing save on NOT NULL columns, or
	// silently dropping the date on nullable ones. This was the single shared cause behind that
	// entire bug family. Now the configured system format is tried first; the two original
	// hardcoded formats remain as a fallback for backwards compatibility.
	protected function convertForDatePicker($data) {
		$format = 'Y-m-d';
		if (empty($data) || !is_string($data)) {
			return null;
		}

		// already in the format Cake's own marshaller accepts - nothing to do
		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
			return $data;
		}

		$ConfigItems = \Cake\ORM\TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
		$systemDateFormat = $ConfigItems->value('date_format') ?: 'd-m-Y';
		// bootstrap-datepicker cannot emit ordinals; parse without "S" (also strip legacy "31st" input)
		$editableDateFormat = preg_replace('/\s+/', ' ', trim(str_replace('S', '', $systemDateFormat))) ?: 'd-m-Y';
		$normalized = preg_replace('/(\d+)(st|nd|rd|th)\b/i', '$1', $data);

		// Try the actual configured system format first - this is what the datepicker widget really submits
		$dateObj = \DateTime::createFromFormat($editableDateFormat, $normalized);
		if ($dateObj === false) {
			$dateObj = \DateTime::createFromFormat($systemDateFormat, $data);
		}

		// to handle both d-m-y and d-m-Y because datepicker and cake doesnt validate (legacy fallback)
		if ($dateObj === false) {
			$dateObj = date_create_from_format("d-m-Y",$data);
		}
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
