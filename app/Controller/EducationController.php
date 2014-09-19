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
	public $modules = array(
		'EducationSystem',
		'EducationLevel',
		'EducationCycle',
		'EducationProgramme',
		'EducationGrade',
		'EducationGradeSubject',
		'EducationSubject',
		'EducationCertification',
		'EducationProgrammeOrientation',
		'EducationFieldOfStudy'
	);
	
	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
		$this->Navigation->addCrumb('Education', array('controller' => 'Education', 'action' => 'index'));
		
		$actionOptions = array(
			//'index' => __('Education Structure'),
			'EducationSystem' => __('Education Systems'),
			'EducationSubject' => __('Education Subjects'),
			'EducationCertification' => __('Certifications'),
			'EducationFieldOfStudy' => __('Field of Study'),
			'EducationProgrammeOrientation' => __('Programme Orientations')
		);
		$this->set('actionOptions', $actionOptions);
	}
	
	public function index() {
		return $this->redirect(array('action' => 'EducationSystem'));
		//$this->Navigation->addCrumb('Education Structure');
	}
	
	public function reorder($moduleKey=null) {
		if(!is_null($moduleKey) && in_array($moduleKey, $this->modules)) {
			$model = $moduleKey;
			$modelObj = ClassRegistry::init($model);
			$conditions = $this->params->named;
			$data = $modelObj->find('all', array(
				'conditions' => $conditions,
				'order' => array($model.'.order')
			));
			
			$this->set(compact('data', 'model', 'conditions'));
			$this->Navigation->addCrumb('Reorder');
		} else {
			return $this->redirect(array('action' => 'index'));
		}
	}
	
	public function move($moduleKey=null) {
		if ($this->request->is('post') || $this->request->is('put')) {
			$data = $this->request->data;
			$modelObj = ClassRegistry::init($moduleKey);
			$conditions = $this->params->named;
			$modelObj->moveOrder($data, $conditions);
			$redirect = array('action' => 'reorder', $moduleKey);
			$redirect = array_merge($redirect, $conditions);
			return $this->redirect($redirect);
		}
	}
}