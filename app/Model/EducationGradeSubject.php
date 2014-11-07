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

class EducationGradeSubject extends AppModel {
	public $useTable = 'education_grades_subjects';
	public $actsAs = array('ControllerAction2', 'Reorder');
	public $belongsTo = array(
		'EducationGrade',
		'EducationSubject',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'created_user_id'
		)
	);
	
	public $_condition = 'education_grade_id';
	
	public function beforeAction() {
		parent::beforeAction();
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		$this->setVar('conditionId', $conditionId);
		
		$this->Navigation->addCrumb('Education Grades - Subjects');
		
		$this->setVar('selectedAction', 'EducationSystem');
		$this->setVar('_condition', $this->_condition);
	}
	
	public function index() {
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationGrade->exists($conditionId)) {
			$data = $this->findAllByEducationGradeIdAndVisible($conditionId, 1, array(), array('EducationSubject.order' => 'ASC'));
			$gradeObj = $this->EducationGrade->findById($conditionId);
			$cycleObj = ClassRegistry::init('EducationCycle')->findById($gradeObj['EducationProgramme']['education_cycle_id']);
			$systemObj = ClassRegistry::init('EducationSystem')->findById($cycleObj['EducationLevel']['education_system_id']);
			$paths = array();
			$paths[] = array(
				'name' => $systemObj['EducationSystem']['name'],
				'url' => array('action' => 'EducationSystem')
			);
			$paths[] = array(
				'name' => $cycleObj['EducationLevel']['name'],
				'url' => array('action' => 'EducationLevel', 'education_system_id' => $systemObj['EducationSystem']['id'])
			);
			$paths[] = array(
				'name' => $cycleObj['EducationCycle']['name'],
				'url' => array('action' => 'EducationCycle', 'education_level_id' => $cycleObj['EducationLevel']['id'])
			);
			$paths[] = array(
				'name' => $gradeObj['EducationProgramme']['name'],
				'url' => array('action' => 'EducationProgramme', 'education_cycle_id' => $cycleObj['EducationCycle']['id'])
			);
			$paths[] = array(
				'name' => $gradeObj['EducationGrade']['name'],
				'url' => array('action' => 'EducationGrade', 'education_programme_id' => $gradeObj['EducationProgramme']['id'])
			);
			$paths[] = array(
				'name' => '(' . __('Education Subjects') . ')'
			);
			$this->setVar(compact('data', 'paths'));
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'EducationSystem'));
		}
	}
	
	public function edit() {
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationGrade->exists($conditionId)) {
			if($this->request->is('get')) {
				$data = $this->EducationSubject->find('all', array(
					'recursive' => 0,
					'fields' => array(
						'EducationSubject.id', 'EducationSubject.name', 'EducationSubject.code', 'EducationSubject.visible', 'EducationSubject.order',
						'EducationGradeSubject.id', 'EducationGradeSubject.visible', 'EducationGradeSubject.hours_required'
					),
					'joins' => array(
						array(
							'table' => 'education_grades_subjects',
							'alias' => 'EducationGradeSubject',
							'type' => 'LEFT',
							'conditions' => array(
								'EducationGradeSubject.education_subject_id = EducationSubject.id',
								'EducationGradeSubject.education_grade_id = ' . $conditionId
							)
						)
					),
					'order' => array('EducationGradeSubject.visible DESC', 'EducationSubject.order')
				));
				$this->setVar(compact('data', 'conditionId'));
			} else {
				$data = $this->request->data;

				$id = $this->request->params['named']['education_grade_id'];
				$this->deleteAll(array('education_grade_id' => $id ),false);
				
				if(isset($data[$this->alias])) {
					foreach($data[$this->alias] as $i => $obj) {
						if(empty($obj['id']) && $obj['visible'] == 0) {
							unset($data[$this->alias][$i]);
						} else {
							if(empty($obj['hours_required'])) {
								$data[$this->alias][$i]['hours_required'] = 0;
							}
						}
					}
					if(!empty($data[$this->alias])) {
						$this->saveAll($data[$this->alias]);
					}
				}
				$this->Message->alert('general.edit.success');
				return $this->redirect(array('action' => get_class($this), $this->_condition => $conditionId));
			}
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'EducationSystem'));
		}
	}
	
	public function findSubjectsByGrades($gradeIds) {
		$list = $this->find('all', array(
				'fields' => array(
					'EducationGradeSubject.id', 'EducationGradeSubject.education_grade_id', 
					'EducationGradeSubject.education_subject_id', 'EducationSubject.code', 'EducationSubject.name'
				),
				'conditions' => array('EducationGradeSubject.education_grade_id' => $gradeIds),
				'order' => array('EducationSubject.order')
			)
		);
		
		$list  = $this->formatArray($list);
		
		return $list;
	}
	
	public function groupSubjectsByGrade($subjectList) {
		$list = array(0 => array());
		foreach($subjectList as $subject) {
			$gradeId = $subject['education_grade_id'];
			$subjectId = $subject['education_subject_id'];
			
			$found = false;
			foreach($list[0] as $id => $item) {
				if(intval($subjectId) == intval($item['education_subject_id'])) {
					$found = true;
					break;
				}
			}
			if(!$found) {
				$list[0][$subject['id']] = array(
					'education_grade_subject_id' => $subject['id'],
					'education_subject_id' => $subjectId,
					'education_subject_name' => $subject['name']
				);
			}
			$list[$gradeId][$subject['id']] = array(
				'education_grade_subject_id' => $subject['id'],
				'education_subject_id' => $subjectId,
				'education_subject_name' => $subject['name']
			);
		}
		return $list;
	}
}
