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
		if(!$this->Session->check('_alert')) {
			$this->Session->write('_alert', $_settings);
		}
	}
	
	public function getMessage($code) {
		$msgList = array();
		
		// Custom Fields
		$msgList['CUSTOM_FIELDS_NO_CONFIG'] = "There are no academic configured in the system.";
		$msgList['CUSTOM_FIELDS_NO_RECORD'] = "No records available.";
		
		// General Messages
		$msgList['ERROR_UNEXPECTED'] = "You have encountered an unexpected error. Please contact the system administrator for assistance.";
		$msgList['CREATE_SUCCESS'] = "The record has been created successfully.";
		$msgList['UPDATE_SUCCESS'] = "Records have been added/updated successfully.";
		$msgList['DELETE_SUCCESS'] = "Record has been deleted.";
		$msgList['SAVE_SUCCESS'] = "Your data has been saved successfully.";
		$msgList['INVALID_ID_NO'] = "You have entered an invalid Identification No.";
		$msgList['CONFIG_SAVED'] = "Your configurations have been saved.";
		$msgList['NO_HISTORY'] = "No history found.";
		$msgList['NO_RECORD'] = "There are no records.";
		
		// Login Messages
		$msgList['LOGIN_TIMEOUT'] = "Your session is timed out. Please login again.";
		$msgList['LOGIN_INVALID'] = "You have entered an invalid username or password.";
		$msgList['LOGIN_USER_INACTIVE'] = "You are not an authorized user.";
		
		// School Year Messages
		$msgList['SCHOOL_YEAR_EMPTY_LIST'] = "There are no school years configured in the system.";
		
		// Census Messages
		$msgList['CENSUS_NO_PROG'] = "There are no programmes associated with this institution site for the selected year.";
		$msgList['CENSUS_UPDATED'] = "The census data has been updated successfully.";
		$msgList['CENSUS_GRADUATE_NOT_REQUIRED'] = "Graduates not required.";
		$msgList['CENSUS_NO_SUBJECTS'] = "There are no subjects configured in the system";
		
		// Education Messages
		$msgList['EDUCATION_NO_LEVEL'] = "There is no active education level in this Education System.";
		$msgList['EDUCATION_NO_SYSTEM'] = "There is no active Education System.";
		$msgList['EDUCATION_PROGRAMME_ADDED'] = "Education Programme has been added successfully. Please add Education Grades to this Programme.";
		$msgList['EDUCATION_INACTIVE'] = "No Education Programme is available, please check your Education Structure.";
		
		// Assessment Messages
		$msgList['ASSESSMENT_NO_PROGRAMME'] = "There are no active programmes in the system.";
		$msgList['ASSESSMENT_NO_ASSESSMENT'] = "There are no assessments on the selected programme.";
		$msgList['ASSESSMENT_RESULT_INACTIVE'] = "You cannot edit the results because this assessment is not active.";
		
		// Access Control Messages
		$msgList['SECURITY_NO_ACCESS'] = "You do not have access to this functionality.";
		$msgList['SECURITY_GRP_NO_NAME'] = "Please enter a valid name.";
		$msgList['SECURITY_GRP_NAME_EXISTS'] = "The name is already in use.";
		$msgList['SECURITY_GRP_USER_ADD'] = "The user has been added to the role.";
		
		// Institution Sites
		$msgList['NO_SITES'] = "No Institution Sites";
		
		// Institution Site Classes
		$msgList['SITE_CLASS_EMPTY_NAME'] = "Please enter a valid class name.";
		$msgList['SITE_CLASS_DUPLICATE_NAME'] = "The name is already existed.";
		$msgList['SITE_CLASS_NO_GRADES'] = "Please add a grade to this class.";
		$msgList['SITE_CLASS_NO_CLASSES'] = "There are no classes for the selected year.";
		
		$msgList['NO_EMPLOYMENT'] = "No Employment found.";
		$msgList['NO_CLASSES'] = "No Classes found.";
		
		// Students
		$msgList['STUDENT_SEARCH_NO_RESULT'] = "No Student found.";
		$msgList['STUDENT_ALREADY_ADDED'] = "is already exists in this institution site.";
		$msgList['STUDENT_NO_BEHAVIOUR_DATA'] = "No behaviour found.";
		$msgList['SITE_STUDENT_BEHAVIOUR_EMPTY_TITLE'] = "Please enter a valid title.";
		$msgList['CENSUS_UPDATED'] = "The Student attendance data has been updated successfully.";
		
		// Teachers
		$msgList['TEACHER_NOT_FOUND'] = "No Teacher found.";
		
		// Staff
		$msgList['STAFF_NOT_FOUND'] = "No Staff found.";
		
		// Reports
		$msgList['REPORT_NO_FILES'] = "There are no available files found for this report.";
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
	
	public function formatGender($value) {
		return ($value == 'F') ? __('Female') : __('Male');
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