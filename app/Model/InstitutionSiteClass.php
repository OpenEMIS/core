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

class InstitutionSiteClass extends AppModel {
    
	public $belongsTo = array('SchoolYear');
	
	public $actsAs = array(
		'CascadeDelete' => array(
			'cascade' => array(
				'InstitutionSiteClassGrade',
				'InstitutionSiteClassStaff'
			)
		),
                'ControllerAction'
	);
	
	public function isNameExists($name, $institutionSiteId, $yearId) {
		$count = $this->find('count', array(
			'conditions' => array(
				'InstitutionSiteClass.name LIKE' => $name,
				'InstitutionSiteClass.institution_site_id' => $institutionSiteId,
				'InstitutionSiteClass.school_year_id' => $yearId
			)
		));
		return $count>0;
	}
	
	public function getClass($classId, $institutionSiteId=0) {
		$conditions = array('InstitutionSiteClass.id' => $classId);
		
		if($institutionSiteId > 0) {
			$conditions['InstitutionSiteClass.institution_site_id'] = $institutionSiteId;
		}
		
		$obj = $this->find('first', array('conditions' => $conditions));
		return $obj;
	}
	
	public function getListOfClasses($yearId, $institutionSiteId) {
		$InstitutionSiteClassGrade = ClassRegistry::init('InstitutionSiteClassGrade');
		$InstitutionSiteClassGradeStudent = ClassRegistry::init('InstitutionSiteClassGradeStudent');
		
		$classes = $this->find('list', array(
			'fields' => array('InstitutionSiteClass.id', 'InstitutionSiteClass.name'),
			'conditions' => array(
				'InstitutionSiteClass.school_year_id' => $yearId,
				'InstitutionSiteClass.institution_site_id' => $institutionSiteId
			),
			'order' => array('InstitutionSiteClass.name')
		));
		
		$data = array();
		foreach($classes as $id => $name) {
			$data[$id] = array(
				'name' => $name,
				'grades' => $InstitutionSiteClassGrade->getGradesByClass($id),
				'gender' => $InstitutionSiteClassGradeStudent->getGenderTotalByClass($id)
			);
		}
		return $data;
	}
	
	public function getClassOptions($yearId, $institutionSiteId, $gradeId=false) {
		$options = array(
			'fields' => array('InstitutionSiteClass.id', 'InstitutionSiteClass.name'),
			'conditions' => array(
				'InstitutionSiteClass.school_year_id' => $yearId,
				'InstitutionSiteClass.institution_site_id' => $institutionSiteId
			),
			'order' => array('InstitutionSiteClass.name')
		);
		
		if($gradeId!==false) {
			$options['joins'] = array(
				array(
					'table' => 'institution_site_class_grades',
					'alias' => 'InstitutionSiteClassGrade',
					'conditions' => array(
						'InstitutionSiteClassGrade.institution_site_class_id = InstitutionSiteClass.id',
						'InstitutionSiteClassGrade.education_grade_id = ' . $gradeId
					)
				)
			);
			$options['group'] = array('InstitutionSiteClass.id');
		}
		$data = $this->find('list', $options);
		return $data;
	}
        
        public function getClassListByInstitution($institutionSiteId){
            $data = $this->find('list', array(
                'fields' => array('InstitutionSiteClass.id', 'InstitutionSiteClass.name'),
                'conditions' => array(
                    'InstitutionSiteClass.institution_site_id' => $institutionSiteId
                ),
                'order' => array('InstitutionSiteClass.name')
            ));
            
            return $data;
        }
		
		public function getClassListByInstitutionSchoolYear($institutionSiteId, $yearId){
			if(empty($yearId)){
				$conditions = array(
                    'InstitutionSiteClass.institution_site_id' => $institutionSiteId
                );
			}else{
				$conditions = array(
                    'InstitutionSiteClass.institution_site_id' => $institutionSiteId,
					'InstitutionSiteClass.school_year_id' => $yearId
                );
			}
			
            $data = $this->find('list', array(
                'fields' => array('InstitutionSiteClass.id', 'InstitutionSiteClass.name'),
                'conditions' => $conditions,
                'order' => array('InstitutionSiteClass.name')
            ));
            
            return $data;
        }
        
        public function classes($controller, $params) {
        $controller->Navigation->addCrumb('List of Classes');
        $yearOptions = $controller->SchoolYear->getYearList();
        $selectedYear = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : key($yearOptions);
        $data = $controller->InstitutionSiteClass->getListOfClasses($selectedYear, $controller->institutionSiteId);

        // Checking if user has access to add
        $_add_class = $controller->AccessControl->check('InstitutionSites', 'classesAdd');
        // End Access Control
        
        $controller->set(compact('yearOptions', 'selectedYear', 'data', '_add_class'));
    }

    public function classesAdd($controller, $params) {
        if ($controller->request->is('get')) {
            $controller->Navigation->addCrumb('Add Class');
            $years = $controller->SchoolYear->getYearList();
            $yearOptions = array();

            $programmeOptions = array();
            foreach ($years as $yearId => $year) {
                $programmes = $controller->InstitutionSiteProgramme->getProgrammeOptions($controller->institutionSiteId, $yearId);
                if (!empty($programmes)) {
                    $yearOptions[$yearId] = $year;
                    if (empty($programmeOptions)) {
                        $programmeOptions = $programmes;
                    }
                }
            }
            $displayContent = !empty($programmeOptions);

            if ($displayContent) {
                $gradeOptions = array();
                $selectedProgramme = false;
                // loop through the programme list until a valid list of grades is found
                foreach ($programmeOptions as $programmeId => $name) {
                    $gradeOptions = $controller->EducationGrade->getGradeOptions($programmeId, array(), true);
                    if (!empty($gradeOptions)) {
                        $selectedProgramme = $programmeId;
                        break;
                    }
                }
				
				$shiftMax = intval($controller->ConfigItem->getValue('no_of_shifts'));
                $shiftOptions = array();
                if($shiftMax > 1){
                    for($i=1; $i <= $shiftMax; $i++){
                        $shiftOptions[$i] = $i;
                    }
                }else{
                    $shiftOptions[1] = 1;
                }
                //pr($shiftOptions);
                
                $controller->set(compact('yearOptions', 'programmeOptions', 'selectedProgramme', 'gradeOptions', 'shiftOptions'));
            } else {
                $controller->Utility->alert($controller->Utility->getMessage('CENSUS_NO_PROG'), array('type' => 'warn', 'dismissOnClick' => false));
            }
            
            $controller->set(compact('displayContent'));
        } else {
            $classData = $controller->data['InstitutionSiteClass'];
            $classData['institution_site_id'] = $controller->institutionSiteId;
            $controller->InstitutionSiteClass->create();
            $classObj = $controller->InstitutionSiteClass->save($classData);
            if ($classObj) {
                $classId = $classObj['InstitutionSiteClass']['id'];
                $gradesData = $controller->data['InstitutionSiteClassGrade'];
                $grades = array();
                foreach ($gradesData as $obj) {
                    $gradeId = $obj['education_grade_id'];
                    if ($gradeId > 0 && !in_array($gradeId, $grades)) {
                        $grades[] = $obj['education_grade_id'];
                        $obj['institution_site_class_id'] = $classId;
                        $controller->InstitutionSiteClassGrade->create();
                        $controller->InstitutionSiteClassGrade->save($obj);
                    }
                }
            }
            $controller->redirect(array('action' => 'classesEdit', $classId));
        }
    }

    public function classesView($controller, $params) {
        $classId = $controller->params['pass'][0];
        $controller->Session->write('InstitutionSiteClassId', $classId);
        $classObj = $controller->InstitutionSiteClass->getClass($classId);

        if (!empty($classObj)) {
            $className = $classObj['InstitutionSiteClass']['name'];
            $controller->Navigation->addCrumb($className);

            $grades = $controller->InstitutionSiteClassGrade->getGradesByClass($classId);
            $students = $controller->InstitutionSiteClassGradeStudent->getStudentsByGrade(array_keys($grades));
            $staffs = $controller->InstitutionSiteClassStaff->getStaffs($classId);
            $subjects = $controller->InstitutionSiteClassSubject->getSubjects($classId);

            $yearId = $classObj['SchoolYear']['id'];
            $year = $classObj['SchoolYear']['name'];
            $noOfSeats = $classObj['InstitutionSiteClass']['no_of_seats'];
            $noOfShifts = $classObj['InstitutionSiteClass']['no_of_shifts'];
            
            $controller->set(compact('classId', 'className', 'yearId', 'year', 'grades', 'students', 'staffs', 'noOfSeats', 'noOfShifts', 'subjects'));
        } else {
            $controller->redirect(array('action' => 'classesList'));
        }
    }

    public function classesEdit($controller, $params) {
        $classId = $controller->params['pass'][0];
        $classObj = $controller->InstitutionSiteClass->getClass($classId);

        if (!empty($classObj)) {
			if ($controller->request->is('post')) {
                $data = $controller->data['InstitutionSiteClass'];
                $data['id'] = $classId;
                //pr($data);
                $controller->InstitutionSiteClass->save($data);
                $controller->redirect(array('action' => 'classesView', $classId));
            }
			
            $className = $classObj['InstitutionSiteClass']['name'];
            $controller->Navigation->addCrumb(__('Edit') . ' ' . $className);

            $grades = $controller->InstitutionSiteClassGrade->getGradesByClass($classId);
            $students = $controller->InstitutionSiteClassGradeStudent->getStudentsByGrade(array_keys($grades));
            $staffs = $controller->InstitutionSiteClassStaff->getStaffs($classId);
            $subjects = $controller->InstitutionSiteClassSubject->getSubjects($classId);
            $studentCategoryOptions = $controller->StudentCategory->findList(true);
            
            $year = $classObj['SchoolYear']['name'];
            $noOfSeats = $classObj['InstitutionSiteClass']['no_of_seats'];
            $noOfShifts = $classObj['InstitutionSiteClass']['no_of_shifts'];
			
			$shiftMax = intval($controller->ConfigItem->getValue('no_of_shifts'));
            $shiftOptions = array();
            if($shiftMax > 1){
                for($i=1; $i <= $shiftMax; $i++){
                    $shiftOptions[$i] = $i;
                }
            }else{
                $shiftOptions[1] = 1;
            }
            //pr($shiftOptions);
            
            $controller->set(compact('classId', 'className', 'year', 'grades', 'students', 'staffs', 'noOfSeats', 'noOfShifts', 'studentCategoryOptions', 'subjects', 'shiftOptions'));
        } else {
            $controller->redirect(array('action' => 'classesList'));
        }
    }

    public function classesDelete($controller, $params) {
        $id = $controller->params['pass'][0];
        $name = $controller->InstitutionSiteClass->field('name', array('InstitutionSiteClass.id' => $id));
        $controller->InstitutionSiteClass->delete($id);
        $controller->Utility->alert($name . ' have been deleted successfully.');
        $controller->redirect(array('action' => 'classes'));
    }
	
	public function classesAddStaffRow($controller, $params) {
        if (sizeof($params['pass']) == 2) {
            $year = $params['pass'][0];
            $classId = $params['pass'][1];
            $index = $params->query['index'];
			
			$InstitutionSiteStaff = ClassRegistry::init('InstitutionSiteStaff');
			$data = $InstitutionSiteStaff->getStaffSelectList($year, $controller->institutionSiteId, $classId);

            $controller->set('index', $index);
            $controller->set('data', $data);
        }
    }

	public function classesStaffAjax($controller, $params)  {
        //$this->autoRender = false;
		$this->render = false;
        if (sizeof($params['pass']) == 1) {
			
            $classId = $params['pass'][0];
            $staffId = $params->query['staffId'];
            $action = $params->query['action'];

            $result = false;
			$InstitutionSiteClassStaff = ClassRegistry::init('InstitutionSiteClassStaff');
            if ($action === 'add') {
                $data = array('staff_id' => $staffId, 'institution_site_class_id' => $classId);
				
				if (!$InstitutionSiteClassStaff->hasAny($data)){
					//do something
					$InstitutionSiteClassStaff->create();
					$result = $InstitutionSiteClassStaff->save($data);
				}
				else{
					return json_encode($controller->Message->get('general.add.failed'));
				}
                
            } else {
                $result = $InstitutionSiteClassStaff->deleteAll(array(
                    'InstitutionSiteClassStaff.staff_id' => $staffId,
                    'InstitutionSiteClassStaff.institution_site_class_id' => $classId
                        ), false);
            }

            $return = array();
            if ($result) {
                $controller->Utility->setAjaxResult('success', $return);
            } else {
                $return = $controller->Message->get('general.add.error');
            }
            return json_encode($return);
        }
    }

	public function classesDeleteStaff($controller, $params)  {
       // $this->autoRender = false;
		$this->render = false;
        if (sizeof($params['pass']) == 1) {
            $gradeId = $params['pass'][0];
            $studentId = $params->query['studentId'];

            $data = array('student_id' => $studentId, 'institution_site_class_grade_id' => $gradeId);
			
			$InstitutionSiteClassGradeStudent = ClassRegistry::init('InstitutionSiteClassGradeStudent');
            $InstitutionSiteClassGradeStudent->create();
            $obj = $InstitutionSiteClassGradeStudent->save($data);

            $result = array();
            if ($obj) {
                $controller->Utility->setAjaxResult('success', $result);
            } else {
                $controller->Utility->setAjaxResult('error', $result);
                $result['msg'] = $controller->Utility->getMessage('ERROR_UNEXPECTED');
            }
            return json_encode($result);
        }
    }
		
	public function getClassByIdSchoolYear($classId, $schoolYearId){
		$data = $this->find('first', array(
			'recursive' => -1,
			'conditions' => array(
				'InstitutionSiteClass.id' => $classId,
				'InstitutionSiteClass.school_year_id' => $schoolYearId
			)
		));
		
		return $data;
	}
}
