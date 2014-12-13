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

class InstitutionSiteClassSubject extends AppModel {
	public $actsAs = array('ControllerAction');
	
	public $belongsTo = array(
		'InstitutionSiteClass'
	);
	
	public $_action = 'classesSubject';
	
	public function beforeAction($controller, $action) {
		parent::beforeAction($controller, $action);
		$id = $controller->Session->read('InstitutionSiteClass.id');
		
		if($this->InstitutionSiteClass->exists($id)) {
			$header = $this->InstitutionSiteClass->field('name', array('id' => $id));
			$controller->Navigation->addCrumb($header);
			$controller->set('header', $header);
			$controller->set('_action', $this->_action);
			$controller->set('selectedAction', $this->_action);
			$controller->set('actionOptions', $this->InstitutionSiteClass->getClassActions($controller));
		} else {
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => $this->InstitutionSiteClass->_action));
		}
	}
	
	public function classesSubject($controller, $params) {
		$id = $controller->Session->read('InstitutionSiteClass.id');
		
		$data = $this->find('all', array(
			'fields' => array(
				'EducationGradeSubject.id', 'EducationGrade.name', 'EducationSubject.code', 'EducationSubject.name'
			),
			'joins' => array(
				array(
					'table' => 'education_grades_subjects',
					'alias' => 'EducationGradeSubject',
					'conditions' => array('EducationGradeSubject.id = InstitutionSiteClassSubject.education_grade_subject_id')
				),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.id = EducationGradeSubject.education_grade_id')
				),
				array(
					'table' => 'education_subjects',
					'alias' => 'EducationSubject',
					'conditions' => array('EducationSubject.id = EducationGradeSubject.education_subject_id')
				)
			),
			'conditions' => array(
				'institution_site_class_id' => $id,
				'status' => 1
			),
			'order' => array('EducationSubject.order ASC')
		));
		if(empty($data)) {
			$controller->Message->alert('general.noData');
		}
		$controller->set(compact('data'));
	}
	
	public function classesSubjectEdit($controller, $params) {
		$id = $controller->Session->read('InstitutionSiteClass.id');
		$maxSubjects = $controller->ConfigItem->getValue('max_subjects_per_class');

		if($controller->request->is('get')) {
			$data = ClassRegistry::init('EducationSubject')->find('all', array(
				'recursive' => 0,
				'fields' => array(
					'EducationSubject.id', 'EducationGradeSubject.id', 'EducationGrade.name', 'EducationSubject.code', 'EducationSubject.name',
					'InstitutionSiteClassSubject.id', 'InstitutionSiteClassSubject.status'
				),
				'joins' => array(
					array(
						'table' => 'education_grades_subjects',
						'alias' => 'EducationGradeSubject',
						'conditions' => array('EducationGradeSubject.education_subject_id = EducationSubject.id')
					),
					array(
						'table' => 'education_grades',
						'alias' => 'EducationGrade',
						'conditions' => array(
							'EducationGrade.id = EducationGradeSubject.education_grade_id'
						)
					),
					array(
						'table' => 'education_programmes',
						'alias' => 'EducationProgramme',
						'conditions' => array(
							'EducationGrade.education_programme_id = EducationProgramme.id'
						)
					),
					array(
						'table' => 'institution_site_programmes',
						'alias' => 'InstitutionSiteProgramme',
						'conditions' => array(
							'InstitutionSiteProgramme.education_programme_id = EducationProgramme.id',
							'InstitutionSiteProgramme.status = 1'
						)
					),
					array(
						'table' => 'institution_site_classes',
						'alias' => 'InstitutionSiteClass',
						'conditions' => array(
							'InstitutionSiteProgramme.institution_site_id = InstitutionSiteClass.institution_site_id',
							'InstitutionSiteProgramme.school_year_id = InstitutionSiteClass.school_year_id',
							'InstitutionSiteClass.id = ' . $id
						)
					),
					array(
						'table' => 'institution_site_class_subjects',
						'alias' => $this->alias,
						'type' => 'LEFT',
						'conditions' => array(
							$this->alias . '.institution_site_class_id = InstitutionSiteClass.id',
							$this->alias . '.education_grade_subject_id = EducationGradeSubject.id'
						)
					)
				),
				'order' => array('EducationSubject.order', 'InstitutionSiteClassSubject.id DESC')
			));
			
			if(empty($data)) {
				$controller->Message->alert('general.noData');
			}
			$controller->set(compact('data', 'id'));
		} else {
			$data = $controller->request->data;
			if(isset($data[$this->alias])) {
				foreach($data[$this->alias] as $i => $obj) {
					if(empty($obj['id']) && $obj['status'] == 0) {
						unset($data[$this->alias][$i]);
					}
				}
				if(!empty($data[$this->alias])) {
					$this->saveAll($data[$this->alias]);
				}
			}
			$controller->Message->alert('general.edit.success');
			return $controller->redirect(array('action' => $this->_action));
		}
	}
	
	// used by InstitutionSite.classesEdit/classesView
	public function getSubjects($classId) {
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'InstitutionSiteClassSubject.id', 'InstitutionSiteClassSubject.education_grade_subject_id',
                'EducationSubject.code', 'EducationSubject.name', 'EducationGrade.name'
			),
			'joins' => array(
                array(
                    'table' => 'education_grades_subjects',
                    'alias' => 'EducationGradeSubject',
                    'type' => 'LEFT',
                    'conditions' => array('EducationGradeSubject.id = InstitutionSiteClassSubject.education_grade_subject_id',
                                          'EducationGradeSubject.visible = 1')
                ),
                array(
                    'table' => 'education_subjects',
                    'alias' => 'EducationSubject',
                    'type' => 'LEFT',
                    'conditions' => array('EducationSubject.id = EducationGradeSubject.education_subject_id',
                                          'EducationSubject.visible = 1')
                ),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'type' => 'LEFT',
					'conditions' => array('EducationGrade.id = EducationGradeSubject.education_grade_id',
                                          'EducationGrade.visible = 1')
				)
			),
			'conditions' => array('InstitutionSiteClassSubject.institution_site_class_id' => $classId)
		));
		return $data;
	}
}
