<?php
class UtilityComponent extends Component {
	public $components = array('Session');
	
	//called after Controller::beforeRender()
	public function beforeRender(Controller $controller) {
	}
	
	public function alert($message, $settings=array()) {
		$types = array('ok', 'error', 'info', 'warn');
		$_settings = array(
			'type' => 'ok',
			'dismissOnClick' => true,
		);
		$_settings = array_merge($_settings, $settings);
		if(!in_array($_settings['type'], $types)) {
			$_settings['type'] =  $types[0];
		}
		$_settings['message'] = __($message);
		$this->Session->write('_alert', $_settings);
	}
	
	public function getMessage($code) {
		$msgList = array();
		// General Messages
		$msgList['ERROR_UNEXPECTED'] = "You have encountered an unexpected error. Please contact the system administrator for assistance.";
		$msgList['UPDATE_SUCCESS'] = "Records have been added/updated successfully.";
		
		// Login Messages
		$msgList['LOGIN_TIMEOUT'] = "Your session is timed out. Please login again.";
		$msgList['LOGIN_INVALID'] = "You have entered an invalid username or password.";
		$msgList['LOGIN_USER_INACTIVE'] = "You are not an authorized user.";
		
		// Census Messages
		$msgList['CENSUS_NO_PROG'] = "There are no programmes associated with this institution site.";
		$msgList['CENSUS_UPDATED'] = "The census data has been updated successfully.";
		$msgList['CENSUS_GRADUATE_NOT_REQUIRED'] = "Graduates not required.";
		
		// Education Messages
		$msgList['EDUCATION_NO_LEVEL'] = "There is no active education level in this Education System.";
		$msgList['EDUCATION_NO_SYSTEM'] = "There is no active Education System.";
		$msgList['EDUCATION_PROGRAMME_ADDED'] = "Education Programme has been added successfully. Please add Education Grades to this Programme.";
		
		$msg = isset($msgList[$code]) ? $msgList[$code] : 'Message Not Found';
		return __($msg);
	}
	
	public function ajaxReturnCodes($code=null) {
		$return = array('error' => -1, 'success' => 0, 'alert' => 1);
		
		if(is_null($code))
			return $return;
		else 
			return $return[$code];
	}
	
	public function getAjaxErrorHandler() {
		$handler = array(
			0 => array(
				'title' => __('Host Unreachable'),
				'content' => __('Host is unreachable, please check your internet connection.')
			),
			403 => array(
				'title' => __('Session Timed Out')
			),
			404 => array(
				'title' => __('Page not found'),
				'content' => __('The requested page cannot be found.').' <br /><br /> '.__('Please contact the administrator for assistance')
			),
			500 => array(
				'title' => __('Internal Server Error'),
				'content' => __('An unexpected error has occurred.').' <br /><br /> '.__('Please contact the administrator for assistance')
			),
			'parsererror' => array(
				'title' => __('JSON parse failed'),
				'content' => __('Invalid JSON data.')
			),
			'timeout' => array(
				'title' => __('Request Timeout'),
				'content' => __('Your request has been timed out. Please try again.')
			),
			'abort' => array(
				'title' => __('Request Aborted'),
				'content' => __('Your request has been aborted.')
			),
			'unknown' => array(
				'title' => __('Unexpected Error'),
				'content' => __('An unexpected error has occurred.').' <br /><br /> '. __('Please contact the administrator for assistance')
			)
		);
		
		return $handler;
	}
	
	public function setAjaxResult($code, &$result) {
		$result['type'] = $this->ajaxReturnCodes($code);
	}
	
	public function getAlertType($code) {
		$types = array(
			'alert.error' => 0,
			'alert.ok' => 1,
			'alert.info' => 2,
			'alert.warn' => 3
		);
		
		return $types[$code];
	}
	
	public function getFileExtensionList() {
		$ext = array(
			'jpg' => __('Image'),
			'jpeg' => __('Image'),
			'png' => __('Image'),
			'gif' => __('Image'),
			'docx' => __('Document'),
			'doc' => __('Document'),
			'xls' => __('Excel'),
			'xlsx' => __('Excel'),
			'ppt' => __('Powerpoint'),
			'pptx' => __('Powerpoint')
		);
		return $ext;
	}
	
	public function getFirstError($action, $model, $user) {
		$errorMsg = '';
		$errors = $model->invalidFields();
		foreach($errors as $key => $err) {
			$this->log('UserId-' . $user['id'] . ' | ' . $action . ' | ' . $err[0], 'debug');
			$errorMsg = $err[0];
			break;
		}
		return $errorMsg;
	}
	
	public function formatResult($list) {

		$result = array();
		foreach($list as $record) {
			$data = array();
			foreach($record as $model => $val) {
				$data = array_merge($data, $val);
			}
			$result[] = $data;
		}
		return $result;
	}
	
	public function unshiftArray(&$origArray, $newArray) {
		$tmpArray = array();
        foreach($newArray as $key => $val){
            $tmpArray[$key] = $val;
        }
        foreach($origArray as $key => $val){
            $tmpArray[$key] = $val;
        }
        $origArray = $tmpArray;
	}
	
	//
	// Param: $array = array(
	//			array('All Sex', 'Male', 'Female'),
	//			array('All Locality', 'Urban', 'Rural'),
	//			array('All Grades', 'Grade 1', 'Grade 2')
	//		  )
	//
	public function arrayPermutate($array) {
		$permutations = array();
		$iter = 0;
		
		while(1) {
			$num = $iter++;
			$pick = array();
			
			for($i=0; $i<sizeof($array); $i++) {
				$groupSize = sizeof($array[$i]);
				$r = $num % $groupSize;
				$num = ($num - $r) / $groupSize;
				array_push($pick, $array[$i][$r]);
			}
			if($num > 0) break;
			
			array_push($permutations, $pick);
		}
		return $permutations;
	}
}
?>
