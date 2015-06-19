<?php
namespace Student\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class Absence extends Entity
{
	protected $_virtual = ['days', 'time'];
	
    protected function _getDays() {
    	$name = '';
    	$InstitutionSiteStudentAbsences = TableRegistry::get('Institution.InstitutionSiteStudentAbsences');
    	$settingWeekdays = $InstitutionSiteStudentAbsences->getWeekdaysBySetting();

    	if ($this->full_day_absent == 'Yes') {
			$stampFirstDateAbsent = strtotime($this->first_date_absent);
			$stampLastDateAbsent = strtotime($this->last_date_absent);
			if(!empty($this->last_date_absent) && $stampLastDateAbsent > $stampFirstDateAbsent){
				$lastDateFormatted = $InstitutionSiteStudentAbsences->formatDate($this->last_date_absent, null, false);
				$totalWeekdays = $InstitutionSiteStudentAbsences->getAbsenceDaysBySettings($this->first_date_absent, $this->last_date_absent, $settingWeekdays);
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
    		$InstitutionSiteStudentAbsences = TableRegistry::get('Institution.InstitutionSiteStudentAbsences');
    		$settingWeekdays = $InstitutionSiteStudentAbsences->getWeekdaysBySetting();

    		if ($this->full_day_absent == 'Yes') {
				$timeStr = __('full day');
    		} else {
				$timeStr = sprintf('%s - %s', $this->start_time_absent, $this->end_time_absent);
    		}
    	}
    	return $timeStr;
	}
}
