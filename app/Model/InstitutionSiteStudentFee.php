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

	public $actsAs = array(
		'ControllerAction'
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

	public $hasMany = array(
		'InstitutionSiteStudentFeeTransaction' => array(
			'dependent' => true
		)
	);


	public $validate = array(
		'total_paid' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a Paid Amount'
			)
		)
	);

	public $headerDefault = 'Student Fees';

	public $_action = 'studentFee';
	
	public function beforeAction($controller, $action) {
		parent::beforeAction($controller, $action);
		$controller->set('_action', $this->_action);
		$controller->set('selectedAction', $this->_action . 'View');
	}

	public function studentFee($controller, $params) {
		$controller->Navigation->addCrumb($this->headerDefault);

		$schoolYear = ClassRegistry::init('SchoolYear');
		$yearOptions = $schoolYear->institutionProgrammeYearList($controller->institutionSiteId);
		$selectedYear = (isset($params->pass[0]) ? $params->pass[0] : key($yearOptions));
		$data = $this->getListOfFees($selectedYear, $controller->institutionSiteId);

		$institutionSiteId = $controller->Session->read('InstitutionSite.id');
		$InstitutionSiteProgramme = ClassRegistry::init('InstitutionSiteProgramme');

		if(!empty($selectedYear)){
			$controller->Session->write('InstitutionSiteStudentFee.selected_year', $selectedYear);
		}
		$programmes = $InstitutionSiteProgramme->getSiteProgrammes($institutionSiteId, $selectedYear);

		$EducationGrade = ClassRegistry::init('EducationGrade');

		foreach($programmes as $key => $programme){
			$programmes[$key]['education_grades'] = $EducationGrade->find('list', array('recursive'=>-1, 'fields'=>array('id', 'name'), 'conditions'=>array('EducationGrade.education_programme_id'=>$programme['education_programme_id'])));
		}
		$modelName = $this->name;
		$controller->set('subheader', $this->headerDefault);
		$controller->set(compact('data', 'selectedYear', 'programmes', 'yearOptions', 'modelName'));
	}

	public function studentFeeView($controller, $params) {
	
		$studentId = empty($params['pass'][0])? 0:$params['pass'][0];
		$feeId = empty($params['pass'][1])? 0:$params['pass'][1];

		
		$data = $this->InstitutionSiteFee->find('first', array('conditions'=>array('InstitutionSiteFee.id'=>$feeId)));
		$studentData = $this->Student->find('first', array('recursive'=>-1, 'conditions'=>array('Student.id'=>$studentId)));
		$studentFeeData = $this->find('first', array('recursive'=>-1, 'conditions'=>array('InstitutionSiteStudentFee.student_id'=>$studentId, 'InstitutionSiteStudentFee.institution_site_fee_id'=>$feeId)));
		if(empty($data) || empty($studentData)){
			$controller->Session->delete('InstitutionSiteStudentFee.studentId');
			$controller->Session->delete('InstitutionSiteStudentFee.studentName');
			$controller->Session->delete('InstitutionSiteStudentFee.feeId');
			$controller->Session->delete('InstitutionSiteStudentFee.id');
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action'=>'studentFee'));
		}else{
			$controller->Session->write('InstitutionSiteStudentFee.studentId', $studentId);
			$controller->Session->write('InstitutionSiteStudentFee.studentName', $studentData['Student']['first_name'] . ' ' . $studentData['Student']['last_name']);
			$controller->Session->write('InstitutionSiteStudentFee.feeId', $feeId);
			$controller->Session->write('InstitutionSiteStudentFee.id', (isset($studentFeeData['InstitutionSiteStudentFee']['id']) ? $studentFeeData['InstitutionSiteStudentFee']['id'] : 0));
		}

		$institutionSiteStudentFeeTransactions = $this->getListOfTransactions($controller);

		$this->addStudentBreadCrumb($controller);
		$controller->Navigation->addCrumb($this->headerDefault . ' Details');

		$data = array_merge($data, $studentData);

		$grades = $this->InstitutionSiteFee->EducationGrade->find('first', array('conditions'=>array('EducationGrade.id'=>$data['InstitutionSiteFee']['education_grade_id'])));
	
		$data['InstitutionSiteStudentFee']['programme'] = $grades['EducationProgramme']['name'];
		$data['InstitutionSiteStudentFee']['education_grade'] = $grades['EducationGrade']['name'];
		$data['InstitutionSiteStudentFee']['identification_no'] = $data['Student']['identification_no'];
		$data['InstitutionSiteStudentFee']['name'] = $data['Student']['first_name'] . ' ' . $data['Student']['last_name'];

		$controller->set('subheader', $this->headerDefault . ' Details');
		$controller->set(compact('data', 'studentFeeData', 'institutionSiteFeeTypes', 'institutionSiteStudentFeeTransactions'));
	}

	private function getListOfTransactions($controller){
		$id = $controller->Session->read('InstitutionSiteStudentFee.id');
		$institutionSiteStudentFeeTransactions = array();
		if(!empty($id)){
			$institutionSiteStudentFeeTransactions = $this->InstitutionSiteStudentFeeTransaction->find('all', array('conditions'=>array('InstitutionSiteStudentFeeTransaction.institution_site_student_fee_id'=>$id), 'order'=>array('InstitutionSiteStudentFeeTransaction.paid_date ASC')));
		}
		
		return $institutionSiteStudentFeeTransactions;
	}

	private function addStudentBreadCrumb($controller){
		if(!$controller->Session->check('InstitutionSiteStudentFee.studentId') || !$controller->Session->check('InstitutionSiteStudentFee.feeId') || 
			!$controller->Session->check('InstitutionSiteStudentFee.studentName')
		){
			return $controller->redirect(array('action'=>'studentFee'));
		}

		$studentId = $controller->Session->read('InstitutionSiteStudentFee.studentId');
		$studentName = $controller->Session->read('InstitutionSiteStudentFee.studentName');

		$link = $controller->Navigation->createLink($studentName, 'Students', 'view', '', array($studentId));
		unset($link['title']);
		unset($link['pattern']);
		$controller->Navigation->addCrumb($studentName, $link);
	}

	public function getDisplayFields($controller, $model) {
        $fields = array(
            'model' => $model,
            'fields' => array(
                array('field' => 'id', 'type' => 'hidden'),
             	array('field' => 'paid_date',  'labelKey' => 'general.date'),
                array('field' => 'paid',  'labelKey' => 'FinanceFee.amount'),
				array('field' => 'comments'),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false, 'labelKey' => 'general.modified_by'),
                array('field' => 'modified', 'edit' => false),
                array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false, 'labelKey' => 'general.created_by'),
                array('field' => 'created', 'edit' => false)
                )
			);

		 return $fields;
    }

	public function studentFeeAddTransaction($controller, $params) {
		$this->addStudentBreadCrumb($controller);
		$controller->Navigation->addCrumb('Add ' . $this->headerDefault . ' Details');
		$controller->set('subheader', $this->headerDefault);
		$this->setup_add_edit_form_transaction($controller, $params);

	}

	public function studentFeeEditTransaction($controller, $params) {
		$this->addStudentBreadCrumb($controller);
		$controller->Navigation->addCrumb('Edit ' . $this->headerDefault . ' Details');
		$controller->set('subheader', $this->headerDefault);
		$this->setup_add_edit_form_transaction($controller, $params);

		$this->render = 'addTransaction';
	}

	public function studentFeeDeleteTransaction($controller, $params) {
		if ($controller->Session->check('InstitutionSiteStudentFeeTransaction.id')) {
			$studentId = $controller->Session->read('InstitutionSiteStudentFee.studentId');
			$feeId = $controller->Session->read('InstitutionSiteStudentFee.feeId');
			$id = $controller->Session->read('InstitutionSiteStudentFeeTransaction.id');

			$data = $this->InstitutionSiteStudentFeeTransaction->find('first', array('recursive'=>2, 'conditions'=>array('InstitutionSiteStudentFeeTransaction.id'=>$id)));
			if(empty($data)){
				$controller->Message->alert('general.notExists');
				return $controller->redirect(array('action' => 'studentFeeView', $studentId, $feeId));
			}

			$totalFee = isset($data['InstitutionSiteStudentFee']['InstitutionSiteFee']['total_fee']) ? $data['InstitutionSiteStudentFee']['InstitutionSiteFee']['total_fee'] : 0;
			$paid = 0;
			$studentFeeId = isset($data['InstitutionSiteStudentFee']['id']) ? $data['InstitutionSiteStudentFee']['id'] : 0;
			$saveData['InstitutionSiteStudentFee'] = $data['InstitutionSiteStudentFee'];
			$this->computeFee($totalFee, $paid, $studentFeeId, $id, $saveData);
			$this->save($saveData);
			if ($this->InstitutionSiteStudentFeeTransaction->delete($id)) {
				$controller->Message->alert('general.delete.success');
			} else {
				$controller->Message->alert('general.delete.failed');
			}
			return $controller->redirect(array('action' => 'studentFee'));
		}
	}

	public function studentFeeViewTransaction($controller, $params) {
		$id = empty($params['pass'][0])? 0:$params['pass'][0];
		$studentId = $controller->Session->read('InstitutionSiteStudentFee.studentId');
		$feeId = $controller->Session->read('InstitutionSiteStudentFee.feeId');
		$model = 'InstitutionSiteStudentFeeTransaction';

		$this->addStudentBreadCrumb($controller);
		$controller->Navigation->addCrumb($this->headerDefault . ' Details');

		$controller->set('subheader', $this->headerDefault . ' Details');

		$data = $this->InstitutionSiteStudentFeeTransaction->findById($id);

		if (!empty($data)) {
			$controller->Session->write('InstitutionSiteStudentFeeTransaction.id', $id);
		} else {
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => 'studentFee'));
		}
		$fields = $this->getDisplayFields($controller, $model);

		$controller->set('subheader', $this->headerDefault . ' Details');
		$controller->set(compact('id', 'studentId', 'feeId', 'data', 'fields'));
	}
	

	function setup_add_edit_form_transaction($controller, $params){
		$studentId = $controller->Session->read('InstitutionSiteStudentFee.studentId');
		$feeId = $controller->Session->read('InstitutionSiteStudentFee.feeId');

		$feeData = $this->find('first', array('conditions'=>array('InstitutionSiteStudentFee.student_id'=>$studentId, 'InstitutionSiteStudentFee.institution_site_fee_id'=>$feeId)));

		if($controller->request->is('get')){
			$id = empty($params['pass'][0])? 0:$params['pass'][0];

			$data = $this->InstitutionSiteStudentFeeTransaction->find('first', array('recursive'=>-1,'conditions'=>array('InstitutionSiteStudentFeeTransaction.id'=>$id)));
			if(empty($id) || empty($data)){
				$id = null;
			}
			if(!empty($data)){
				$data['InstitutionSiteStudentFeeTransaction'][0] = $data['InstitutionSiteStudentFeeTransaction'];
			}
			$controller->request->data = $data;
		}else{
			$saveData = $controller->request->data;
			$id = (isset($saveData['InstitutionSiteStudentFeeTransaction'][0]['id']) ? $saveData['InstitutionSiteStudentFeeTransaction'][0]['id'] : 0);

			$studentFeeId = isset($feeData) ? $feeData['InstitutionSiteStudentFee']['id'] : 0;
			$saveData['InstitutionSiteStudentFee']['id'] = $studentFeeId;
			$saveData['InstitutionSiteStudentFee']['institution_site_fee_id'] = $feeId;
			$saveData['InstitutionSiteStudentFee']['student_id'] = $studentId;

			$totalFee = $feeData['InstitutionSiteFee']['total_fee'];
			$paid = $saveData['InstitutionSiteStudentFeeTransaction'][0]['paid'];
		
			$this->computeFee($totalFee, $paid, $studentFeeId, $id, $saveData);
			if(empty($saveData['InstitutionSiteStudentFee']['id'])){
				$this->create();
			}
			if($this->saveAll($saveData)){
				if(empty($saveData['InstitutionSiteStudentFeeTransaction'][0]['id'])){
					$controller->Message->alert('general.add.success');
				}else{
					$controller->Message->alert('general.edit.success');
				}
				return $controller->redirect(array('action' => 'studentFeeView', $studentId, $feeId));
			}
		}
		$model = 'InstitutionSiteStudentFeeTransaction';
		$controller->set(compact('studentId','feeId', 'model'));
	}

	private function computeFee($totalFee, $paid, $studentFeeId, $id, &$saveData){
		$this->InstitutionSiteStudentFeeTransaction->virtualFields = array('total_paid' => 'SUM(InstitutionSiteStudentFeeTransaction.paid)');
		$totalPaid = $this->InstitutionSiteStudentFeeTransaction->find('first', array('fields'=>array('total_paid'), 'conditions'=>array('InstitutionSiteStudentFeeTransaction.institution_site_student_fee_id'=>$studentFeeId, 'InstitutionSiteStudentFeeTransaction.id !='.$id)));
		$totalPaid = $totalPaid['InstitutionSiteStudentFeeTransaction']['total_paid'];

		pr($totalPaid);

	
		$saveData['InstitutionSiteStudentFee']['total_paid'] = (floatval($totalPaid) + floatval($paid));
		$saveData['InstitutionSiteStudentFee']['total_outstanding'] = floatval($totalFee) - floatval($totalPaid) - floatval($paid);
		if($saveData['InstitutionSiteStudentFee']['total_outstanding']<0){
			$saveData['InstitutionSiteStudentFee']['total_outstanding'] = 0;
		}

		pr($saveData['InstitutionSiteStudentFee']['total_paid'] );
	}

	private function getListOfFees($yearId, $institutionSiteId) {
		$data = array();

		$programmeGrades = ClassRegistry::init('InstitutionSiteProgramme')->getProgrammeList($institutionSiteId, $yearId);
		foreach($programmeGrades as $programGrade){
			$fees = $this->InstitutionSiteFee->find('all', array(
				'recursive' => -1,
				'fields' => array('Student.id', 'Student.identification_no', 'Student.first_name', 'Student.last_name', 'InstitutionSiteFee.id', 
					'InstitutionSiteStudentFee.id', 'InstitutionSiteStudentFee.total_paid', 'InstitutionSiteFee.total_fee',
					'InstitutionSiteStudentFee.total_outstanding', 'EducationGrade.id', 'EducationGrade.name', 
					'InstitutionSiteProgramme.id'),
					'joins' => array(
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
				),
				'conditions' => array(
					'InstitutionSiteProgramme.school_year_id' => $yearId,
					'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId,
					'EducationGrade.id' => array_keys($programGrade['education_grades'])
				),
				'order' => array('EducationGrade.id', 'Student.first_name', 'Student.last_name')
			));
			if(!empty($fees)){
				foreach($fees as $fee) {
					$totalOutstanding = $fee['InstitutionSiteStudentFee']['total_outstanding'];
					if(empty($fee['InstitutionSiteStudentFee']['total_paid'])){
						$totalOutstanding = $fee['InstitutionSiteFee']['total_fee'];
					}
					$data[$fee['InstitutionSiteProgramme']['id'].'_'.$fee['EducationGrade']['id']][] = array(
						'student_id' => $fee['Student']['id'],
						'id' => $fee['InstitutionSiteFee']['id'],
						'student_fee_id' => $fee['InstitutionSiteStudentFee']['id'],
						'identification_no' => $fee['Student']['identification_no'],
						'name' => $fee['Student']['first_name'] . ' ' . $fee['Student']['last_name'],
						'grade' => $fee['EducationGrade']['name'],
						'total_paid'=> (isset($fee['InstitutionSiteStudentFee']['total_paid']) ? $fee['InstitutionSiteStudentFee']['total_paid'] : 0),
						'total_outstanding'=> $totalOutstanding
					);
				}
			}
		}
		
		return $data;
	}

}
