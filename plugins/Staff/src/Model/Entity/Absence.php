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

		$stampFirstDateAbsent = strtotime($this->start_date);
		$stampLastDateAbsent = strtotime($this->end_date);
		if(!empty($this->end_date) && $stampLastDateAbsent > $stampFirstDateAbsent){
			$lastDateFormatted = $InstitutionSiteStaffAbsences->formatDate($this->end_date, null, false);
			$totalWeekdays = $InstitutionSiteStaffAbsences->getAbsenceDaysBySettings($this->start_date, $this->end_date, $settingWeekdays);
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
    		$InstitutionSiteStaffAbsences = TableRegistry::get('Institution.InstitutionSiteStaffAbsences');
    		$settingWeekdays = $InstitutionSiteStaffAbsences->getWeekdaysBySetting();

    		if ($this->full_day) {
				$timeStr = __('full day');
    		} else {
				$timeStr = sprintf('%s - %s', $this->start_time, $this->end_time);
    		}
    	}
    	return $timeStr;
	}
}
