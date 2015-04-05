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
        'EducationProgramme',
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
		$InstitutionSiteProgramme = $this->EducationProgramme->InstitutionSiteProgramme;
		$InstitutionSiteGrade = $this->EducationProgramme->InstitutionSiteProgramme->InstitutionSiteGrade;
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

					$conditions = array(
						'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId,
						'InstitutionSiteProgramme.education_programme_id' => $programmeIds
					);
					$conditions = $InstitutionSiteProgramme->getConditionsByAcademicPeriodId($periodId, $conditions);

					$siteGrades = $InstitutionSiteGrade->find('all', array(
						'contain' => array(
							'EducationGrade',
							'InstitutionSiteProgramme' => array(
								'conditions' => $conditions
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
						$siteClasses = $InstitutionSiteSectionClass->find('all', array(
							'contain' => array(
								'InstitutionSiteClass', 'InstitutionSiteSection.EducationGrade', 'InstitutionSiteSection.EducationGrade.EducationProgramme'
							),
							'conditions' => array(
								'InstitutionSiteSection.education_grade_id' => $gradeIds
							),
							'group' => array(
								'InstitutionSiteSection.id', 'InstitutionSiteClass.id'
							),
							'order' => array(
								'InstitutionSiteSection.name', 'InstitutionSiteClass.name'
							)
						));
					}

					if (!empty($siteClasses)) {
						foreach ($siteClasses as $siteClass) {
							$classes = array();
							$classes['AcademicPeriod']['id'] = $periodId;
							$classes['AcademicPeriod']['name'] = $period['AcademicPeriod']['name'];
							$classes['EducationProgramme']['id'] = $siteClass['InstitutionSiteSection']['EducationGrade']['EducationProgramme']['id'];
							$classes['EducationProgramme']['name'] = $siteClass['InstitutionSiteSection']['EducationGrade']['EducationProgramme']['name'];
							$classes['EducationProgramme']['cycle_programme_name'] = $siteClass['InstitutionSiteSection']['EducationGrade']['EducationProgramme']['cycle_programme_name'];
							$classes['EducationGrade']['id'] = $siteClass['InstitutionSiteSection']['EducationGrade']['id'];
							$classes['EducationGrade']['name'] = $siteClass['InstitutionSiteSection']['EducationGrade']['name'];
							$classes['InstitutionSiteSection']['id'] = $siteClass['InstitutionSiteSection']['id'];
							$classes['InstitutionSiteSection']['name'] = $siteClass['InstitutionSiteSection']['name'];
							$classes['InstitutionSiteClass']['id'] = $siteClass['InstitutionSiteClass']['id'];
							$classes['InstitutionSiteClass']['name'] = $siteClass['InstitutionSiteClass']['name'];
							$classes['QualityStatus']['date_disabled'] = $period['QualityStatus']['date_disabled'];
							$templates[$i]['InstitutionSiteClass'][] = $classes;
						}
					} else {
						//unset($templates[$i]);
					}
				}
			}
		}

		$contentHeader = __('Rubrics');
		$data = $templates;
		$this->controller->set(compact('contentHeader', 'data'));
	}

	public function listSection() {
		$named = $this->controller->params->named;
		$selectedTemplate = isset($named['template']) ? $named['template'] : 0;
		$selectedPeriod = isset($named['period']) ? $named['period'] : 0;
		$selectedProgramme = isset($named['programme']) ? $named['programme'] : 0;
		$selectedGrade = isset($named['grade']) ? $named['grade'] : 0;
		$selectedSection = isset($named['section']) ? $named['section'] : 0;
		$selectedClass = isset($named['class']) ? $named['class'] : 0;
		$selectedStatus = isset($named['status']) ? $named['status'] : 0;

		$this->Navigation->addCrumb('Rubrics', array('controller' => 'InstitutionSites', 'action' => $this->alias, 'index', 'status' => $selectedStatus, 'plugin' => false));
		$this->Navigation->addCrumb('Add');
		
		$this->RubricTemplate->RubricSection->contain();
		$data = $this->RubricTemplate->RubricSection->find('all', array(
			'conditions' => array(
				'RubricSection.rubric_template_id' => $selectedTemplate
			),
			'order' => array(
				'RubricSection.order'
			)
		));

		$contentHeader = $this->RubricTemplate->field('name', $selectedTemplate);
		$this->controller->set(compact('contentHeader', 'data', 'selectedStatus'));
	}

	public function edit($id=0) {
		$named = $this->controller->params->named;

		$selectedTemplate = isset($named['template']) ? $named['template'] : 0;
		$selectedPeriod = isset($named['period']) ? $named['period'] : 0;
		$selectedProgramme = isset($named['programme']) ? $named['programme'] : 0;
		$selectedGrade = isset($named['grade']) ? $named['grade'] : 0;
		$selectedSection = isset($named['section']) ? $named['section'] : 0;
		$selectedClass = isset($named['class']) ? $named['class'] : 0;
		$selectedStatus = isset($named['status']) ? $named['status'] : 0;
		$institutionSiteId = $this->Session->read('InstitutionSite.id');

		$editUrl = array('action' => $this->alias, 'edit');
		$editUrl = array_merge($editUrl, $named);

		$backUrl = array('controller' => 'InstitutionSites', 'action' => $this->alias, 'listSection', 'plugin' => false);
		$backUrl = array_merge($backUrl, $named);
		$this->Navigation->addCrumb('Rubrics', $backUrl);
		$this->Navigation->addCrumb('Details');

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
		$data = $this->RubricTemplate->RubricSection->RubricCriteria->find('all', array(
			'conditions' => array(
				'RubricCriteria.rubric_section_id' => $id
			),
			'order' => array(
				'RubricCriteria.order'
			)
		));

		if ($this->request->is(array('post', 'put'))) {
			$data = $this->request->data;
			unset($data['criteria']);

			if ($this->saveAll($data)) {
				$this->Message->alert('general.add.success');
				return $this->controller->redirect($backUrl);
			} else {
				$this->log($his->validationErrors, 'debug');
				$this->Message->alert('general.add.failed');
			}
		} else {
			$newData = array();
			$newData['InstitutionSiteQualityRubric']['status'] = $selectedStatus;
			$newData['InstitutionSiteQualityRubric']['comment'] = '';
			$newData['InstitutionSiteQualityRubric']['rubric_template_id'] = $selectedTemplate;
			$newData['InstitutionSiteQualityRubric']['academic_period_id'] = $selectedPeriod;
			$newData['InstitutionSiteQualityRubric']['education_programme_id'] = $selectedProgramme;
			$newData['InstitutionSiteQualityRubric']['education_grade_id'] = $selectedGrade;
			$newData['InstitutionSiteQualityRubric']['institution_site_section_id'] = $selectedSection;
			$newData['InstitutionSiteQualityRubric']['institution_site_class_id'] = $selectedClass;
			$newData['InstitutionSiteQualityRubric']['staff_id'] = 0;
			$newData['InstitutionSiteQualityRubric']['institution_site_id'] = $institutionSiteId;
			$this->request->data = $newData;
		}

		$contentHeader = $this->RubricTemplate->field('name', $selectedTemplate) . " - " . $this->RubricTemplate->RubricSection->field('name', $id);
		$this->controller->set(compact('contentHeader', 'data', 'rubricTemplateOptions', 'rubricTemplateOptionCount', 'editUrl', 'backUrl'));
	}
}
