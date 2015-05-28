<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class AbsencesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_student_absences');
		parent::initialize($config);
	}
}


// /*
// @OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

// OpenEMIS
// Open Education Management Information System

// Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
// it under the terms of the GNU General Public License as published by the Free Software Foundation
// , either version 3 of the License, or any later version.  This program is distributed in the hope 
// that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
// or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
// have received a copy of the GNU General Public License along with this program.  If not, see 
// <http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
// */

// App::uses('AppModel', 'Model');

// class Absence extends AppModel {
// 	public $useTable = 'institution_site_student_absences';
	
// 	public $actsAs = array(
// 		'Excel' => array('header' => array('Student' => array('SecurityUser.openemis_no', 'SecurityUser.first_name', 'SecurityUser.last_name'))),
// 		'DatePicker' => array(
// 			'first_date_absent', 'last_date_absent'
// 		),
// 		'TimePicker' => array('start_time_absent' => array('format' => 'h:i a'), 'end_time_absent' => array('format' => 'h:i a')),
// 		'ControllerAction2'
// 	);
	
// 	public $belongsTo = array(
// 		'Students.Student',
// 		'InstitutionSiteSection',
// 		'StudentAbsenceReason' => array(
// 			'className' => 'FieldOptionValue',
// 			'foreignKey' => 'student_absence_reason_id'
// 		),
// 		'ModifiedUser' => array(
// 			'className' => 'SecurityUser',
// 			'fields' => array('first_name', 'last_name'),
// 			'foreignKey' => 'modified_user_id',
// 			'type' => 'LEFT'
// 		),
// 		'CreatedUser' => array(
// 			'className' => 'SecurityUser',
// 			'fields' => array('first_name', 'last_name'),
// 			'foreignKey' => 'created_user_id',
// 			'type' => 'LEFT'
// 		)
// 	);

// 	public function index($academicPeriodId=0, $monthId=0) {
// 		if (!$this->Session->check('Student.id')) {
// 			return $this->redirect(array('plugins' => 'Students', 'controller' => 'Students', 'action' => 'index'));
// 		}
// 		$studentId = $this->Session->read('Student.id');
		
// 		$this->Navigation->addCrumb('Absence');
// 		$header = __('Absence');
		
// 		$academicPeriodList = ClassRegistry::init('AcademicPeriod')->getAcademicPeriodList();
		
// 		if ($academicPeriodId != 0) {
// 			if (!array_key_exists($academicPeriodId, $academicPeriodList)) {
// 				$academicPeriodId = key($academicPeriodList);
// 			}
// 		} else {
// 			$academicPeriodId = key($academicPeriodList);
// 		}
		
// 		$monthOptions = $this->controller->generateMonthOptions();
// 		$currentMonthId = $this->controller->getCurrentMonthId();
// 		if ($monthId != 0) {
// 			if (!array_key_exists($monthId, $monthOptions)) {
// 				$monthId = $currentMonthId;
// 			}
// 		} else {
// 			$monthId = $currentMonthId;
// 		}
		
// 		$absenceData = ClassRegistry::init('InstitutionSiteStudentAbsence')->getStudentAbsenceDataByMonth($studentId, $academicPeriodId, $monthId);
// 		$data = $absenceData;
		
// 		if (empty($data)) {
// 			$this->Message->alert('general.noData');
// 		}
		
// 		$settingWeekdays = $this->controller->getWeekdaysBySetting();

// 		$this->setVar(compact('header', 'data', 'academicPeriodList', 'academicPeriodId', 'monthOptions', 'monthId', 'settingWeekdays'));
// 	}
// }
