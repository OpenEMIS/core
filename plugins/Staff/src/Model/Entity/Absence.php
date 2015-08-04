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

    	if ($this->full_day) {//Need to remove _absence
			$stampFirstDateAbsent = strtotime($this->first_date);
			$stampLastDateAbsent = strtotime($this->last_date);
			if(!empty($this->last_date_absent) && $stampLastDateAbsent > $stampFirstDateAbsent){
				$lastDateFormatted = $InstitutionSiteStaffAbsences->formatDate($this->last_date, null, false);//Need to remove _absence
				$totalWeekdays = $InstitutionSiteStaffAbsences->getAbsenceDaysBySettings($this->start_date, $this->end_date, $settingWeekdays);//Need to remove _absence
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

    	if ($this->has('full_day')) {//Need to remove _absence
    		$InstitutionSiteStaffAbsences = TableRegistry::get('Institution.InstitutionSiteStaffAbsences');
    		$settingWeekdays = $InstitutionSiteStaffAbsences->getWeekdaysBySetting();

    		if ($this->full_day) {//Need to remove _absence
				$timeStr = __('full day');
    		} else {
				$timeStr = sprintf('%s - %s', $this->start_time, $this->end_time);//Need to remove _absence
    		}
    	}
    	return $timeStr;
	}
}
