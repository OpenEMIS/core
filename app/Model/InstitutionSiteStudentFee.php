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

App::uses('AppModel', 'Model');

class InstitutionSiteStudentFee extends AppModel {
	public $useTable = 'student_fees';
	
	public $actsAs = array(
		'ControllerAction2',
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		),
		'DatePicker' => array('payment_date')
	);
	
	public $belongsTo = array(
		'InstitutionSiteFee',
		'Students.Student',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'modified_user_id',
			'type' => 'LEFT'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'created_user_id',
			'type' => 'LEFT'
		)
	);
	
	public $SchoolYear;
	
	public $validate = array(
		'amount' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Amount'
			)
		)
	);
	
	public function beforeAction() {
		parent::beforeAction();
		$contentHeader = 'Student Fees';
		$this->Navigation->addCrumb($contentHeader);
		$this->SchoolYear = $this->InstitutionSiteFee->SchoolYear;
		
		$ConfigItem = ClassRegistry::init('ConfigItem');
	   	$currency = $ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'currency'));
		$this->setVar('currency', $currency);
		
		$this->fields['student_id']['type'] = 'hidden';
		$this->fields['institution_site_fee_id']['type'] = 'hidden';
		$this->setFieldOrder('amount', 3);
		
		if ($this->action == 'add' || $this->action == 'remove') {
			$studentId = $this->controller->params->pass[1];
			$institutionSiteFeeId =  $this->controller->params->pass[2];
			$this->fields['student_id']['value'] = $studentId;
			$this->fields['institution_site_fee_id']['value'] = $institutionSiteFeeId;
			$this->setVar('params', array('back' => 'viewPayments', $studentId, $institutionSiteFeeId));
		}
		$this->setVar('contentHeader', __($contentHeader));
	}
	
	public function afterAction() {
		if ($this->action == 'view') {
			$studentId = $this->controller->viewVars['data'][$this->alias]['student_id'];
			$institutionSiteFeeId = $this->controller->viewVars['data'][$this->alias]['institution_site_fee_id'];
			$this->setVar('params', array('back' => 'viewPayments', $studentId, $institutionSiteFeeId));
		}
		parent::afterAction();
	}
	
	public function index($selectedYear=0, $selectedGrade=0) {
		$params = $this->controller->params;
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$yearOptions = $this->SchoolYear->find('list', array('conditions' => array('available' => 1), 'order' => array('order')));
		
		if (empty($selectedYear)) {
			$selectedYear = key($yearOptions);
		}
		
		$gradeOptions = $this->InstitutionSiteFee->EducationGrade->getGradeOptionsByInstitutionAndSchoolYear($institutionSiteId, $selectedYear, true);
		if (empty($gradeOptions)) {
			$gradeOptions = array('0' => __('No Data'));
		}
		if (empty($selectedGrade)) {
			$selectedGrade = key($gradeOptions);
		}
		
		$Student = ClassRegistry::init('InstitutionSiteClassStudent');
		$data = $Student->find('all', array(
			'fields' => array(
				'InstitutionSiteFee.id',
				'InstitutionSiteFee.total',
				'SUM(StudentFee.amount) AS paid'
			),
			'contain' => array(
				'InstitutionSiteClass',
				'Student' => array('fields' => array('id', 'identification_no', 'first_name', 'last_name'))
			),
			'joins' => array(
				array(
					'table' => 'institution_site_fees', 'alias' => 'InstitutionSiteFee',
					'conditions' => array(
						'InstitutionSiteFee.school_year_id = ' . $selectedYear,
						'InstitutionSiteFee.institution_site_id = ' . $institutionSiteId,
						'InstitutionSiteFee.education_grade_id = InstitutionSiteClassStudent.education_grade_id'
					)
				),
				array(
					'table' => 'student_fees', 'alias' => 'StudentFee',
					'type' => 'LEFT',
					'conditions' => array(
						'StudentFee.institution_site_fee_id = InstitutionSiteFee.id',
						'StudentFee.student_id = InstitutionSiteClassStudent.student_id'
					)
				)
			),
			'conditions' => array(
				'InstitutionSiteClass.institution_site_id' => $institutionSiteId,
				'InstitutionSiteClass.school_year_id' => $selectedYear,
				'InstitutionSiteClassStudent.education_grade_id' => $selectedGrade
			),
			'group' => array('InstitutionSiteClassStudent.student_id', 'InstitutionSiteClassStudent.education_grade_id'),
			'order' => array('Student.first_name')
		));
		
		if (empty($data)) {
			$this->Message->alert('general.noData');
		}

		$this->setVar(compact('data', 'selectedYear', 'yearOptions', 'selectedGrade', 'gradeOptions'));
	}
	
	public function viewPayments($studentId, $institutionSiteFeeId) {
		$alias = $this->alias;
		$this->Student->contain();
		$student = $this->Student->findById($studentId);
		$this->InstitutionSiteFee->contain(array(
			'SchoolYear' => array('fields' => array('name')),
			'EducationGrade' => array(
				'fields' => array('name'),
				'EducationProgramme' => array('fields' => array('name'))
			),
			'InstitutionSiteFeeType' => array(
				'FeeType' => array('fields' => array('name')),
				'order' => array('amount DESC')
			)
		));
		$fees = $this->InstitutionSiteFee->findById($institutionSiteFeeId);
		
		$payments = $this->find('all', array(
			'contain' => array('CreatedUser'),
			'conditions' => array(
				"$alias.student_id" => $studentId,
				"$alias.institution_site_fee_id" => $institutionSiteFeeId
			),
			'order' => array("$alias.payment_date")
		));
		
		$fields = array();
		$fields['year']['visible'] = true;
		$fields['programme']['visible'] = true;
		$fields['grade']['visible'] = true;
		$fields['openemisId']['labelKey'] = 'general';
		$fields['openemisId']['visible'] = true;
		$fields['name']['visible'] = true;
		$fields['outstanding']['visible'] = true;
		$fields['fee_types'] = array(
			'type' => 'element',
			'element' => '../InstitutionSites/InstitutionSiteStudentFee/fee_types',
			'class' => 'col-md-9',
			'visible' => true
		);
		$fields['payments'] = array(
			'type' => 'element',
			'element' => '../InstitutionSites/InstitutionSiteStudentFee/payments',
			'class' => 'col-md-9',
			'visible' => true
		);
		
		$outstanding = $fees['InstitutionSiteFee']['total'];
		foreach ($payments as $payment) {
			$outstanding -= $payment[$alias]['amount'];
		}
		
		$data = array();
		$data[$alias]['year'] = $fees['SchoolYear']['name'];
		$data[$alias]['programme'] = $fees['EducationGrade']['EducationProgramme']['name'];
		$data[$alias]['grade'] = $fees['EducationGrade']['name'];
		$data[$alias]['student_id'] = $student['Student']['id'];
		$data[$alias]['openemisId'] = $student['Student']['identification_no'];
		$data[$alias]['name'] = trim($student['Student']['first_name'] . ' ' . $student['Student']['last_name']);
		$data[$alias]['institution_site_fee_id'] = $fees['InstitutionSiteFee']['id'];
		$data[$alias]['total_fee'] = $fees['InstitutionSiteFee']['total'];
		$data[$alias]['outstanding'] = number_format($outstanding, 2);
		$data['InstitutionSiteFeeType'] = $fees['InstitutionSiteFeeType'];
		$data[$alias]['payments'] = $payments;
		
		$params = array($fees['InstitutionSiteFee']['school_year_id'], $fees['InstitutionSiteFee']['education_grade_id']);
		$this->fields = $fields;
		
		$this->setVar(compact('data', 'fees', 'params'));
	}

	public function reportsGetHeader($args) {
		$ConfigItem = ClassRegistry::init('ConfigItem');
   		$currency = $ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'currency'));

		$header = array(
			__('School Year'),
			__('Education Programme'),
			__('Education Grade'),
			__('OpenEMIS ID'),
			__('First Name'),
			__('Middle Name'),
			__('Last Name'),
			__('Preferred Name'),
			__('Total Paid') . ' ('.$currency.')',
			__('Total Outstanding') . ' ('.$currency.')'
		);

		return $header;
	}
	
	public function reportsGetData($args) {
		$institutionSiteId = $args[0];
		$index = $args[1];

		if ($index == 1) {
			$options = array();
			$options['recursive'] = -1;
			$options['fields'] = array(
				'SchoolYear.name', 
				'EducationProgramme.name', 
				'EducationGrade.name', 
				'Student.identification_no', 
				'Student.first_name',
				'Student.middle_name',
				'Student.last_name',
				'Student.preferred_name',
				'InstitutionSiteStudentFee.total_paid',
				'InstitutionSiteStudentFee.total_outstanding',
				'InstitutionSiteFee.total_fee'
			);

			$options['order'] = array('InstitutionSiteProgramme.school_year_id', 'EducationGrade.education_programme_id', 'EducationGrade.id', 'Student.first_name', 'Student.last_name');
			$options['conditions'] = array('InstitutionSiteProgramme.institution_site_id' => $institutionSiteId, 'EducationGrade.visible'=>1);
			$options['joins'] = array(
					array(
						'table' => 'education_grades',
						'alias' => 'EducationGrade',
						'conditions' => array('EducationGrade.id = InstitutionSiteFee.education_grade_id')
					),
					array(
						'table' => 'education_programmes',
						'alias' => 'EducationProgramme',
						'conditions' => array('EducationProgramme.id = EducationGrade.education_programme_id')
					),
					array(
						'table' => 'institution_site_programmes',
						'alias' => 'InstitutionSiteProgramme',
						'conditions' => array(
							'InstitutionSiteProgramme.institution_site_id = InstitutionSiteFee.institution_site_id',
							'InstitutionSiteProgramme.education_programme_id = EducationProgramme.id',
							'InstitutionSiteProgramme.school_year_id = InstitutionSiteFee.school_year_id',
						)
					),
					array(
						'table' => 'school_years',
						'alias' => 'SchoolYear',
						'conditions' => array(
							'InstitutionSiteProgramme.school_year_id = SchoolYear.id'
						)
					),
					array(
						'table' => 'institution_site_students',
						'alias' => 'InstitutionSiteStudent',
						'conditions' => array('InstitutionSiteStudent.institution_site_programme_id = InstitutionSiteProgramme.id')
					),
					array(
						'table' => 'students',
						'alias' => 'Student',
						'conditions' => array('Student.id = InstitutionSiteStudent.student_id')
					),
					array(
						'type' => 'LEFT',
						'table' => 'institution_site_student_fees',
						'alias' => 'InstitutionSiteStudentFee',
						'conditions' => array(
							'InstitutionSiteStudentFee.student_id = Student.id', 
							'InstitutionSiteStudentFee.institution_site_fee_id = InstitutionSiteFee.id'
						)
					)
				);

			$data = $this->InstitutionSiteFee->find('all', $options);
			
			$newData = array();
			foreach($data AS $row){
				$tempRow = array();
				
				$schoolYear = $row['SchoolYear'];
				$educationProgramme = $row['EducationProgramme'];
				$educationGrade = $row['EducationGrade'];
				$student = $row['Student'];

				$institutionSiteStudentFee = (isset($row['InstitutionSiteStudentFee']) ? $row['InstitutionSiteStudentFee'] : null);
			
				$tempRow[] = $schoolYear['name'];
				$tempRow[] = $educationProgramme['name'];
				$tempRow[] = $educationGrade['name'];

				$tempRow[] = $student['identification_no'];
				$tempRow[] = $student['first_name'];
				$tempRow[] = $student['middle_name'];
				$tempRow[] = $student['last_name'];
				$tempRow[] = $student['preferred_name'];
				
				$tempRow[] = isset($institutionSiteStudentFee['total_paid']) ? number_format($institutionSiteStudentFee['total_paid'], 2) : number_format(0, 2);
				$tempRow[] = isset($institutionSiteStudentFee['total_outstanding']) ? number_format($institutionSiteStudentFee['total_outstanding'], 2) : number_format($row['InstitutionSiteFee']['total_fee'], 2);

				$newData[] = $tempRow;
			}

			return $newData;
		}
	}

	public function reportsGetFileName($args) {
		$index = $args[1];
		return 'Report_Finance_Student';
	}
	

}
