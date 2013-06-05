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
	public $uses = Array(
		'EducationSystem',
		'EducationLevel',
		'EducationLevelIsced',
		'EducationCycle',
		'EducationProgramme',
		'EducationProgrammeOrientation',
		'EducationFieldOfStudy',
		'EducationCertification',
		'EducationGrade',
		'EducationGradeSubject',
		'EducationSubject'
	);
	
	public $views = array(
		'EducationSystem' => array('view' => false, 'edit' => false, 'nameEditable' => false),
		'EducationLevel' => array('view' => true, 'edit' => true, 'nameEditable' => false),
		'EducationCycle' => array('view' => true, 'edit' => true, 'nameEditable' => false),
		'EducationProgramme' => array('view' => true, 'edit' => true, 'nameEditable' => false),
		'EducationGrade' => array('view' => true, 'edit' => true, 'nameEditable' => false),
		'EducationGradeSubject' => array('view' => true, 'edit' => true, 'nameEditable' => false),
		'EducationProgrammeOrientation' => array('view' => false, 'edit' => false, 'nameEditable' => false, 'addAllowed' => false),
		'EducationFieldOfStudy' => array('view' => true, 'edit' => true, 'nameEditable' => true),
		'EducationCertification' => array('view' => false, 'edit' => false, 'nameEditable' => true),
		'EducationSubject' => array('view' => true, 'edit' => true, 'nameEditable' => true)
	);
	
	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Settings';
		$this->Navigation->addCrumb('Settings', array('controller' => 'Setup', 'action' => 'index'));
		$this->Navigation->addCrumb('Education', array('controller' => 'Education', 'action' => 'index'));
		
		if($this->action === 'index') {
			$this->Navigation->addCrumb('Structure');
		} else {
			$this->Navigation->addCrumb('Setup', array('controller' => 'Education', 'action' => 'setup'));
		}
		
		$setupOptions = array(
			'System' => __('System'),
			'Level' => __('Level'),
			'Cycle' => __('Cycle'),
			'Programme' => __('Programme'),
			'ProgrammeOrientation' => __('Orientation'),
			'FieldOfStudy' => __('Field of Study'),
			'Certification' => __('Certification'),
			'Subject' => __('Subject')
		);
		$this->set('setupOptions', $setupOptions);
	}
	
	public function index() {
		$conditions = array('conditions' => array('visible' => 1));
		$systemList = $this->EducationSystem->findList($conditions);
		
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
		// Checking if user has access to _view for setup
		$_view_setup = false;
		if($this->AccessControl->check($this->params['controller'], 'setup')) {
			$_view_setup = true;
		}
		$this->set('_view_setup', $_view_setup);
		// End Access Control
	}
	
	private function processSave($data, $model, $action) {
		if(isset($data[$model])) {
			$dataObjects = $data[$model];
			foreach($dataObjects as $key => $obj) {
				if(isset($obj['name'])) {
					if(strlen($obj['name']) == 0) {
						unset($dataObjects[$key]);
					} else {
						if($model == 'EducationSystem') {
							$name = preg_replace('/\s+/', ' ', trim($obj['name']));
							$find = $this->EducationSystem->find('first', array(
								'recursive' => -1,
								'conditions' => array('UPPER(EducationSystem.name)' => strtoupper($name))
							));
							if($find) { // remove duplicate names from adding into database
								unset($dataObjects[$key]);
							}
						}
					}
				}
			}
			$this->{$model}->saveMany($dataObjects);
			$this->Utility->alert($this->Utility->getMessage('UPDATE_SUCCESS'));
		}
		$this->redirect($action);
	}
	
	public function setup() {
		if(!isset($this->params['pass'][0])) {
			$this->redirect(array('action' => $this->action, 'System'));
		} else {
			$option = $this->params['pass'][0];
			if(!method_exists($this, $this->action . $option)) {
				$option = 'System';
			}
			$method = $this->action . $option;
			$title = Inflector::humanize(Inflector::underscore($option));
			$this->set('pageTitle', $title);
			$this->set('selectedOption', $option);
			$this->Navigation->addCrumb($title);
			call_user_func(array($this, $method));
			if($this->views['Education'.$option]['view']) {
				$view = Inflector::underscore($this->action . $option);
				$this->render($view);
			}
		}
	}
	
	public function setupEdit() {
		if(!isset($this->params['pass'][0])) {
			$this->redirect(array('action' => $this->action, 'System'));
		} else {
			$option = $this->params['pass'][0];
			$model = 'Education'.$option;
			
			if($this->request->is('get')) {
				if(!method_exists($this, 'setup' . $option)) {
					$option = 'System';
				}
				$method = 'setup' . $option;
				$model = 'Education'.$option;
				$title = Inflector::humanize(Inflector::underscore($option));
				$this->set('pageTitle', $title);
				$this->set('model', $model);
				$this->set('isNameEditable', $this->views[$model]['nameEditable']);
				$this->set('addAllowed', !isset($this->views[$model]['addAllowed']));
				$this->set('selectedOption', $option);
				$this->Navigation->addCrumb($title);
				call_user_func(array($this, $method));
				if($this->views[$model]['edit']) {
					$view = Inflector::underscore('setup' . $option) . '_edit';
					$this->render($view);
				}
			} else {
				$url = array_merge(array('action' => 'setup'), $this->params['pass']);
				$this->processSave($this->data, $model, $url);
			}
		}
	}
	
	public function setupAdd() {
		$this->layout = 'ajax';
		$iscedList = $this->EducationLevelIsced->getList();
		$orientationList = $this->EducationProgrammeOrientation->find('list');
		
		// For adding education grade subjects
		if(isset($this->params->query['education_grade_id'])) {
			$gradeId = $this->params->query['education_grade_id'];
			$subjectIds = $this->params->query['subjectIds'];
			
			$list = $this->EducationSubject->find('all', array(
				'recursive' => 0,
				'conditions' => array('EducationSubject.id NOT' => $subjectIds),
				'order' => 'order'
			));
			
			$subjectList = array();
			foreach($list as $obj) {
				$subject = $obj['EducationSubject'];
				$subjectList[$subject['id']] = $subject['code'] . ' - ' . $subject['name'];
			}
			
			if(!empty($subjectList)) {
				$this->set('subjects', $subjectList);
			} else {
				$this->render('/Layouts/ajax');
			}
		}
		// end education grade subjects
		
		$this->set('isced', $iscedList);
		$this->set('orientation', $orientationList);
		$this->set('params', $this->params->query);
	}
	
	private function setupSystem() {
		$this->set('list', $this->EducationSystem->findOptions());
	}
	
	private function setupLevel() {
		$list = array();
		$conditions = array('conditions' => array('EducationSystem.visible' => 1));
		$systemList = $this->EducationSystem->findOptions($conditions);
		$iscedList = $this->EducationLevelIsced->find('list');
		
		foreach($systemList as $system) {
			$systemName = $system['name'];
			
			$levelList = $this->EducationLevel->findOptions(array('conditions' => array('education_system_id' => $system['id'])));
			foreach($levelList as &$level) {
				$level['isced_name'] = $iscedList[$level['education_level_isced_id']];
			}
			$levelList['id'] = $system['id'];
			$list[$systemName] = $levelList;
		}
		$this->set('list', $list);
		$this->set('isced', $this->EducationLevelIsced->getList());
	}
	
	private function setupCycle() {
		$list = array();
		$conditions = array('conditions' => array('EducationSystem.visible' => 1));
		$systemList = $this->EducationSystem->findOptions($conditions);
		
		foreach($systemList as $systemObj) {
			$systemId = $systemObj['id'];
			if(!isset($list[$systemId])) { 
				$list[$systemId] = array('name' => $systemObj['name'], 'levels' => array());
			}
			$conditions = array('conditions' => array('EducationLevel.visible' => 1, 'education_system_id' => $systemId));
			$levelList = $this->EducationLevel->findOptions($conditions);
			foreach($levelList as $levelObj) {
				$levelId = $levelObj['id'];
				$cycleList = $this->EducationCycle->findOptions(array('conditions' => array('education_level_id' => $levelId)));
				$list[$systemId]['levels'][$levelId] = array('name' => $levelObj['name'], 'cycles' => $cycleList);
			}
		}
		$this->set('list', $list);
	}
	
	private function setupProgramme() {
		$list = array();
		$conditions = array('conditions' => array('EducationSystem.visible' => 1));
		$systemList = $this->EducationSystem->findOptions($conditions);
		
		foreach($systemList as $systemObj) {
			$systemId = $systemObj['id'];
			if(!isset($list[$systemId])) { 
				$list[$systemId] = array('name' => $systemObj['name'], 'levels' => array());
			}
			$conditions = array('conditions' => array('EducationLevel.visible' => 1, 'education_system_id' => $systemId));
			$levelList = $this->EducationLevel->findOptions($conditions);
			
			foreach($levelList as $levelObj) {
				$levelId = $levelObj['id'];
				
				$conditions = array('conditions' => array('EducationCycle.visible' => 1, 'education_level_id' => $levelId));
				$cycleList = $this->EducationCycle->findOptions($conditions);
				
				foreach($cycleList as $cycleObj) {
					$cycleId = $cycleObj['id'];
					if(!isset($list[$systemId]['cycles'][$cycleId])) {
						$list[$systemId]['cycles'][$cycleId] = array('name' => $levelObj['name'] . ' - ' . $cycleObj['name'], 'programmes' => array());
					}
					$programmeList = $this->EducationProgramme->findOptions(array('conditions' => array('education_cycle_id' => $cycleObj['id'])));
					
					foreach($programmeList as $programme) {
						$list[$systemId]['cycles'][$cycleId]['programmes'][] = $programme;
					}
				}
			}
		}
		$this->set('list', $list);
	}
	
	public function setupProgrammeAddDialog() {
		if($this->request->is('get')) {
			$this->layout = 'ajax';
			$model = 'EducationProgramme';
			$count = $this->params->query['count'];
			$cycleId = $this->params->query['education_cycle_id'];
			$cycleObj = $this->EducationCycle->find('first', array('conditions' => array('EducationCycle.id' => $cycleId)));
			$levelObj = $this->EducationLevel->find('first', array('conditions' => array('EducationLevel.id' => $cycleObj['EducationCycle']['education_level_id'])));
			
			$fieldList = $this->EducationFieldOfStudy->findList();
			$certificationList = $this->EducationCertification->findList();
			
			$systemName = $levelObj['EducationSystem']['name'];
			$levelName = $levelObj['EducationLevel']['name'];
			$cycleName = $cycleObj['EducationCycle']['name'];
			$this->set('model', $model);
			$this->set('count', $count);
			$this->set('action', $this->action);
			$this->set('systemName', $systemName);
			$this->set('levelName', $levelName);
			$this->set('cycleName', $cycleName);
			$this->set('cycleId', $cycleId);
			$this->set('fieldList', $fieldList);
			$this->set('certificationList', $certificationList);
		} else {
			$programme = $this->EducationProgramme->save($this->data);
			$this->Utility->alert($this->Utility->getMessage('EDUCATION_PROGRAMME_ADDED'));
			$this->redirect(array('action' => 'setupEdit', 'Grade', $programme['EducationProgramme']['id']));
		}
	}
	
	private function setupProgrammeOrientation() {
		$this->set('list', $this->EducationProgrammeOrientation->findOptions());
	}
	
	private function setupGrade() {
		if(!isset($this->params['pass'][1])) {
			$this->redirect(array('controller' => 'Education', 'action' => 'setup', 'Programme'));
		} else {
			$programmeId = $this->params['pass'][1];
			$programmeName = $this->EducationProgramme->field('name', array('EducationProgramme.id' => $programmeId));
			$conditions = array('conditions' => array('education_programme_id' => $programmeId));
			$list = $this->EducationGrade->findOptions($conditions);
			$this->set('programmeId', $programmeId);
			$this->set('programmeName', $programmeName);
			$this->set('list', $list);
			$this->set('selectedOption', 'Programme');
		}
	}
	
	private function setupGradeSubject() {
		if(!isset($this->params['pass'][1]) && !isset($this->params['pass'][2])) {
			$this->redirect(array('controller' => 'Education', 'action' => 'setup', 'Programme'));
		} else {
			$programmeId = $this->params['pass'][1];
			$gradeId = $this->params['pass'][2];
			$programmeName = $this->EducationProgramme->field('name', array('EducationProgramme.id' => $programmeId));
			$gradeName = $this->EducationGrade->field('name', array('EducationGrade.id' => $gradeId));
			$list = $this->EducationGradeSubject->find('all', array(
				'conditions' => array('education_grade_id' => $gradeId),
				'order' => array('EducationGradeSubject.order', 'EducationSubject.order')
			));
			
			$this->set('gradeId', $gradeId);
			$this->set('gradeName', $gradeName);
			$this->set('programmeId', $programmeId);
			$this->set('programmeName', $programmeName);
			$this->set('list', $list);
			$this->set('selectedOption', 'Programme');
		}
	}
	
	private function setupFieldOfStudy() {
		$list = $this->EducationFieldOfStudy->findOptions();
		$orientation = $this->EducationProgrammeOrientation->find('list');
		$this->set('list', $list);
		$this->set('orientation', $orientation);
	}
	
	private function setupCertification() {
		$list = $this->EducationCertification->findOptions();
		unset($list[0]);
		$this->set('list', $list);
	}
	
	private function setupSubject() {
		$this->set('list', $this->EducationSubject->findOptions());
	}
}