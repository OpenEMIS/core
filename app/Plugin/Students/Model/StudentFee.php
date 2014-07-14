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

class StudentFee extends StudentsAppModel {
	public $actsAs = array('ControllerAction');

	public $useTable = 'institution_site_student_fees';

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

	public $headerDefault = 'Fees';

	public $_action = 'fee';
	
	public function beforeAction($controller, $action) {
		parent::beforeAction($controller, $action);
		$controller->set('_action', $this->_action);
		$controller->set('selectedAction', $this->_action . 'View');
	}

	public function fee($controller, $params) {
		$controller->Navigation->addCrumb($this->headerDefault);

		$studentId = $controller->Session->read('Student.id');
		$data = $this->InstitutionSiteFee->find('all', array('recursive'=>2,'conditions'=>array('InstitutionSiteStudentFee.student_id'=>$studentId)));
		
		$ConfigItem = ClassRegistry::init('ConfigItem');
		$currency = $ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'currency'));

		$modelName = $this->name;
		$controller->set('subheader', $this->headerDefault);
		$controller->set(compact('data', 'modelName', 'currency'));
	}

	public function feeView($controller, $params) {
		$studentId =$controller->Session->read('Student.id');
		$studentFeeId = empty($params['pass'][0])? 0:$params['pass'][0];

		
		$data = $this->InstitutionSiteFee->find('first', array('recursive'=>2, 'conditions'=>array('InstitutionSiteStudentFee.id'=>$studentFeeId, 'InstitutionSiteStudentFee.student_id'=>$studentId)));
		
		if(empty($data)){
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action'=>'fee'));
		}	
		
		$InstitutionSiteStudentFee = ClassRegistry::init('InstitutionSiteStudentFee');
		$institutionSiteStudentFeeTransactions = $InstitutionSiteStudentFee->getListOfTransactions($controller, $studentFeeId);

		$controller->Navigation->addCrumb($this->headerDefault . ' Details');

		$ConfigItem = ClassRegistry::init('ConfigItem');
	   	$currency = $ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'currency'));

		$controller->set('subheader', $this->headerDefault . ' Details');
		$controller->set(compact('data', 'studentFeeData', 'institutionSiteFeeTypes', 'institutionSiteStudentFeeTransactions', 'currency'));
	}



}
