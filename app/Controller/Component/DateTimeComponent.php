<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

class DateTimeComponent extends Component {

	/**
	 * Getting Date Format from Config
	 * @return string [date format]
	 */
	public static function getConfigDateFormat() {
		$format = '';
		if(isset($_SESSION['Config.DateFormat'])) {
			$format = $_SESSION['Config.DateFormat'];
		} else {
			$configItem = ClassRegistry::init('ConfigItem');
			$format = $configItem->getValue('date_format');
			$_SESSION['Config.DateFormat'] = $format;
		}
		return $format;
	}
        
        public function formatDateByConfig($date) {
		$format = $this->getConfigDateFormat();
		$output = null;
		if($date == '0000-00-00' || $date == ''){ 
			$output = '';
		}else{
			$date = new DateTime($date);
			$output = $date->format($format);
		}
		return $output;
	}
	
	public static function getConfigLowestYear() {
		$configItem = ClassRegistry::init('ConfigItem');
		return $configItem->getValue('lowest_year');
	}

	/**
     * Simple date check to validate if date is not 0000-00-00
     * @param  string  $date [date in Y-m-d]
     * @return boolean       [description]
     */
    public function isValidDate($date) {
        return date('Y-m-d', strtotime($date)) === $date;
    }
	
	/**
     * Simple datetime check to validate if datetime is not 0000-00-00 00:00:00
     * @param  string  $date [date in Y-m-d]
     * @return boolean       [description]
     */
    public function isValidDateTime($date) {
        return date('Y-m-d H:i:s', strtotime($date)) === $date;
    }
	
	public static function generateMonth() {
		$mth = array();
        $mth['01'] = __('January');
		$mth['02'] = __('February');
		$mth['03'] = __('March');
		$mth['04'] = __('April');
		$mth['05'] = __('May');
		$mth['06'] = __('June');
		$mth['07'] = __('July');
		$mth['08'] = __('August');
		$mth['09'] = __('September');
		$mth['10'] = __('October');
		$mth['11'] = __('November');
		$mth['12'] = __('December');
        return $mth;
    }
    
    public static function generateDay($year=0, $month=0) {
		$day = array();
		$noOfDays = 31;
		
		if($year!=0 && $month!=0) {
			$noOfDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		}
		
        for($i=1; $i<=$noOfDays; $i++) {
            $day[substr("0".$i,-2)] = $i;
        }
        return $day;
    }

    public static function generateYear($param = array()) {
		if(empty($param)){
			$start = DateTimeComponent::getConfigLowestYear();
			$end = date('Y')+1;
		}else{
			if(sizeof($param) == 0 || !isset($param['offset'])) {
				$param['offset'] = 100;
			}
			$start = date('Y') - $param['offset'];
			$end = date('Y');
			if(isset($param['low']) && isset($param['high']) && $param['low'] > 0 && $param['high'] > 0) {
				$start = $param['low'];
				$end = $param['high'];
			}
		}
		
		$year = array();
        for($i=$start; $i<=$end; $i++) {
            $year[strval($i)] = $i;
        }
        return $year;
    }

    public static function dateAsSql($timeStamp, $timezone = NULL){
        $date = new DateTime('@'.$timeStamp);
        $sqlDateTimeFormat = 'Y-m-d H:i:s';
        if(!is_null($timezone)) $date->setTimezone(new DateTimeZone($timezone));
        return $date->format($sqlDateTimeFormat);
    }
	
	public function yearOptionsByConfig(){
		$configItem = ClassRegistry::init('ConfigItem');
		$lowestYear = $configItem->getValue('lowest_year');
		$currentYear = date("Y");
		
		$options = array();
		for($i=$currentYear; $i >= $lowestYear; $i--){
			$options[$i] = $i;
		}
		
		return $options;
	}
	
}
