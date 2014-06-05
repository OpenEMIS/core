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
	
	public function beforeAction($controller, $action) {
        $controller->set('model', $this->alias);
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
    
    public function studentsBehaviour($controller, $params) {
        extract($controller->studentsCustFieldYrInits());
        $controller->Navigation->addCrumb('List of Behaviour');

        $data = $controller->StudentBehaviour->getBehaviourData($id, $controller->institutionSiteId);

        if (empty($data)) {
            $controller->Utility->alert($controller->Utility->getMessage('STUDENT_NO_BEHAVIOUR_DATA'), array('type' => 'info'));
        }
        
        $controller->set(compact('id', 'data'));
    }

    public function studentsBehaviourAdd($controller, $params) { //pr('asd');die;
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

            $controller->StudentBehaviour->create();
            if (!$controller->StudentBehaviour->save($studentBehaviourData)) {
                // Validation Errors
                //debug($controller->StudentBehaviour->validationErrors); 
                //die;
            } else {
                $controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
            }

            $controller->redirect(array('action' => 'studentsBehaviour', $studentBehaviourData['student_id']));
        }
    }

    public function studentsBehaviourView($controller, $params) {
        $studentBehaviourId = $controller->params['pass'][0];
        $studentBehaviourObj = $controller->StudentBehaviour->find('all', array('conditions' => array('StudentBehaviour.id' => $studentBehaviourId)));

        if (!empty($studentBehaviourObj)) {
            $studentId = $studentBehaviourObj[0]['StudentBehaviour']['student_id'];
            $data = $controller->Student->find('first', array('conditions' => array('Student.id' => $studentId)));
            $name = sprintf('%s %s', $data['Student']['first_name'], $data['Student']['last_name']);
            $controller->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'studentsView', $studentId));
            $controller->Navigation->addCrumb('Behaviour Details');

            $yearOptions = array();
            $yearOptions = $controller->SchoolYear->getYearList();
            $categoryOptions = array();
            $categoryOptions = $controller->StudentBehaviourCategory->getCategory();

            $institutionSiteOptions = $controller->InstitutionSite->find('list', array('recursive' => -1, 'conditions' => array('id' => $controller->institutionSiteId)));
            
            $institutionSiteId = $controller->institutionSiteId;
            $controller->Session->write('StudentBehavourId', $studentBehaviourId);
            
            $controller->set(compact('institutionSiteId', 'categoryOptions', 'institutionSiteOptions', 'yearOptions', 'studentBehaviourObj'));
        } else {
            //$controller->redirect(array('action' => 'classesList'));
        }
    }

    public function studentsBehaviourEdit($controller, $params) {
        if ($controller->request->is('get')) {
            $studentBehaviourId = $controller->params['pass'][0];
            $studentBehaviourObj = $controller->StudentBehaviour->find('all', array('conditions' => array('StudentBehaviour.id' => $studentBehaviourId)));

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

            $controller->StudentBehaviour->create();
            if (!$controller->StudentBehaviour->save($studentBehaviourData)) {
                // Validation Errors
                //debug($controller->StudentBehaviour->validationErrors); 
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
            $name = $controller->StudentBehaviour->field('title', array('StudentBehaviour.id' => $id));
            $institution_site_id = $controller->StudentBehaviour->field('institution_site_id', array('StudentBehaviour.id' => $id));
            if ($institution_site_id != $controller->institutionSiteId) {
                $controller->Utility->alert($controller->Utility->getMessage('SECURITY_NO_ACCESS'));
                $controller->redirect(array('action' => 'studentsBehaviourView', $id));
            }
            $controller->StudentBehaviour->delete($id);
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
	
	//Student Module
	public function behaviour($controller, $params) {
     //   extract($controller->studentsCustFieldYrInits());
        $controller->Navigation->addCrumb('List of Behaviour');
		$header = __('List of Behaviour');
        $data = $this->getBehaviourData($controller->studentId);
        if (empty($data)) {
			$controller->Message->alert('general.noData');
           // $controller->Utility->alert($controller->Utility->getMessage('CUSTOM_FIELDS_NO_RECORD'));
        }

        $controller->set(compact('data', 'header'));
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
