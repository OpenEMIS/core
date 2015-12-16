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
    	$InstitutionStaffAbsences = TableRegistry::get('Institution.InstitutionStaffAbsences');
    	$settingWeekdays = $InstitutionStaffAbsences->getWeekdaysBySetting();

		$stampFirstDateAbsent = strtotime($this->start_date);
		$stampLastDateAbsent = strtotime($this->end_date);
		if(!empty($this->end_date) && $stampLastDateAbsent > $stampFirstDateAbsent){
			$lastDateFormatted = $InstitutionStaffAbsences->formatDate($this->end_date, null, false);
			$totalWeekdays = $InstitutionStaffAbsences->getAbsenceDaysBySettings($this->start_date, $this->end_date, $settingWeekdays);
			$noOfDays = sprintf('%s (to %s)', $totalWeekdays, $lastDateFormatted);
		}else{
			$noOfDays = 1;
		}
		
    	return $noOfDays;
	}

	protected function _getTime() {
    	$name = '';
		$timeStr = '';
    	if ($this->has('full_day')) {
    		$InstitutionStaffAbsences = TableRegistry::get('Institution.InstitutionStaffAbsences');
    		$settingWeekdays = $InstitutionStaffAbsences->getWeekdaysBySetting();

    		if ($this->full_day) {
				$timeStr = __('Full Day');
    		} else {
				$timeStr = sprintf('%s - %s', $this->start_time, $this->end_time);
    		}
    	}
    	return $timeStr;
	}
}
