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
		$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
		$this->Navigation->addCrumb('Education', array('controller' => 'Education', 'action' => 'index'));
		
		$actionOptions = array(
			//'index' => __('Education Structure'),
			'systems' => __('Education Systems'),
			'subjects' => __('Education Subjects'),
			'certifications' => __('Certifications'),
			'fields' => __('Field of Study'),
			'orientations' => __('Programme Orientation')
		);
		$this->set('actionOptions', $actionOptions);
	}
	
	public function index() {
		return $this->redirect(array('action' => 'systems'));
		//$this->Navigation->addCrumb('Education Structure');
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