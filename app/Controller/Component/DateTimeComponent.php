<?php
class DateTimeComponent extends Component {

	/**
	 * Getting Date Format from Config
	 * @return string [date format]
	 */
	public static function getConfigDateFormat() {
		$configItem = ClassRegistry::init('ConfigItem');
		return $configItem->getValue('date_format');
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
	
}