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

class EducationGrade extends AppModel {
	public $actsAs = array(
		'ControllerAction2',
		'Reorder' => array('parentKey' => 'education_programme_id')
	);
	public $hasMany = array('EducationGradeSubject');
	public $belongsTo = array(
		'EducationProgramme',
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

	public $virtualFields = array(
		'programme_grade_name' => "SELECT CONCAT(`EducationProgramme`.`name`, ' - ', `EducationGrade`.`name`) from `education_programmes` AS `EducationProgramme` WHERE `EducationProgramme`.`id` = `EducationGrade.education_programme_id`"
	);
	
	public $_condition = 'education_programme_id';
	
	public function beforeAction() {
		parent::beforeAction();
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		$this->setVar('conditionId', $conditionId);
		
		$this->fields['order']['visible'] = false;
		$this->fields['education_programme_id']['type'] = 'disabled';
		
		if ($this->action == 'add') {
			$this->fields['order']['type'] = 'hidden';
			$this->fields['order']['visible'] = true;
			$this->fields['order']['value'] = 0;
			$this->fields['visible']['type'] = 'hidden';
			$this->fields['visible']['value'] = 1;
			$this->fields['education_programme_id']['type'] = 'hidden';
			$this->fields['education_programme_id']['value'] = $conditionId;
			$this->fields['education_programme'] = array(
				'visible' => true,
				'type' => 'disabled',
				'value' => $this->EducationProgramme->field('name', array('EducationProgramme.id' => $conditionId))
			);
			$this->setFieldOrder('education_programme', 0);
		} else {
			$this->fields['visible']['type'] = 'select';
			$this->fields['visible']['options'] = $this->controller->Option->get('yesno');
			$this->fields['education_programme_id']['dataModel'] = 'EducationProgramme';
			$this->fields['education_programme_id']['dataField'] = 'name';
		}
		$this->setFieldOrder('education_programme_id', 1);
		
		$this->Navigation->addCrumb('Education Grades');
		
		$this->setVar('selectedAction', 'EducationSystem');
		$this->setVar('_condition', $this->_condition);
	}
	
	public function index() {
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationProgramme->exists($conditionId)) {
			$contain = array(
				'EducationGradeSubject' => array(
					'EducationSubject' => array(
						'fields' => array(
							'EducationSubject.name' 
						)
					),
					'conditions'=>array('EducationGradeSubject.visible = 1')
				)
			);
			$this->contain($contain);
			$data = $this->findAllByEducationProgrammeId($conditionId, array(), array($this->alias.'.order' => 'ASC'));
			$programmeObj = $this->EducationProgramme->findById($conditionId);
			$levelObj = ClassRegistry::init('EducationLevel')->findById($programmeObj['EducationCycle']['education_level_id']);
			$paths = array();
			$paths[] = array(
				'name' => $levelObj['EducationSystem']['name'],
				'url' => array('action' => 'EducationSystem')
			);
			$paths[] = array(
				'name' => $levelObj['EducationLevel']['name'],
				'url' => array('action' => 'EducationLevel', 'education_system_id' => $levelObj['EducationSystem']['id'])
			);
			$paths[] = array(
				'name' => $programmeObj['EducationCycle']['name'],
				'url' => array('action' => 'EducationCycle', 'education_level_id' => $levelObj['EducationLevel']['id'])
			);
			$paths[] = array(
				'name' => $programmeObj['EducationProgramme']['name'],
				'url' => array('action' => 'EducationProgramme', 'education_cycle_id' => $programmeObj['EducationCycle']['id'])
			);
			$paths[] = array(
				'name' => '(' . __('Education Grades') . ')'
			);
			$this->setVar(compact('data', 'paths'));
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'EducationSystem'));
		}
	}
	
	public function add() {
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		$this->render = '../template/add';
		if($this->EducationProgramme->exists($conditionId)) {
			if($this->request->is(array('post', 'put'))) {
				$this->request->data[$this->alias]['order'] = $this->field('order', array(), 'order DESC') + 1;
				if ($this->save($this->request->data)) {
					$this->Message->alert('general.add.success');
					return $this->redirect(array('action' => get_class($this), $this->_condition => $conditionId));
				}
			}
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'EducationSystem'));
		}
	}
	
	public function view($id=0) {
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationProgramme->exists($conditionId)) {
			$contain = array(
				'EducationGradeSubject' => array(
					'EducationSubject' => array(
						'fields' => array(
							'EducationSubject.id', 'EducationSubject.name', 'EducationSubject.code' 
						)
					)
				),
				'EducationProgramme'
			);
			$this->contain($contain);
			$data = $this->findById($id);
			$this->fields['subject'] = array(
				'type' => 'element',
				'element' => '../Education/EducationGrade/subjects',
				'override' => true,
				'visible' => true
			);
			$this->setVar(compact('data'));
			$this->render = '../template/view';
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'EducationSystem'));
		}
	}
	
	public function edit($id=0) {
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationProgramme->exists($conditionId)) {
			$contain = array(
				'EducationGradeSubject' => array(
					'EducationSubject' => array(
						'fields' => array(
							'EducationSubject.id', 'EducationSubject.name', 'EducationSubject.code' 
						)
					)
				),
				'EducationProgramme'
			);
			$this->contain($contain);
			$data = $this->findById($id);
			if(!empty($data)) {
				$this->fields['subject'] = array(
					'type' => 'element',
					'element' => '../Education/EducationGrade/subjects',
					'override' => true,
					'visible' => true
				);

				$subjectOptions = $this->EducationGradeSubject->EducationSubject->getSubjectOptions();
				$subjectOptions = $this->controller->Option->prependLabel($subjectOptions, $this->alias . '.add_subject');
				
				if($this->request->is(array('post', 'put'))) {
					$postData = $this->request->data;

					if ($postData['submit'] == 'add') {
						if (isset($postData[$this->alias]['education_subject_id']) && !empty($postData[$this->alias]['education_subject_id'])) {
							$educationSubjectId = $postData[$this->alias]['education_subject_id'];

							$this->EducationGradeSubject->EducationSubject->recursive = -1;
							$subjectObj = $this->EducationGradeSubject->EducationSubject->findById($educationSubjectId);

							$newRow = array(
								'id' => false,
								'education_subject_id' => $educationSubjectId,
								'education_grade_id' => $id,
			                    'hours_required' => 0,
			                    'visible' => 1,
								'EducationSubject' => $subjectObj['EducationSubject']
							);

							// search if the new subject was previously added before
							foreach ($data['EducationGradeSubject'] as $row) {
								if ($row['education_subject_id'] == $educationSubjectId) {
									$newRow['id'] = $row['id'];
								}
							}

							$this->request->data['EducationGradeSubject'][] = $newRow;
							$this->request->data[$this->alias]['education_programme_id'] = $this->request->data['EducationProgramme']['id'];

							unset($this->request->data[$this->alias]['education_subject_id']);
							unset($this->request->data['submit']);
						}
					} else if ($postData['submit'] = 'Save') {
						$this->EducationGradeSubject->updateAll(
							array('EducationGradeSubject.visible' => 0),
							array('EducationGradeSubject.education_grade_id' => $id)
						);

						// must unset $postData['EducationProgramme'] to avoid fail to save as we are not creating or updating the EducationProgramme record.
						unset($postData['EducationProgramme']);
						if ($this->saveAll($postData)) {
							$this->Message->alert('general.edit.success');
							$this->redirect(array('action' => $this->alias, 'view', $this->_condition => $conditionId, $id));
						} else {
							$this->Message->alert('general.edit.failed');
						}
					}
				} else {
					$this->request->data = $data;
				}

				// removing existing students from StudentOptions
				foreach ($this->request->data['EducationGradeSubject'] as $row) {
					if (array_key_exists($row['education_subject_id'], $subjectOptions)) {
						unset($subjectOptions[$row['education_subject_id']]);
					}
				}

				$this->setVar(compact('subjectOptions'));
				$this->render = '../template/edit';
			} else {
				$this->Message->alert('general.notExists');
				return $this->redirect(array('action' => get_class($this), $this->_condition => $conditionId));
			}
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'EducationSystem'));
		}
	}
	
	// Used by InstitutionSiteFee
	public function getGradeOptionsByInstitutionAndAcademicPeriod($institutionSiteId, $academicPeriodId, $visible = false) {
		$institutionSiteProgrammeConditions = array(
			'InstitutionSiteProgramme.education_programme_id = EducationGrade.education_programme_id',
			'InstitutionSiteProgramme.institution_site_id = ' . $institutionSiteId
		);
		$institutionSiteProgrammeConditions = ClassRegistry::init('InstitutionSiteProgramme')->getConditionsByAcademicPeriodId($academicPeriodId, $institutionSiteProgrammeConditions);

		$conditions = array();
		if ($visible !== false) {
			$conditions['EducationProgramme.visible'] = 1;
			$conditions['EducationGrade.visible'] = 1;
		}
		
		$list = $this->find('all', array(
			'recursive' => 0,
			'joins' => array(
				array(
					'table' => 'institution_site_programmes',
					'alias' => 'InstitutionSiteProgramme',
					'conditions' => $institutionSiteProgrammeConditions
				)
			),
			'conditions' => $conditions,
			'order' => array('EducationProgramme.order', 'EducationGrade.order')
		));
		
		$data = array();
		foreach ($list as $obj) {
			$grade = $obj['EducationGrade'];
			$data[$grade['id']] = $obj['EducationProgramme']['name'] . ' - ' . $grade['name'];
		}
		
		return $data;
	}
	
	public function findListAsSubgroups() {
		return $this->findList(true);
	}
	
	public function getGradeOptions($programmeId, $exclude=array(), $onlyVisible=false) {
		$conditions = array('EducationGrade.education_programme_id' => $programmeId);
		
		if(!empty($exclude)) {
			$conditions['EducationGrade.id NOT'] = $exclude;
		}
		
		if($onlyVisible) {
			$conditions['EducationGrade.visible'] = $onlyVisible;
		}
		
		$options = array(
			'recursive' => -1,
			'fields' => array('EducationGrade.id', 'EducationGrade.name'),
			'conditions' => $conditions,
			'order' => array('EducationGrade.order')
		);
		$data = $this->find('list', $options);
		return $data;
	}
}
