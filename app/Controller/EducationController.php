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

class EducationController extends AppController {
	public $uses = array('EducationSystem', 'EducationLevel', 'EducationProgramme', 'EducationProgrammeOrientation', 'EducationGrade', 'EducationGradeSubject');
	
	public $modules = array(
		'systems' => 'EducationSystem',
		'levels' => 'EducationLevel',
		'cycles' => 'EducationCycle',
		'programmes' => 'EducationProgramme',
		'gradeSubjects' => 'EducationGradeSubject',
		'grades' => 'EducationGrade',
		'subjects' => 'EducationSubject',
		'certifications' => 'EducationCertification',
		'orientations' => 'EducationProgrammeOrientation',
		'fields' => 'EducationFieldOfStudy'
	);
	
	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Setup', 'action' => 'index'));
		$this->Navigation->addCrumb('Education', array('controller' => 'Education', 'action' => 'index'));
		
		$actionOptions = array(
			'index' => __('Education Structure'),
			'systems' => __('Education Systems'),
			'subjects' => __('Education Subjects'),
			'certifications' => __('Certifications'),
			'fields' => __('Field of Study'),
			'orientations' => __('Programme Orientation')
		);
		$this->set('actionOptions', $actionOptions);
	}
	
	public function index() {
		$conditions = array('conditions' => array('visible' => 1));
		$systemList = $this->EducationSystem->findList($conditions);
		$this->set('header', __('Education Structure'));
		$this->set('selectedAction', 'index');
		if(sizeof($systemList) > 0) {
			$systemId = isset($this->params['pass'][0]) ? $this->params['pass'][0] : key($systemList);
			
			$levelConditions = $conditions;
			$levelConditions['conditions']['education_system_id'] = $systemId;
			$levelList = $this->EducationLevel->findList($levelConditions);
			$orientationList = $this->EducationProgrammeOrientation->findList($conditions);
			
			$structure = array();
			
			foreach($levelList as $levelId => $levelName) {
				$programmes = $this->EducationProgramme->find('all', array(
						'recursive' => 0,
						'fields' => array(
							'EducationProgramme.id', 'EducationProgramme.name', 'EducationProgramme.duration',
							'EducationCycle.name', 'EducationFieldOfStudy.name', 'EducationCertification.name',
							'EducationFieldOfStudy.education_programme_orientation_id'
						),
						'conditions' => array(
							'EducationCycle.education_level_id' => $levelId,
							'EducationCycle.visible' => 1,
							'EducationProgramme.visible' => 1
						),
						'order' => array('EducationCycle.order', 'EducationProgramme.order')
					)
				);
				
				foreach($programmes as $list) {
					$programme = $list['EducationProgramme'];
					$programmeId = $programme['id'];
					$gradeConditions = array('conditions' => array('EducationGrade.education_programme_id' => $programmeId));
					$gradeList = $this->EducationGrade->findList($gradeConditions);
					
					$subjectList = $this->EducationGradeSubject->findSubjectsByGrades(array_keys($gradeList));
					$gradeSubjects = $this->EducationGradeSubject->groupSubjectsByGrade($subjectList);
					
					foreach($gradeList as $key => $val) {
						if(!isset($gradeSubjects[$key])) {
							$gradeSubjects[$key] = array();
						}
					}
					
					$structure[$levelName][] = array(
						'id' => $programmeId,
						'name' => $programme['name'],
						'cycle_name' => $list['EducationCycle']['name'],
						'orientation' => $orientationList[$list['EducationFieldOfStudy']['education_programme_orientation_id']],
						'field' => $list['EducationFieldOfStudy']['name'],
						'duration' => $programme['duration'],
						'certificate' => $list['EducationCertification']['name'],
						'grades' => $gradeList,
						'subjects' => $gradeSubjects
					);
				}
			}
			
			if(empty($levelList)) {
				$this->Utility->alert($this->Utility->getMessage('EDUCATION_NO_LEVEL'), array('type' => 'info', 'dismissOnClick' => false));
			}
			
			$this->set('systems', $systemList);
			$this->set('levels', $levelList);
			$this->set('structure', $structure);
			$this->set('selectedSystem', $systemId);
		} else {
			$this->Utility->alert($this->Utility->getMessage('EDUCATION_NO_SYSTEM'), array('type' => 'info', 'dismissOnClick' => false));
		}
	}
	
	public function reorder() {
		$moduleKey = isset($this->params->pass[0]) ? $this->params->pass[0] : null;
		
		if(!is_null($moduleKey) && array_key_exists($moduleKey, $this->modules)) {
			$model = $this->modules[$moduleKey];
			$modelObj = ClassRegistry::init($model);
			$header = __($modelObj->_header);
			$_action = $modelObj->_action;
			$conditions = $this->params->named;
			$data = $modelObj->find('all', array(
				'conditions' => $conditions,
				'order' => array($model.'.order')
			));
			
			$this->set(compact('data', 'model', 'header', '_action', 'conditions'));
			$this->Navigation->addCrumb($modelObj->_header);
		} else {
			return $this->redirect(array('action' => 'index'));
		}
	}
	
	public function move() {
		$moduleKey = isset($this->params->pass[0]) ? $this->params->pass[0] : null;
		if ($this->request->is('post') || $this->request->is('put')) {
			$data = $this->request->data;
			$model = $this->modules[$moduleKey];
			$modelObj = ClassRegistry::init($model);
			
			$conditions = $this->params->named;
			$modelObj->moveOrder($data, $conditions);
			$redirect = array('action' => 'reorder', $moduleKey);
			$redirect = array_merge($redirect, $conditions);
			return $this->redirect($redirect);
		}
	}
}