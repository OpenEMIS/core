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

class MessageComponent extends Component {
	public $components = array('Session', 'Message');
	public $alertTypes = array(
		'ok' => 'alert_ok',
		'error' => 'alert_error',
		'info' => 'alert_info',
		'warn' => 'alert_warn'
	);
	
	public $messages = array(
		'general' => array(
			'notExists' => array('type' => 'info', 'msg' => 'The Record does not exist.'),
			'notEditable' => array('type' => 'warn', 'msg' => 'This record is not editable'),
			'exists' => array('type' => 'error', 'msg' => 'The record is exists in the system.'),
			'noData' => array('type' => 'info', 'msg' => 'There are no records.'),
			'error' => array('type' => 'error', 'msg' => 'An unexpected error has been encounted. Please contact the administrator for assistance.'),
			'add' => array(
				'success' => array('type' => 'ok', 'msg' => 'The record has been added successfully.'),
				'failed' => array('type' => 'error', 'msg' => 'The record is not added due to errors encountered.')
			),
			'edit' => array(
				'success' => array('type' => 'ok', 'msg' => 'The record has been updated successfully.'),
				'failed' => array('type' => 'error', 'msg' => 'The record is not updated due to errors encountered.')
			),
			'delete' => array(
				'success' => array('type' => 'ok', 'msg' => 'The record has been deleted successfully.'),
				'failed' => array('type' => 'error', 'msg' => 'The record is not deleted due to errors encountered.'),
			),
			'duplicate' => array(
				'success' => array('type' => 'ok', 'msg' => 'The record has been duplicated successfully.'),
				'failed' => array('type' => 'error', 'msg' => 'The record is not duplicated due to errors encountered.'),
			),
			'invalidDate'  => array('type' => 'error', 'msg' => 'You have entered an invalid date.'),
			'translation' => array(
				'success' => array('type' => 'ok', 'msg' => 'The translation file has been compiled successfully.'),
			)
		),
		'security' => array(
			'login' => array(
				'timeout' => array('type' => 'info', 'msg' => 'Your session has timed out. Please login again.'),
				'fail' => array('type' => 'error', 'msg' => 'You have entered an invalid username or password.'),
				'inactive' => array('type' => 'error', 'msg' => 'You are not an authorized user.')
			)
		),
		'search' => array(
			'no_result' => array('type' => 'info', 'msg' => 'No result returned from the search.')
		),
		'FileUpload' => array(
			'success' => array(
				'singular' =>  array('type' => 'ok', 'msg' => 'The file has been uploaded.'),
				'plural' =>  array('type' => 'ok', 'msg' => 'The files has been uploaded.'),
				'delete' => array('type'=> 'error', 'msg' => 'File is deleted successfully.'),
			),
			'error' => array(
				'delete' => array('type'=> 'error', 'msg' => 'Error occurred while deleting file.'),
				'general' => array('type' => 'error', 'msg' => 'An error has occur. Please contact the system administrator.'),
				'uploadSizeError' => array('type' => 'error', 'msg' => 'Please ensure that the file is smaller than file limit.'),
				'UPLOAD_ERR_NO_FILE' => array('type' => 'info', 'msg' => 'No file was uploaded.'),
				'UPLOAD_ERR_FORM_SIZE' => array('type' => 'error', 'msg' => 'Please ensure that the file is smaller than file limit.'),
				'UPLOAD_ERR_INI_SIZE' => array('type' => 'error', 'msg' => 'Please ensure that the file is smaller than file limit.'),
				'invalidFileFormat' => array('type' => 'error', 'msg' => 'The file is not a valid format.'),
				'saving' => array('type' => 'error', 'msg' => 'The record is not added due to errors encountered.')
			)
		),
		'SchoolYear' => array(
			'noAvailableYear' => array('type' => 'warn', 'msg' => 'There are no school years available')
		),
		'InstitutionSiteProgramme' => array(
			'noData' => array('type' => 'warn', 'msg' => 'There are no programmes associated with this institution site for the selected year')
		),
		'institutionSiteAttendance' => array(
			'student' => array(
				'failed' => array(
					'class_student_not_match' => array('type' => 'error', 'msg' => 'Class and Student do not match.'),
					'class_first_date_not_match' => array('type' => 'error', 'msg' => 'First Date Absent is not within the school year.')
				)
			),
			'no_data' => array('type' => 'info', 'msg' => 'There is no data matched.'),
			'no_student' => array('type' => 'info', 'msg' => 'There is no student matched.'),
			'no_staff' => array('type' => 'info', 'msg' => 'There is no staff matched.')
		),
		'Quality' => array(
			'rubric' => array(
				'addRow' => array('type' => 'info', 'msg' => 'New row has been added at the bottom of the rubric table.'),
				'headerFirst' => array('type' => 'info', 'msg' => 'Please ensure the header is at the 1st position.')
			)
		),
		'InstitutionSite' => array(
			'student' => array(
				'student_already_exists_in_other_site' => array('type' => 'info', 'msg' => 'This student is already exists in other institution site.'),
				'notExist' => array('type' => 'error', 'msg' => 'The Student Record does not exist.')
			)
		),
		'Student' => array(
			'add' => array(
				'success' => array('type' => 'ok', 'msg' => 'New student has been created.')
			)
		),
		'Census' => array(
			'noSubjects' => array('type' => 'warn', 'msg' => 'There are no subjects configured in the system')
		),
		'CensusGraduate' => array(
			'notRequired' => array('type' => 'info', 'msg' => 'Graduates not required.')
		),
		'CensusShift' => array(
			'mismatch' => array('type' => 'warn', 'msg' => 'The total number of shift does not match the total number of classes.')
		),
		'InstitutionSiteClass' => array(
			'noData' => array('type' => 'warn', 'msg' => 'There are no classes associated with this institution site for the selected year.')
		),
		'Training' => array(
			'activate' => array(
				'success' => array('type' => 'ok', 'msg' => 'The record has been activated successfully.'),
				'failed' => array('type' => 'error', 'msg' => 'The record is not activated due to errors encountered.')
			),
			'inactivate' => array(
				'success' => array('type' => 'ok', 'msg' => 'The record has been inactivated successfully.'),
				'failed' => array('type' => 'error', 'msg' => 'The record is not inactivated due to errors encountered.')
			)
		),
		'Assessment' => array(
			'result' => array(
				'noAssessmentItem' => array('type' => 'warn', 'msg' => 'There are no assessment items.'),
				'noClass' => array('type' => 'warn', 'msg' => 'There are no classes.'),
				'noStudent' => array('type' => 'warn', 'msg' => 'There are no students.')
			)
		),
		'visualizer' => array(
			'setting' => array(
				'minScatterDimension' => array('type' => 'info', 'msg' => '2 dimensions must be selected in order to view this chart.')
			),
			'failed' => array(
				'minSelection' => array('type' => 'error', 'msg' => 'Minimum 1 selection must be made in order to proceed.')
			)
		),
		'NationalDenominators' => array(
			'finance' => array(
				'gnpEmpty' => array('type' => 'error', 'msg' => 'GNP value is required.')
			)
		)
	);
	
	public function get($code) {
		$index = explode('.', $code);
		$message = $this->messages;
		foreach($index as $i) {
			if(isset($message[$i])) {
				$message = $message[$i];
			} else {
				$message = '[Message Not Found]';
				break;
			}
		}
		return !is_array($message) ? __($message) : $message;
	}
	
	public function alert($code, $settings=array()) {
		$types = $this->alertTypes;
		$_settings = array(
			'type' => key($types),
			'types' => $types,
			'dismissOnClick' => true,
			'params' => array()
		);
		$_settings = array_merge($_settings, $settings);
		$message = $this->get($code);
		if(!array_key_exists($_settings['type'], $types)) {
			$_settings['type'] = key($types);
		} else {
			$_settings['type'] = $message['type'];
		}
		if(!empty($_settings['params'])) {
			$message = vsprintf($message, $_settings['params']);
		}
		$_settings['message'] = $message['msg'];
		$this->Session->write('_alert', $_settings);
	}
	
	public function stopAlert() {
		$this->Session->delete('_alert');
	}
}
