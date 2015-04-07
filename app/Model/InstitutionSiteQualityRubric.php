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

class InstitutionSiteQualityRubric extends AppModel {
	public $belongsTo = array(
		'RubricTemplate' => array(
            'className' => 'Quality.RubricTemplate',
            'foreignKey' => 'rubric_template_id'
        ),
        'AcademicPeriod',
		'EducationGrade',
		'InstitutionSiteSection',
		'InstitutionSiteClass',
		'Staff.Staff',
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

	public $hasMany = array(
        'InstitutionSiteQualityRubricAnswer' => array(
            'className' => 'InstitutionSiteQualityRubricAnswer',
			'dependent' => true
        )
    );

	public function beforeAction() {
		$named = $this->controller->request->params['named'];
		$selectedAction = isset($named['status']) ? $named['status'] : 0;

		$tabsElement = '../InstitutionSites/InstitutionSiteQualityRubric/nav_tabs';

		$contentHeader = __('Rubrics');
		$this->controller->set(compact('contentHeader', 'tabsElement', 'selectedAction'));
	}

	public function index() {
		$this->Navigation->addCrumb('Rubrics');

		$named = $this->controller->params->named;
		$status = isset($named['status']) ? $named['status'] : 0;
		$todayDate = date("Y-m-d");
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$userId = $this->Session->read('Auth.User.id');
		$SecurityGroupUser = ClassRegistry::init('SecurityGroupUser');
		$securityRoles = $SecurityGroupUser->getRolesByUserId($userId);
		$userRoles = array();
		foreach ($securityRoles as $key => $securityRole) {
			$userRoles[$securityRole['SecurityRole']['id']] = $securityRole['SecurityRole']['id'];
		}

		// Find all templates
		$this->RubricTemplate->contain();
		$templates = $this->RubricTemplate->find('all', array(
			'order' => array(
				'RubricTemplate.name'
			)
		));

		$QualityStatus = $this->RubricTemplate->QualityStatus;
		$InstitutionSiteProgramme = $this->EducationGrade->EducationProgramme->InstitutionSiteProgramme;
		$InstitutionSiteGrade = $this->EducationGrade->EducationProgramme->InstitutionSiteProgramme->InstitutionSiteGrade;
		$InstitutionSiteClass = ClassRegistry::init('InstitutionSiteClass');
		$InstitutionSiteSectionClass = ClassRegistry::init('InstitutionSiteSectionClass');
		
		// for each template, find available quality statuses
		foreach ($templates as $i => $template) {
			$templateId = $template['RubricTemplate']['id'];
			$QualityStatus->contain('AcademicPeriod');
			$qualityStatuses = $QualityStatus->find('list', array(
				'joins' => array(
					array(
						'table' => 'quality_status_roles',
						'alias' => 'QualityStatusRole',
						'conditions' => array(
							'QualityStatusRole.quality_status_id = QualityStatus.id',
							'QualityStatusRole.security_role_id' => $userRoles
						)
					)
				),
				'conditions' => array(
					'QualityStatus.rubric_template_id' => $templateId,
					'QualityStatus.date_disabled >=' => $todayDate
				)
			));

			foreach ($qualityStatuses as $qualityStatus) {
				$periods = $QualityStatus->AcademicPeriod->find('all', array(
					'fields' => array(
						'AcademicPeriod.id', 'AcademicPeriod.name', 'QualityStatus.date_disabled'
					),
					'joins' => array(
						array(
							'table' => 'quality_status_periods',
							'alias' => 'QualityStatusPeriod',
							'conditions' => array(
								'QualityStatusPeriod.academic_period_id = AcademicPeriod.id',
								'QualityStatusPeriod.quality_status_id' => $qualityStatus
							)
						),
						array(
							'table' => 'quality_statuses',
							'alias' => 'QualityStatus',
							'conditions' => array(
								'QualityStatus.id = QualityStatusPeriod.quality_status_id'
							)
						)
					),
					'group' => array(
						'AcademicPeriod.id'
					)
				));

				$programmeIds = $QualityStatus->EducationProgramme->find('list', array(
					'fields' => array(
						'EducationProgramme.id', 'EducationProgramme.id'
					),
					'joins' => array(
						array(
							'table' => 'quality_status_programmes',
							'alias' => 'QualityStatusProgramme',
							'conditions' => array(
								'QualityStatusProgramme.education_programme_id = EducationProgramme.id',
								'QualityStatusProgramme.quality_status_id' => $qualityStatus
							)
						),
						array(
							'table' => 'quality_statuses',
							'alias' => 'QualityStatus',
							'conditions' => array(
								'QualityStatus.id = QualityStatusProgramme.quality_status_id'
							)
						)
					),
					'group' => array(
						'EducationProgramme.id'
					)
				));

				foreach ($periods as $period) {
					$periodId = $period['AcademicPeriod']['id'];

					$siteProgrammeConditions = array(
						'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId,
						'InstitutionSiteProgramme.education_programme_id' => $programmeIds
					);
					$siteProgrammeConditions = $InstitutionSiteProgramme->getConditionsByAcademicPeriodId($periodId, $siteProgrammeConditions);

					$siteGrades = $InstitutionSiteGrade->find('all', array(
						'contain' => array(
							'EducationGrade',
							'InstitutionSiteProgramme' => array(
								'conditions' => $siteProgrammeConditions
							)
						),
						'conditions' => array(
							'InstitutionSiteGrade.institution_site_programme_id = InstitutionSiteProgramme.id',
							'InstitutionSiteGrade.status' => 1
						),
						'order' => array('EducationGrade.education_programme_id', 'EducationGrade.order')
					));

					$gradeIds = array();
					foreach ($siteGrades as $siteGradeId => $siteGrade) {
						$gradeIds[$siteGrade['EducationGrade']['id']] = $siteGrade['EducationGrade']['id'];
					}

					$siteClasses = array();
					if ($gradeIds) {
						$options = array();
						$fields = array(
							'InstitutionSiteSection.id', 'InstitutionSiteSection.name', 'InstitutionSiteClass.id', 'InstitutionSiteClass.name',
							'EducationGrade.id', 'EducationGrade.name', 'EducationProgramme.id', 'EducationProgramme.name',
							'Staff.id', 'Staff.first_name', 'Staff.last_name'
						);
						$joins = array(
							array(
								'table' => 'institution_site_sections',
								'alias' => 'InstitutionSiteSection',
								'conditions' => array(
									'InstitutionSiteSection.id = InstitutionSiteSectionClass.institution_site_section_id'
								)
							),
							array(
								'type' => 'LEFT',
								'table' => 'education_grades',
								'alias' => 'EducationGrade',
								'conditions' => array(
									'EducationGrade.id = InstitutionSiteSection.education_grade_id'
								)
							),
							array(
								'type' => 'LEFT',
								'table' => 'education_programmes',
								'alias' => 'EducationProgramme',
								'conditions' => array(
									'EducationProgramme.id = EducationGrade.education_programme_id'
								)
							),
							array(
								'table' => 'institution_site_classes',
								'alias' => 'InstitutionSiteClass',
								'conditions' => array(
									'InstitutionSiteClass.id = InstitutionSiteSectionClass.institution_site_class_id'
								)
							),
							array(
								'type' => 'LEFT',
								'table' => 'institution_site_class_staff',
								'alias' => 'InstitutionSiteClassStaff',
								'conditions' => array(
									'InstitutionSiteClassStaff.institution_site_class_id = InstitutionSiteClass.id'
								)
							),
							array(
								'type' => 'LEFT',
								'table' => 'staff',
								'alias' => 'Staff',
								'conditions' => array(
									'Staff.id = InstitutionSiteClassStaff.staff_id'
								)
							)
						);
						$conditions = array(
							'InstitutionSiteSection.education_grade_id' => $gradeIds
						);
						$group = array(
							'InstitutionSiteSection.id', 'InstitutionSiteClass.id'
						);
						$order = array(
							'InstitutionSiteSection.name', 'InstitutionSiteClass.name'
						);

						$this->contain('InstitutionSiteClass');
						$existingClassOptions = array(
							'fields' => array('InstitutionSiteClass.id'),
							'conditions' => array(
								$this->alias . '.institution_site_id' => $institutionSiteId,
								$this->alias . '.rubric_template_id' => $templateId,
								$this->alias . '.academic_period_id' => $periodId,
								$this->alias . '.education_grade_id' => $gradeIds
							)
						);

						// if it's a new Quality Rubric, exclude classes from draft or complete
						if ($status == 0) { // Quality Rubric New
							$existingClassOptions['conditions'][$this->alias . '.status'] = array(1, 2);
						} else if ($status == 1 || $status == 2) { // Quality Rubric Draft / Completed
							$existingClassOptions['conditions'][$this->alias . '.status'] = $status;

							$fields[] = $this->alias . '.id';
							$fields[] = $this->alias . '.modified';
							$fields[] = $this->alias . '.created';
							$joinConditions = array(
								$this->alias . '.institution_site_id' => $institutionSiteId,
								$this->alias . '.rubric_template_id' => $templateId,
								$this->alias . '.academic_period_id' => $periodId,
								$this->alias . '.education_grade_id' => $gradeIds,
								$this->alias . '.status' => $status
							);

							$joins[] = array(
								'table' => $this->useTable,
								'alias' => $this->alias,
								'conditions' => $joinConditions
							);
						}
						$existingClasses = $this->find('list', $existingClassOptions);

						$findClasses = false;
						if (!empty($existingClasses)) {
							if ($status == 0) {
								$conditions['NOT']['InstitutionSiteClass.id'] = $existingClasses;
							} else if ($status == 1 || $status == 2) {
								$conditions['InstitutionSiteClass.id'] = $existingClasses;
							}

							$findClasses = true;
						} else {
							if ($status == 0) {
								$findClasses = true;
							}
						}

						if ($findClasses) {
							$options['fields'] = $fields;
							$options['joins'] = $joins;
							$options['conditions'] = $conditions;
							$options['group'] = $group;
							$options['order'] = $order;

							$InstitutionSiteSectionClass->contain();
							$siteClasses = $InstitutionSiteSectionClass->find('all', $options);
						}
					}

					if (!empty($siteClasses)) {
						foreach ($siteClasses as $siteClass) {
							$classes = $siteClass;
							$classes['AcademicPeriod']['id'] = $periodId;
							$classes['AcademicPeriod']['name'] = $period['AcademicPeriod']['name'];
							$classes['QualityStatus']['date_disabled'] = $period['QualityStatus']['date_disabled'];
							$templates[$i]['InstitutionSiteClass'][] = $classes;
						}
					}
				}
			}
			if (empty($templates[$i]['InstitutionSiteClass'])) {
				unset($templates[$i]);
			}
		}

		$contentHeader = __('Rubrics');
		$data = $templates;
		if (empty($data)) {
			$this->Message->alert('general.noData');
		}
		$this->controller->set(compact('contentHeader', 'data'));
	}

	public function view($id=0) {
		$named = $this->controller->params->named;
		$status = isset($named['status']) ? $named['status'] : 0;

		if ($id == 0) {
			$selectedTemplate = isset($named['template']) ? $named['template'] : 0;
			$selectedPeriod = isset($named['period']) ? $named['period'] : 0;
			$selectedGrade = isset($named['grade']) ? $named['grade'] : 0;
			$selectedSiteSection = isset($named['siteSection']) ? $named['siteSection'] : 0;
			$selectedSiteClass = isset($named['siteClass']) ? $named['siteClass'] : 0;
			$selectedStaff = isset($named['staff']) ? $named['staff'] : 0;
			$data = array();
			$result = $this->RubricTemplate->find('first', array(
				'contain' => array(),
				'fields' => array(
					'RubricTemplate.id', 'RubricTemplate.name'
				),
				'conditions' => array('RubricTemplate.id' => $selectedTemplate)
			));
			$data['RubricTemplate'] = $result['RubricTemplate'];
			$result = $this->AcademicPeriod->find('first', array(
				'contain' => array(),
				'fields' => array(
					'AcademicPeriod.id', 'AcademicPeriod.name'
				),
				'conditions' => array('AcademicPeriod.id' => $selectedPeriod)
			));
			$data['AcademicPeriod'] = $result['AcademicPeriod'];
			$result = $this->EducationGrade->find('first', array(
				'contain' => array('EducationProgramme'),
				'fields' => array(
					'EducationGrade.id', 'EducationGrade.name', 'EducationProgramme.id', 'EducationProgramme.name'
				),
				'conditions' => array('EducationGrade.id' => $selectedGrade)
			));
			$data['EducationGrade'] = $result['EducationGrade'];
			$data['EducationProgramme'] = $result['EducationProgramme'];
			$result = $this->InstitutionSiteSection->find('first', array(
				'contain' => array(),
				'fields' => array(
					'InstitutionSiteSection.id', 'InstitutionSiteSection.name'
				),
				'conditions' => array('InstitutionSiteSection.id' => $selectedSiteSection)
			));
			$data['InstitutionSiteSection'] = $result['InstitutionSiteSection'];
			$result = $this->InstitutionSiteClass->find('first', array(
				'contain' => array(),
				'fields' => array(
					'InstitutionSiteClass.id', 'InstitutionSiteClass.name'
				),
				'conditions' => array('InstitutionSiteClass.id' => $selectedSiteClass)
			));
			$data['InstitutionSiteClass'] = $result['InstitutionSiteClass'];
			$result = $this->Staff->find('first', array(
				'contain' => array(),
				'fields' => array(
					'Staff.id', 'Staff.first_name', 'Staff.last_name'
				),
				'conditions' => array('Staff.id' => $selectedStaff)
			));
			$data['Staff'] = $result['Staff'];
		} else {
			$data = $this->find('first', array(
				'fields' => array(
					'RubricTemplate.id', 'RubricTemplate.name',
					'AcademicPeriod.id', 'AcademicPeriod.name',
					'EducationGrade.id', 'EducationGrade.name',
					'InstitutionSiteSection.id', 'InstitutionSiteSection.name',
					'InstitutionSiteClass.id', 'InstitutionSiteClass.name',
					'Staff.id', 'Staff.first_name', 'Staff.last_name'
				),
				'contain' => array('RubricTemplate', 'AcademicPeriod', 'EducationGrade', 'EducationGrade.EducationProgramme', 'InstitutionSiteSection', 'InstitutionSiteClass', 'Staff'),
				'conditions' => array(
					'InstitutionSiteQualityRubric.id' => $id
				)
			));
			$selectedTemplate = $data['RubricTemplate']['id'];
			$selectedPeriod = $data['AcademicPeriod']['id'];
			$selectedGrade = $data['EducationGrade']['id'];
			$selectedSiteSection = $data['InstitutionSiteSection']['id'];
			$selectedSiteClass = $data['InstitutionSiteClass']['id'];
			$selectedStaff = $data['Staff']['id'];
			$result = $this->EducationGrade->find('first', array(
				'contain' => array('EducationProgramme'),
				'fields' => array(
					'EducationGrade.id', 'EducationGrade.name', 'EducationProgramme.id', 'EducationProgramme.name'
				),
				'conditions' => array('EducationGrade.id' => $selectedGrade)
			));
			$data['EducationProgramme'] = $result['EducationProgramme'];
			$this->Session->write($this->alias.'.id', $id);
		}

		$this->Navigation->addCrumb('Rubrics', array('controller' => 'InstitutionSites', 'action' => $this->alias, 'index', 'status' => $status, 'plugin' => false));
		if ($status == 0) {
			$this->Navigation->addCrumb('New');
		} else if ($status == 1) {
			$this->Navigation->addCrumb('Draft');
		} else if ($status == 2) {
			$this->Navigation->addCrumb('Completed');
		}
		
		$this->RubricTemplate->RubricSection->contain('RubricCriteria');
		$rubricSections = $this->RubricTemplate->RubricSection->find('all', array(
			'conditions' => array(
				'RubricSection.rubric_template_id' => $selectedTemplate
			),
			'order' => array(
				'RubricSection.order'
			)
		));

		$tmp = array();
		foreach ($rubricSections as $key => $obj) {
			$count = 0;
			foreach ($obj['RubricCriteria'] as $rubricCriteria) {
				if($rubricCriteria['type'] == 2) {
					$count++;
				}
			}
			$obj['RubricSection']['no_of_criterias'] = $count;

			$answer = $this->InstitutionSiteQualityRubricAnswer->find('count', array(
				'conditions' => array(
					'InstitutionSiteQualityRubricAnswer.institution_site_quality_rubric_id' => $id,
					'InstitutionSiteQualityRubricAnswer.rubric_section_id' => $obj['RubricSection']['id'],
					'InstitutionSiteQualityRubricAnswer.rubric_criteria_option_id IS NOT NULL'
				)
			));
			$obj['RubricSection']['no_of_answers'] = $answer;

			$tmp[$key] = $obj;
		}
		
		$data['RubricSection'] = $tmp;

		$contentHeader = $this->RubricTemplate->field('name', $selectedTemplate);
		$this->controller->set(compact('contentHeader', 'data', 'selectedStatus'));
	}

	public function edit($id=0) {
		$named = $this->controller->params->named;
		$pass = $this->controller->params->pass;
		$selectedSection = isset($named['section']) ? $named['section'] : 0;
		$selectedStatus = isset($named['status']) ? $named['status'] : 0;

		if ($id == 0) {
			$selectedTemplate = isset($named['template']) ? $named['template'] : 0;
			$selectedPeriod = isset($named['period']) ? $named['period'] : 0;
			$selectedGrade = isset($named['grade']) ? $named['grade'] : 0;
			$selectedSiteSection = isset($named['siteSection']) ? $named['siteSection'] : 0;
			$selectedSiteClass = isset($named['siteClass']) ? $named['siteClass'] : 0;
			$selectedStaff = isset($named['staff']) ? $named['staff'] : 0;
			$institutionSiteId = $this->Session->read('InstitutionSite.id');
		} else {
			$result = $this->find('first', array(
				'contain' => array(),
				'conditions' => array(
					'InstitutionSiteQualityRubric.id' => $id
				)
			));
			$selectedTemplate = $result['InstitutionSiteQualityRubric']['rubric_template_id'];
		}

		unset($pass[0]);
		$editUrl = array('action' => $this->alias, 'edit');
		$editUrl = array_merge($editUrl, $named, $pass);

		$backUrl = array('controller' => 'InstitutionSites', 'action' => $this->alias, 'view', 'plugin' => false);
		$backUrl = array_merge($backUrl, $named, $pass);

		$this->Navigation->addCrumb('Rubrics', $backUrl);
		$this->Navigation->addCrumb('Details');

		if ($this->request->is(array('post', 'put'))) {
			$data = $this->request->data;
			unset($data['criteria']);
			$status = isset($data['postFinal']) ? 2 : 1;
			$data['InstitutionSiteQualityRubric']['status'] = $status;

			if ($this->saveAll($data)) {
				if ($status == 1) {
					$this->Message->alert('InstitutionSiteQualityRubric.save.draft');
					$redirectUrl = array('action' => $this->alias, 'view', $this->id, 'status' => $status);
					return $this->controller->redirect($redirectUrl);
				} else if ($status == 2) {
					$templateId = $this->field('rubric_template_id', array('InstitutionSiteQualityRubric.id' => $this->id));
					$criteria = $this->RubricTemplate->RubricSection->RubricCriteria->find('count', array(
						'conditions' => array(
							'RubricCriteria.type' => 2,
							'RubricSection.rubric_template_id' => $templateId
						)
					));
					$answer = $this->InstitutionSiteQualityRubricAnswer->find('count', array(
						'conditions' => array(
							'InstitutionSiteQualityRubricAnswer.institution_site_quality_rubric_id' => $this->id,
							'InstitutionSiteQualityRubricAnswer.rubric_criteria_option_id IS NOT NULL'
						)
					));
					if ($criteria != $answer) {
						$this->Message->alert('InstitutionSiteQualityRubric.save.failed');
						$status = 1;
						$this->saveField('status', $status);
						$redirectUrl = array('action' => $this->alias, 'view', $this->id, 'status' => $status);
						return $this->controller->redirect($redirectUrl);
					} else {
						$this->Message->alert('InstitutionSiteQualityRubric.save.final');
						return $this->controller->redirect(array('action' => $this->alias, 'index', 'status' => $status));
					}
				}
			} else {
				$this->log($this->validationErrors, 'debug');
				$this->Message->alert('general.add.failed');
			}
		} else {
			if ($selectedStatus == 0) {
				$data = array();
				$data['InstitutionSiteQualityRubric']['status'] = $selectedStatus;
				$data['InstitutionSiteQualityRubric']['comment'] = '';
				$data['InstitutionSiteQualityRubric']['rubric_template_id'] = $selectedTemplate;
				$data['InstitutionSiteQualityRubric']['academic_period_id'] = $selectedPeriod;
				$data['InstitutionSiteQualityRubric']['education_grade_id'] = $selectedGrade;
				$data['InstitutionSiteQualityRubric']['institution_site_section_id'] = $selectedSiteSection;
				$data['InstitutionSiteQualityRubric']['institution_site_class_id'] = $selectedSiteClass;
				$data['InstitutionSiteQualityRubric']['staff_id'] = $selectedStaff;
				$data['InstitutionSiteQualityRubric']['institution_site_id'] = $institutionSiteId;
				$this->request->data = $data;
			} else if ($selectedStatus == 1 || $selectedStatus == 2) {
				$this->contain('InstitutionSiteQualityRubricAnswer');
				$data = $this->find('first', array(
					'conditions' => array(
						'InstitutionSiteQualityRubric.id' => $id
					)
				));
				$selectedTemplate = $data['InstitutionSiteQualityRubric']['rubric_template_id'];

				$qualityRubricAnswers = array();
				foreach ($data['InstitutionSiteQualityRubricAnswer'] as $key => $obj) {
					$qualityRubricAnswers[$obj['rubric_criteria_id']] = $obj;
				}
				$data['InstitutionSiteQualityRubricAnswer'] = $qualityRubricAnswers;

				$this->request->data = $data;
			}
		}

		$this->RubricTemplate->RubricTemplateOption->contain();
		$rubricTemplateOptionData = $this->RubricTemplate->RubricTemplateOption->find('all', array(
			'conditions' => array(
				'RubricTemplateOption.rubric_template_id' => $selectedTemplate
			),
			'order' => array('RubricTemplateOption.order', 'RubricTemplateOption.name')
		));
		$rubricTemplateOptions = array();
		foreach ($rubricTemplateOptionData as $key => $obj) {
			$rubricTemplateOptions[$obj['RubricTemplateOption']['id']] = $obj['RubricTemplateOption'];
		}

		$rubricTemplateOptionCount = sizeof($rubricTemplateOptions);

		$this->RubricTemplate->RubricSection->RubricCriteria->contain('RubricSection', 'RubricCriteriaOption');
		$rubricCriterias = $this->RubricTemplate->RubricSection->RubricCriteria->find('all', array(
			'conditions' => array(
				'RubricCriteria.rubric_section_id' => $selectedSection
			),
			'order' => array(
				'RubricCriteria.order'
			)
		));

		$contentHeader = $this->RubricTemplate->field('name', $selectedTemplate) . " - " . $this->RubricTemplate->RubricSection->field('name', $id);
		$this->controller->set(compact('contentHeader', 'rubricCriterias', 'rubricTemplateOptions', 'rubricTemplateOptionCount', 'editUrl', 'backUrl'));
	}

	public function remove() {
		if ($this->Session->check($this->alias . '.id')) {
			$id = $this->Session->read($this->alias . '.id');
			$status = $this->field('status', array('InstitutionSiteQualityRubric.id' => $id));

			if ($status == 1) {
				if($this->delete($id)) {
					$this->Message->alert('general.delete.success');
				} else {
					$this->log($this->validationErrors, 'debug');
					$this->Message->alert('general.delete.failed');
				}
			} else if ($status == 2) {
				$this->id = $id;
				if($this->saveField('status', 1)) {
					$this->Message->alert('InstitutionSiteQualityRubric.reject.success');
				} else {
					$this->log($this->validationErrors, 'debug');
					$this->Message->alert('InstitutionSiteQualityRubric.reject.failed');
				}
			}

			$this->Session->delete($this->alias.'.id');
			return $this->controller->redirect(array('action' => $this->alias, 'index', 'status' => $status));
		}
	}
}
