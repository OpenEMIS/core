<?php
namespace Staff\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class Absence extends Entity
{
	protected $_virtual = ['days', 'time'];
	
    protected function _getDays() {
    	$name = '';
    	$InstitutionSiteStaffAbsences = TableRegistry::get('Institution.InstitutionSiteStaffAbsences');
    	$settingWeekdays = $InstitutionSiteStaffAbsences->getWeekdaysBySetting();

    	if ($this->full_day_absent == 'Yes') {
			$stampFirstDateAbsent = strtotime($this->first_date_absent);
			$stampLastDateAbsent = strtotime($this->last_date_absent);
			if(!empty($this->last_date_absent) && $stampLastDateAbsent > $stampFirstDateAbsent){
				$lastDateFormatted = $InstitutionSiteStaffAbsences->formatDate($this->last_date_absent, null, false);
				$totalWeekdays = $InstitutionSiteStaffAbsences->getAbsenceDaysBySettings($this->first_date_absent, $this->last_date_absent, $settingWeekdays);
				$noOfDays = sprintf('%s (to %s)', $totalWeekdays, $lastDateFormatted);
			}else{
				$noOfDays = 1;
			}
		} else {
			$noOfDays = 1;
		}
    	return $noOfDays;
	}

	protected function _getTime() {
    	$name = '';

    	if ($this->has('full_day_absent')) {
    		$InstitutionSiteStaffAbsences = TableRegistry::get('Institution.InstitutionSiteStaffAbsences');
    		$settingWeekdays = $InstitutionSiteStaffAbsences->getWeekdaysBySetting();

    		if ($this->full_day_absent == 'Yes') {
				$timeStr = __('full day');
    		} else {
				$timeStr = sprintf('%s - %s', $absenceObj['start_time_absent'], $absenceObj['end_time_absent']);
    		}
    	}
    	return $timeStr;
	}
}
