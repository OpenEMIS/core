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

class EducationSubject extends AppModel {
	public $actsAs = array('ControllerAction', 'Reorder');
	public $hasMany = array('EducationGradeSubject');
	
	public $validate = array(
		'code' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a code'
			)
		),
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a name'
			)
		)
	);
	
	public $_action = 'subjects';
	public $_header = 'Education Subjects';
	
	public function beforeAction($controller, $action) {
		parent::beforeAction($controller, $action);
		$controller->Navigation->addCrumb($this->_header);
		$controller->set('header', __($this->_header));
		$controller->set('_action', $this->_action);
		$controller->set('selectedAction', $this->_action);
	}
	
	public function getDisplayFields($controller) {
		$yesnoOptions = $controller->Option->get('yesno');
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'id', 'type' => 'hidden'),
				array('field' => 'code'),
				array('field' => 'name'),
				array('field' => 'visible', 'type' => 'select', 'options' => $yesnoOptions),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		return $fields;
	}
	
	public function subjects($controller, $params) {
		$data = $this->find('all', array('order' => $this->alias.'.order'));
		$controller->set(compact('data'));
	}
	
	public function subjectsAdd($controller, $params) {
		if($controller->request->is('post') || $controller->request->is('put')) {
			$controller->request->data[$this->alias]['order'] = $this->field('order', array(), 'order DESC') + 1;
			if ($this->save($controller->request->data)) {
				$controller->Message->alert('general.add.success');
				return $controller->redirect(array('action' => $this->_action));
			}
		}
	}
	
	public function subjectsView($controller, $params) {
		$id = isset($params->pass[0]) ? $params->pass[0] : 0;
		$data = $this->findById($id);
		$fields = $this->getDisplayFields($controller);
		$controller->set(compact('data', 'fields'));
	}
	
	public function subjectsEdit($controller, $params) {
		$id = isset($params->pass[0]) ? $params->pass[0] : 0;
		$data = $this->findById($id);
		
		if(!empty($data)) {
			$fields = $this->getDisplayFields($controller);
			$controller->set(compact('fields'));
			if($controller->request->is('post') || $controller->request->is('put')) {
				if ($this->save($controller->request->data)) {
					$controller->Message->alert('general.edit.success');
					return $controller->redirect(array('action' => $this->_action.'View', $id));
				}
			} else {
				$controller->request->data = $data;
			}
		} else {
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => $this->_action));
		}
	}
	
	// Used by InstitutionSiteController.classesAddTeacherRow
	public function getSubjectByClassId($classId) {
        // Filtering section
        $InstitutionSiteClassSubject = ClassRegistry::init('InstitutionSiteClassSubject');
        $subjectsExclude = $InstitutionSiteClassSubject->getSubjects($classId);
        $ids = '';
        foreach($subjectsExclude as $obj){
            $ids .= $obj['InstitutionSiteClassSubject']['education_grade_subject_id'].',';
        }
        $ids = rtrim($ids,',');

        if($ids!=''){
            $conditions = 'EducationGradeSubject.id NOT IN (' . $ids . ')';
        }else{
            $conditions = '';
        }
        // End filtering

		$this->formatResult = true;
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('EducationGradeSubject.id', 'EducationSubject.code', 'EducationSubject.name', 'EducationGrade.name AS grade'),
			'joins' => array(
				array(
					'table' => 'education_grades_subjects',
					'alias' => 'EducationGradeSubject',
					'conditions' => array('EducationGradeSubject.education_subject_id = EducationSubject.id',$conditions)
				),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.id = EducationGradeSubject.education_grade_id')
				),
				array(
					'table' => 'institution_site_class_grades',
					'alias' => 'InstitutionSiteClassGrade',
					'conditions' => array(
						'InstitutionSiteClassGrade.education_grade_id = EducationGrade.id',
						'InstitutionSiteClassGrade.institution_site_class_id = ' . $classId
					)
				)
			),
			'group' => array('EducationSubject.id'),
			'conditions' => array('EducationSubject.visible' => 1),
			'order' => array('EducationSubject.order')
		));
		return $data;
	}
}
