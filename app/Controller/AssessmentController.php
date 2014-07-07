<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-14

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

class AssessmentController extends AppController {
	public $uses = array(
		'EducationProgramme',
		'EducationGrade',
		'EducationGradeSubject',
		'EducationSubject',
		'AssessmentItem',
		'AssessmentItemType'
	);
	
	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
		if($this->action === 'index') {
			$this->Navigation->addCrumb('National Assessments');
		} else {
			$this->Navigation->addCrumb('National Assessments', array('controller' => 'Assessment', 'action' => 'index'));
		}
	}
	
	public function loadGradeList() {
		$this->autoRender = false;
		$programmeId = $this->params->query['programmeId'];
		$data = $this->EducationGrade->getGradeOptions($programmeId, null, true);
		
		$html = '<option value="%d">%s</option>';
		$options = '';
		
		if(!empty($data)) {
			$options .= sprintf($html, '', '-- ' . __('Select Grade') . ' --');
			foreach($data as $id => $name) {
				$options .= sprintf($html, $id, $name);
			}
		} else {
			$options .= sprintf($html, '', '-- ' . __('No Grade Available') . ' --');
		}
		return $options;
	}
	
	public function loadSubjectList() {
		$this->layout = 'ajax';
		$gradeId = $this->params->query['gradeId'];
		$data = $this->EducationGradeSubject->findSubjectsByGrades($gradeId);
		
		$this->set('data', $data);
		$this->render('subject_list');
	}
	
	public function index() {
		$programmeOptions = $this->EducationProgramme->getProgrammeOptions(false);
		$selectedProgramme = 0;
		$data = array();
		$type = $this->AssessmentItemType->type['OFFICIAL'];
		if(!empty($programmeOptions)) {
			$selectedProgramme = isset($this->params['pass'][0]) ? $this->params['pass'][0] : key($programmeOptions);
			$list = $this->AssessmentItemType->getAssessmentByTypeAndProgramme($type, $selectedProgramme);
			if(!empty($list)) {
				$data = $this->AssessmentItemType->groupByGrades($list);
			} else {
				$this->Utility->alert($this->Utility->getMessage('ASSESSMENT_NO_ASSESSMENT'), array('type' => 'info'));
			}
		} else {
			$this->Utility->alert($this->Utility->getMessage('ASSESSMENT_NO_PROGRAMME'), array('type' => 'info'));
		}
		$this->set('data', $data);
		$this->set('programmeOptions', $programmeOptions);
		$this->set('selectedProgramme', $selectedProgramme);
		$this->set('type', $type);
    }
	
	public function indexEdit() {
		$this->Navigation->addCrumb('Edit');
		$programmeOptions = $this->EducationProgramme->getProgrammeOptions();
		$data = array();
		$type = $this->AssessmentItemType->type['OFFICIAL'];
		if(!empty($programmeOptions)) {
			$selectedProgramme = isset($this->params['pass'][0]) ? $this->params['pass'][0] : key($programmeOptions);
			if($this->request->is('post')) {
				$assessment = $this->data['AssessmentItemType'];
				if($this->AssessmentItemType->saveMany($assessment, array('validate' => false))) {
					$this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
				} else {
					$this->Utility->alert($this->Utility->getMessage('ERROR_UNEXPECTED'), array('type' => 'error'));
				}
				$this->redirect(array('action' => 'index', $selectedProgramme));
			}
			$list = $this->AssessmentItemType->getAssessmentByTypeAndProgramme($type, $selectedProgramme);
			if(!empty($list)) {
				$data = $this->AssessmentItemType->groupByGrades($list);
			} else {
				$this->redirect(array('action' => 'index', $selectedProgramme));
			}
		} else {
			$this->redirect(array('action' => 'index', $selectedProgramme));
		}
		$this->set('data', $data);
		$this->set('programmeOptions', $programmeOptions);
		$this->set('selectedProgramme', $selectedProgramme);
		$this->set('type', $type);
	}
	
	public function assessmentsAdd() {
		$this->Navigation->addCrumb('Add');
		$programmeId = 0;
		$gradeId = 0;
		$selectedProgramme = 0;
		$selectedGrade = '';
		$items = array();
		if($this->request->is('post')) {
			$programmeId = $this->data['AssessmentItemType']['education_programme_id'];
			$gradeId = $this->data['AssessmentItemType']['education_grade_id'];
			$assessment = $this->data['AssessmentItemType'];
			$this->AssessmentItemType->set($assessment);
			if(isset($this->data['AssessmentItem'])) {
				$items = $this->data['AssessmentItem'];
			}
			if($this->AssessmentItemType->validates()) {
				$order = 1;
				$type = $this->AssessmentItemType->type['OFFICIAL'];
				$list = $this->AssessmentItemType->getAssessmentByTypeAndGrade($type, $gradeId);
				if(!empty($list)) {
					$last = array_pop($list);
					$order = $last['AssessmentItemType']['order'] + 1;
				}
				$assessment['type'] = $type;
				$assessment['order'] = $order;
				$obj = $this->AssessmentItemType->save($assessment);
				if($obj) {
					$assessmentId = $obj['AssessmentItemType']['id'];
					if(!empty($items)) {
						foreach($items as $i => $val) {
							if(isset($val['visible']) && $val['visible']==1) {
								$val['assessment_item_type_id'] = $assessmentId;
								$this->AssessmentItem->create();
								$this->AssessmentItem->save($val);
							}
						}
					}
					$this->redirect(array('action' => 'assessmentsView', $assessmentId));
				}
			}
		}
		$programmeOptions = $this->EducationProgramme->getProgrammeOptions();
		$gradeOptions = array();
		if(!empty($programmeOptions)) {
			if($programmeId == 0) {
				$programmeId = key($programmeOptions);
			}
			$gradeOptions = $this->EducationGrade->getGradeOptions($programmeId, null, true);
		} else {
			$this->Utility->alert($this->Utility->getMessage('EDUCATION_INACTIVE'), array('type' => 'warn'));
		}
		
		$this->set('selectedProgramme', $programmeId);
		$this->set('selectedGrade', $gradeId);
		$this->set('programmeOptions', $programmeOptions);
		$this->set('gradeOptions', $gradeOptions);
		$this->set('items', $items);
	}
	
	public function assessmentsView() {	
		if(isset($this->params['pass'][0])) {
			$this->Navigation->addCrumb('Details');
			$assessmentId = $this->params['pass'][0];
			$data = $this->AssessmentItemType->getAssessment($assessmentId);
			$this->set('data', $data);
		} else {
			$this->redirect(array('action' => 'index'));
		}
	}
	
	public function assessmentsEdit() {	
		if(isset($this->params['pass'][0])) {
			$this->Navigation->addCrumb('Edit Details');
			$assessmentId = $this->params['pass'][0];
			$data = $this->AssessmentItemType->getAssessment($assessmentId);
			$items = array();
			if($this->request->is('post')) {
				$assessment = $this->data['AssessmentItemType'];
				$assessment['education_grade_id'] = $data['education_grade_id'];
				$data = array_merge($data, $assessment);
				$this->AssessmentItemType->set($assessment);
				if(isset($this->data['AssessmentItem'])) {
					$items = $this->data['AssessmentItem'];
				}
				if($this->AssessmentItemType->validates()) {
					$obj = $this->AssessmentItemType->save($assessment);
					if($obj) {
						$assessmentId = $obj['AssessmentItemType']['id'];
						if(!empty($items)) {
							foreach($items as $i => $val) {
								if(!isset($val['visible'])) {
									$val['visible'] = 0;
								}
								if($val['id'] > 0) {
									$this->AssessmentItem->save($val);
								} else {
									if($val['visible']==1) {
										$this->AssessmentItem->create();
										$this->AssessmentItem->save($val);
									}
								}
							}
						}
						$this->redirect(array('action' => 'assessmentsView', $assessmentId));
					}
				}
				$data['AssessmentItem'] = $items;
			}
			$this->set('data', $data);
		} else {
			$this->redirect(array('action' => 'index'));
		}
	}
	
	public function move() {
		if ($this->request->is('post') || $this->request->is('put')) {
			$data = $this->request->data;
			if(isset($this->params['pass'][0])){
				$programmeId = $this->params['pass'][0];
			}else{
				return $this->redirect(array('action' => 'index'));
			}
			
			$conditions = array(
				'education_grade_id' => $data['AssessmentItemType']['gradeId'],
				'type' => 1
			);
			
			$modelObj = ClassRegistry::init('AssessmentItemType');
			$modelObj->moveOrder($data, $conditions);

			$this->redirect(array('action' => 'indexEdit', $programmeId));
		}
	}
}