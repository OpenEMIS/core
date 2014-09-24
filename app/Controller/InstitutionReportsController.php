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

App::uses('AppController', 'Controller');

class InstitutionReportsController extends AppController {
	public $options = array();
	public $institutionSiteId;
	public $uses = array('InstitutionSite');
	
	public function beforeFilter() {
		parent::beforeFilter();
		$this->Navigation->addCrumb('Institutions', array('controller' => 'InstitutionSites', 'action' => 'index'));
		
		if ($this->Session->check('InstitutionSite.id')) {
			$this->institutionSiteId = $this->Session->read('InstitutionSite.id');
			
			$name = $this->Session->read('InstitutionSite.data.InstitutionSite.name');
			$this->bodyTitle = $name;
			
			$this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'view'));
			$this->Navigation->addCrumb('Reports', array('controller' => 'InstitutionReports', 'action' => 'index'));
		} else {
			$this->redirect(array('controller' => 'InstitutionSites', 'action' => 'index'));
		}
    }
	
	public function generate($model, $format) {
		$this->autoRender = false;
		$modelObj = ClassRegistry::init($model);
		$method = $modelObj->getFormatFunction($format);
		if($method !== false) {
			$args = $this->params->pass;
			array_shift($args); // remove model
			array_shift($args); // remove format
			
			// args[0] = institutionSiteId
			// args[1] = index
			
			$args = array_merge(array($this->institutionSiteId), $args);
			$args = array_merge($args, $this->params->named);
			$result = call_user_func_array(array($modelObj, $method), array($args));
			//pr($result);
		}
	}
	
	public function index() {
		return $this->redirect(array('action' => 'general'));
	}
	
	public function general() {
		$header = __('General');
		$this->Navigation->addCrumb($header);
		
		$data = array(
			array('name' => 'Overview and More', 'model' => 'InstitutionSite', 'params' => array('csv' => array(1))),
			array('name' => 'Bank Accounts', 'model' => 'InstitutionSiteBankAccount')
		);
		
		foreach($data as $i => $obj) {
			$model = ClassRegistry::init($obj['model']);
			$formats = $model->getSupportedFormats();
			$data[$i]['formats'] = $formats;
		}
		
		$this->set(compact('data', 'header'));
		$this->render('index');
	}
	
	public function details() {
		$header = __('Details');
		$this->Navigation->addCrumb($header);
		
		$data = array(
			array('name' => 'Programme List', 'model' => 'InstitutionSiteProgramme', 'params' => array('csv' => array(1))),
			array('name' => 'Student List', 'model' => 'InstitutionSiteStudent', 'params' => array('csv' => array(1))),
			array('name' => 'Student Result', 'model' => 'InstitutionSiteClassStudent', 'params' => array('csv' => array(1))),
			array('name' => 'Student Attendance', 'model' => 'InstitutionSiteStudentAbsence', 'params' => array('csv' => array(1, 'dataFormatted' => true))),
			array('name' => 'Student Behaviour', 'model' => 'Students.StudentBehaviour', 'params' => array('csv' => array(1))),
			array('name' => 'Student Academic', 'model' => 'Students.StudentDetailsCustomValue', 'params' => array('csv' => array(1, 'dataFormatted' => true))),
			array('name' => 'Staff List', 'model' => 'InstitutionSiteStaff', 'params' => array('csv' => array(1))),
			array('name' => 'Staff Attendance', 'model' => 'InstitutionSiteStaffAbsence', 'params' => array('csv' => array(1, 'dataFormatted' => true))),
			array('name' => 'Staff Behaviour', 'model' => 'Staff.StaffBehaviour', 'params' => array('csv' => array(1))),
			array('name' => 'Staff Academic', 'model' => 'Staff.StaffDetailsCustomValue', 'params' => array('csv' => array(1, 'dataFormatted' => true))),
			array('name' => 'Class List', 'model' => 'InstitutionSiteClass', 'params' => array('csv' => array(1))),
			array('name' => 'Classes - Students', 'model' => 'InstitutionSiteClass', 'params' => array('csv' => array(2)))
		);
		
		foreach($data as $i => $obj) {
			$model = ClassRegistry::init($obj['model']);
			$formats = $model->getSupportedFormats();
			$data[$i]['formats'] = $formats;
		}
		
		$this->set(compact('data', 'header'));
		$this->render('index');
	}
	
	public function totals() {
		$header = __('Totals');
		$this->Navigation->addCrumb($header);
		
		$data = array(
			array('name' => 'Students', 'model' => 'CensusStudent', 'params' => array('csv' => array(1, 'dataFormatted' => true))),
			array('name' => 'Teachers', 'model' => 'CensusTeacher', 'params' => array('csv' => array(1, 'dataFormatted' => true))),
			array('name' => 'Staff', 'model' => 'CensusStaff', 'params' => array('csv' => array(1, 'dataFormatted' => true))),
			array('name' => 'Classes', 'model' => 'CensusClass', 'params' => array('csv' => array(1, 'dataFormatted' => true))),
			array('name' => 'Shifts', 'model' => 'CensusShift', 'params' => array('csv' => array(1, 'dataFormatted' => true))),
			array('name' => 'Graduates', 'model' => 'CensusGraduate', 'params' => array('csv' => array(1, 'dataFormatted' => true))),
			array('name' => 'Attendance', 'model' => 'CensusAttendance', 'params' => array('csv' => array(1, 'dataFormatted' => true))),
			array('name' => 'Results', 'model' => 'CensusAssessment', 'params' => array('csv' => array(1, 'dataFormatted' => true))),
			array('name' => 'Behaviour', 'model' => 'CensusBehaviour', 'params' => array('csv' => array(1, 'dataFormatted' => true))),
			array('name' => 'Textbooks', 'model' => 'CensusTextbook', 'params' => array('csv' => array(1, 'dataFormatted' => true))),
			array('name' => 'Infrastructure', 'model' => 'InfrastructureCategory', 'params' => array('csv' => array(1, 'dataFormatted' => true))),
			array('name' => 'Finances', 'model' => 'CensusFinance', 'params' => array('csv' => array(1, 'dataFormatted' => true))),
			array('name' => 'More', 'model' => 'CensusCustomField', 'params' => array('csv' => array(1, 'dataFormatted' => true)))
		);
		
		foreach($data as $i => $obj) {
			$model = ClassRegistry::init($obj['model']);
			$formats = $model->getSupportedFormats();
			$data[$i]['formats'] = $formats;
		}
		
		$this->set(compact('data', 'header'));
		$this->render('index');
	}
	
	public function quality() {
		$header = __('Quality');
		$this->Navigation->addCrumb($header);
		$data = array(
			array('name' => 'QA Report', 'model' => 'Quality.QualityInstitutionRubric', 'params' => array('csv' => array(1))),
			array('name' => 'Visit Report', 'model' => 'Quality.QualityInstitutionVisit', 'params' => array('csv' => array(1)))
		);
		
		foreach($data as $i => $obj) {
			$model = ClassRegistry::init($obj['model']);
			$formats = $model->getSupportedFormats();
			$data[$i]['formats'] = $formats;
		}
		
		$this->set(compact('data', 'header'));
		$this->render('index');
	}

	public function finance() {
		$header = __('Finance');
		$this->Navigation->addCrumb($header);
		$data = array(
			array('name' => 'Fees', 'model' => 'InstitutionSiteFee', 'params' => array('csv' => array(1, 'dataFormatted' => true))),
			array('name' => 'Student', 'model' => 'InstitutionSiteStudentFee', 'params' => array('csv' => array(1, 'dataFormatted' => true)))
		);
		
		foreach($data as $i => $obj) {
			$model = ClassRegistry::init($obj['model']);
			$formats = $model->getSupportedFormats();
			$data[$i]['formats'] = $formats;
		}
		
		$this->set(compact('data', 'header'));
		$this->render('index');
	}
}	
