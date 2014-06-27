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

class StudentBehaviour extends StudentsAppModel {
    public $actsAs = array(
		'ControllerAction',
		'DatePicker' => array('date_of_behaviour'),
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		)
	);

    public $useTable = 'student_behaviours';
    public $validate = array(
        'title' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                'message' => 'Please enter a valid title'
            )
        )
    );
	
	public $belongsTo = array(
		'Students.Student',
		'InstitutionSite', 
		'StudentBehaviourCategory',
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
		));
	
	public $reportMapping = array(
		1 => array(
			'fields' => array(
                'InstitutionSite' => array(
                    'name' => 'Institution'
                ),
                'Student' => array(
                    'identification_no' => 'Student OpenEMIS ID',
                    'first_name' => '',
                    'middle_name' => '',
                    'last_name' => '',
                    'preferred_name' => ''
                ),
                'StudentBehaviourCategory' => array(
                    'name' => 'Category'
                ),
                'StudentBehaviour' => array(
                    'date_of_behaviour' => 'Date',
                    'title' => 'Title',
                    'description' => 'Description',
                    'action' => 'Action'
                )
            ),
            'fileName' => 'Report_Student_Behaviour'
		)
	);
	
	public function getDisplayFields($controller) {
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'id', 'type' => 'hidden'),
				array('field' => 'name', 'model' => 'StudentBehaviourCategory', 'labelKey' => 'general.category'),
				array('field' => 'date_of_behaviour', 'type' => 'datepicker', 'labelKey' => 'general.date'),
				array('field' => 'title'),
				array('field' => 'description', 'type' => 'textarea'),
				array('field' => 'action', 'type' => 'textarea'),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		return $fields;
	}
	
	public function getDisplayFieldsStudentBehaviour($controller) {
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'name', 'model' => 'InstitutionSite'),
				array('field' => 'name', 'model' => 'StudentBehaviourCategory', 'labelKey' => 'general.category'),
				array('field' => 'date_of_behaviour', 'type' => 'datepicker', 'labelKey' => 'general.date'),
				array('field' => 'title'),
				array('field' => 'description', 'type' => 'textarea'),
				array('field' => 'action', 'type' => 'textarea'),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		return $fields;
	}
	
	public function beforeAction($controller, $action) {
        parent::beforeAction($controller, $action);
		$this->plugin = false;
    }

    public function getBehaviourData($studentId, $institutionSiteId = null) {

        $options['recursive'] = -1;
        $options['joins'] = array(
            array(
                'table' => 'student_behaviour_categories',
                'alias' => 'StudentBehaviourCategory',
                'type' => 'INNER',
                'conditions' => array(
                    'StudentBehaviourCategory.id = StudentBehaviour.student_behaviour_category_id'
                )
            ),
            array(
                'table' => 'institution_sites',
                'alias' => 'InstitutionSite',
                'type' => 'INNER',
                'conditions' => array(
                    'InstitutionSite.id = StudentBehaviour.institution_site_id'
                )
            )
        );
        $options['fields'] = array('StudentBehaviour.id', 'StudentBehaviour.title', 'StudentBehaviour.date_of_behaviour',
            'StudentBehaviourCategory.name', 'InstitutionSite.name', 'InstitutionSite.id');
        if (!empty($institutionSiteId)) {
            $options['conditions'] = array('StudentBehaviour.student_id' => $studentId, 'InstitutionSite.id' => $institutionSiteId);
        } else {
            $options['conditions'] = array('StudentBehaviour.student_id' => $studentId);
        }

        $list = $this->find('all', $options);


        return $list;
    }
    
	public function behaviourStudentList($controller, $params) {
		$controller->Navigation->addCrumb('Behaviour - Students');
		$InstitutionId = $controller->Session->read('InstitutionSite.id'); 
		$yearOptions = ClassRegistry::init('SchoolYear')->getYearList();
		$selectedYear = empty($params['pass'][0])? key($yearOptions):$params['pass'][0];
		$classOptions = ClassRegistry::init('InstitutionSiteClass')->getClassListByInstitutionSchoolYear($InstitutionId,$selectedYear);
		$selectedClass = empty($params['pass'][1])? key($classOptions):$params['pass'][1];
	
		$data = ClassRegistry::init('InstitutionSiteClassStudent')->getStudentsByClass($selectedClass, true);
		
		if (empty($data)) {
			$controller->Message->alert('general.noData');
        }
		
		$controller->set(compact('yearOptions', 'classOptions', 'data', 'selectedYear', 'selectedClass'));
	}
    //public function studentsBehaviour($controller, $params) {
	public function behaviourStudent($controller, $params) {
        extract($controller->studentsCustFieldYrInits());
        $controller->Navigation->addCrumb('List of Behaviour');

        $data = $this->getBehaviourData($id, $controller->institutionSiteId);

        if (empty($data)) {
            $controller->Utility->alert($controller->Utility->getMessage('STUDENT_NO_BEHAVIOUR_DATA'), array('type' => 'info'));
        }
        
        $controller->set(compact('id', 'data'));
    }
	
	public function behaviourStudentAdd($controller, $params) {
		$studentId = $controller->params['pass'][0];
		$data = $this->Student->find('first', array('conditions' => array('Student.id' => $studentId)));
		$name = sprintf('%s %s', $data['Student']['first_name'], $data['Student']['last_name']);
		$controller->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'studentsView', $studentId));
		$controller->Navigation->addCrumb('Add Behaviour');

		$controller->set('header', __('Add Behaviour'));
		$this->setup_add_edit_form($controller, $params, 'add');
	}
	
	public function behaviourStudentEdit($controller, $params) {
		$studentId = $controller->params['pass'][0];
		$data = $this->Student->find('first', array('conditions' => array('Student.id' => $studentId)));
		$name = sprintf('%s %s', $data['Student']['first_name'], $data['Student']['last_name']);
		$controller->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'studentsView', $studentId));
		$controller->Navigation->addCrumb('Edit Behaviour');
		
		$controller->set('header', __('Edit Behaviour'));
		$this->setup_add_edit_form($controller, $params, 'edit');
		$this->render = 'add';
	}
	
	function setup_add_edit_form($controller, $params, $type){
		$studentId = $controller->params['pass'][0];
	//	$specialNeedTypeOptions = $this->SpecialNeedType->find('list', array('fields'=> array('id', 'name')));
	//	$controller->set('specialNeedTypeOptions', $specialNeedTypeOptions);
		if($controller->request->is('get')){
			$id = empty($params['pass'][1])? 0:$params['pass'][1];
			$this->recursive = -1;
			$data = $this->findById($id);
			if(!empty($data)){
				$controller->request->data = $data;
			}
		}
		else{
			$controller->request->data[$this->name]['institution_site_id'] = $controller->Session->read('InstitutionSite.id');
			$controller->request->data[$this->name]['student_id'] = $studentId;
			if($this->save($controller->request->data)){
				$controller->Message->alert('general.' . $type . '.success');
				return $controller->redirect(array('action' => 'behaviourStudent',$studentId));
			}
		}
		
	//	$yearOptions = $controller->SchoolYear->getYearList();
		$categoryOptions = $controller->StudentBehaviourCategory->getCategory();
		
		$controller->set(compact('studentId','categoryOptions'));
	}

/*	public function behaviourStudentAdd($controller, $params) { //pr('asd');die;
    //public function studentsBehaviourAdd($controller, $params) { //pr('asd');die;
        if ($controller->request->is('get')) {
            $studentId = $controller->params['pass'][0];
            $data = $controller->Student->find('first', array('conditions' => array('Student.id' => $studentId)));
            $name = sprintf('%s %s', $data['Student']['first_name'], $data['Student']['last_name']);
            $controller->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'studentsView', $studentId));
            $controller->Navigation->addCrumb('Add Behaviour');

            $yearOptions = array();
            $yearOptions = $controller->SchoolYear->getYearList();

            $categoryOptions = array();
            $categoryOptions = $controller->StudentBehaviourCategory->getCategory();
            $institutionSiteOptions = $controller->InstitutionSite->find('list', array('recursive' => -1, 'conditions' => array('id' => $controller->institutionSiteId)));
            
            $institutionSiteId = $controller->institutionSiteId;
            
            $controller->set(compact('institutionSiteId', 'institutionSiteOptions', 'studentId', 'categoryOptions', 'yearOptions'));
        } else {
            $studentBehaviourData = $controller->data['InstitutionSiteStudentBehaviour'];
            $studentBehaviourData['institution_site_id'] = $controller->institutionSiteId;

            $this->create();
            if (!$this->save($studentBehaviourData)) {
                // Validation Errors
                //debug($this->validationErrors); 
                //die;
            } else {
				$controller->Message->alert('general.add.success');
            }

            $controller->redirect(array('action' => 'behaviourStudent', $studentBehaviourData['student_id']));
        }
    }
*/
	public function behaviourStudentView($controller, $params) {
	//	$this->render = false;
    //public function studentsBehaviourView($controller, $params) {
        $id = $controller->params['pass'][0];
		
		$data = $this->findById($id);
		if (!empty($data)) {
			$controller->Session->write($this->alias . '.id', $id);
		} else {
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => 'behaviourStudent'));
		}
		
		$studentId = $data['StudentBehaviour']['student_id'];
		$name = sprintf('%s %s', $data['Student']['first_name'], $data['Student']['last_name']);
		$controller->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'studentsView', $studentId));
        $controller->Navigation->addCrumb('Behaviour Details');
		
		$controller->Session->write('StudentBehavour.Id', $id);
		$controller->Session->write('StudentBehavour.StudentId', $studentId);
		
		$fields = $this->getDisplayFields($controller);
		$header = __('Behaviour Details');
		$controller->set(compact('data', 'fields', 'header', 'studentId'));
    }

	public function behaviourStudentDelete($controller, $params) {
		$studentId = $controller->Session->read('StudentBehavour.StudentId');
		return $this->remove($controller, 'behaviourStudent/'. $studentId);
	}
	
   /* public function studentsBehaviourEdit($controller, $params) {
        if ($controller->request->is('get')) {
            $studentBehaviourId = $controller->params['pass'][0];
            $studentBehaviourObj = $this->find('all', array('conditions' => array('StudentBehaviour.id' => $studentBehaviourId)));

            if (!empty($studentBehaviourObj)) {
                $studentId = $studentBehaviourObj[0]['StudentBehaviour']['student_id'];

                if ($studentBehaviourObj[0]['StudentBehaviour']['institution_site_id'] != $controller->institutionSiteId) {
                    $controller->Utility->alert($controller->Utility->getMessage('SECURITY_NO_ACCESS'));
                    $controller->redirect(array('action' => 'studentsBehaviourView', $studentBehaviourId));
                }
                $data = $controller->Student->find('first', array('conditions' => array('Student.id' => $studentId)));
                $name = sprintf('%s %s', $data['Student']['first_name'], $data['Student']['last_name']);
                $controller->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'studentsView', $studentId));
                $controller->Navigation->addCrumb('Edit Behaviour Details');
				
				$institutionSiteId = $controller->institutionSiteId;

                $categoryOptions = array();
                $categoryOptions = $controller->StudentBehaviourCategory->getCategory();
                $institutionSiteOptions = $controller->InstitutionSite->find('list', array('recursive' => -1, 'conditions' => array('id' => $controller->institutionSiteId)));
                
                $controller->set(compact('institutionSiteOptions', 'categoryOptions', 'studentBehaviourObj', 'institutionSiteId'));
            } else {
                //$controller->redirect(array('action' => 'studentsBehaviour'));
            }
        } else {
            $studentBehaviourData = $controller->data['InstitutionSiteStudentBehaviour'];
            $studentBehaviourData['institution_site_id'] = $controller->institutionSiteId;

            $this->create();
            if (!$this->save($studentBehaviourData)) {
                // Validation Errors
                //debug($this->validationErrors); 
                //die;
            } else {
                $controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
            }

            $controller->redirect(array('action' => 'studentsBehaviourView', $studentBehaviourData['id']));
        }
    }

    public function studentsBehaviourDelete($controller, $params) {
        if ($controller->Session->check('InstitutionSiteStudentId') && $controller->Session->check('StudentBehavourId')) {
            $id = $controller->Session->read('StudentBehavourId');
            $studentId = $controller->Session->read('InstitutionSiteStudentId');
            $name = $this->field('title', array('StudentBehaviour.id' => $id));
            $institution_site_id = $this->field('institution_site_id', array('StudentBehaviour.id' => $id));
            if ($institution_site_id != $controller->institutionSiteId) {
                $controller->Utility->alert($controller->Utility->getMessage('SECURITY_NO_ACCESS'));
                $controller->redirect(array('action' => 'studentsBehaviourView', $id));
            }
            $this->delete($id);
            $controller->Utility->alert($name . ' have been deleted successfully.');
            $controller->redirect(array('action' => 'studentsBehaviour', $studentId));
        }
    }

    public function studentsBehaviourCheckName($controller, $params) {
        $this->render = false;
        $title = trim($controller->params->query['title']);

        if (strlen($title) == 0) {
            return $controller->Utility->getMessage('SITE_STUDENT_BEHAVIOUR_EMPTY_TITLE');
        }

        return 'true';
    }
	*/
	//Student Module
	public function behaviour($controller, $params) {
        $controller->Navigation->addCrumb('List of Behaviour');
		$header = __('List of Behaviour');
        $data = $this->getBehaviourData($controller->Session->read('Student.id'));
        if (empty($data)) {
			$controller->Message->alert('general.noData');
        }
        $controller->set(compact('data', 'header'));
    }
	
	public function behaviourView($controller, $params) {
		$id = $controller->params['pass'][0];
		$controller->Navigation->addCrumb('Behaviour Details');
		$data = $this->findById($id);
		if (empty($data)){
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => 'behaviour'));
		}
		
		$fields = $this->getDisplayFieldsStudentBehaviour($controller);
		$controller->set(compact( 'fields','data'));
	}
	
	public function reportsGetHeader($args) {
		//$institutionSiteId = $args[0];
		$index = $args[1];
		return $this->getCSVHeader($this->reportMapping[$index]['fields']);
	}

	public function reportsGetData($args) {
		$institutionSiteId = $args[0];
		$index = $args[1];

		if ($index == 1) {
			$options = array();
			$options['recursive'] = -1;
			$options['fields'] = $this->getCSVFields($this->reportMapping[$index]['fields']);
			$options['order'] = array('Student.identification_no', 'StudentBehaviour.date_of_behaviour', 'StudentBehaviour.id');
			$options['conditions'] = array('StudentBehaviour.institution_site_id' => $institutionSiteId);

			$options['joins'] = array(
				array(
                        'table' => 'institution_sites',
                        'alias' => 'InstitutionSite',
                        'conditions' => array(
                            'StudentBehaviour.institution_site_id = InstitutionSite.id'
                        )
                    ),
                    array(
                        'table' => 'student_behaviour_categories',
                        'alias' => 'StudentBehaviourCategory',
                        'conditions' => array('StudentBehaviour.student_behaviour_category_id = StudentBehaviourCategory.id')
                    ),
                    array(
                        'table' => 'students',
                        'alias' => 'Student',
                        'conditions' => array('StudentBehaviour.student_id = Student.id')
                    )
			);

			$data = $this->find('all', $options);
			
			$newData = array();
			
			foreach ($data AS $row) {
                $row['StudentBehaviour']['date_of_behaviour'] = $this->formatDateByConfig($row['StudentBehaviour']['date_of_behaviour']);
                $newData[] = $row;
            }

			return $newData;
		}
	}

	public function reportsGetFileName($args) {
		//$institutionSiteId = $args[0];
		$index = $args[1];
		return $this->reportMapping[$index]['fileName'];
	}

}
