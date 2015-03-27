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
		$this->Navigation->addCrumb('Rubrics');

		$this->fields['evaluator']['type'] = 'string';
		$this->fields['evaluator']['visible'] = true;
		$this->fields['evaluator']['attr'] = array(
			'disabled' => 'disabled'
		);

		$this->ControllerAction->setFieldOrder('academic_period_id', 1);
		$this->ControllerAction->setFieldOrder('education_grade_id', 2);
		$this->ControllerAction->setFieldOrder('institution_site_section_id', 3);
		$this->ControllerAction->setFieldOrder('institution_site_class_id', 4);
		$this->ControllerAction->setFieldOrder('staff_id', 5);
		$this->ControllerAction->setFieldOrder('rubric_template_id', 6);
		$this->ControllerAction->setFieldOrder('evaluator', 7);

		if($this->action == 'index') {
			$this->fields['academic_period_id']['hyperlink'] = true;
			$this->fields['institution_site_section_id']['visible'] = false;
			$this->fields['evaluator']['visible'] = false;
			$this->fields['comment']['visible'] = false;
			$this->fields['institution_site_id']['visible'] = false;

			$this->fields['academic_period_id']['dataModel'] = 'AcademicPeriod';
			$this->fields['academic_period_id']['dataField'] = 'name';

			$this->fields['education_grade_id']['dataModel'] = 'EducationGrade';
			$this->fields['education_grade_id']['dataField'] = 'name';

			$this->fields['institution_site_class_id']['dataModel'] = 'InstitutionSiteClass';
			$this->fields['institution_site_class_id']['dataField'] = 'name';

			$this->fields['staff_id']['dataModel'] = 'Staff';
			$this->fields['staff_id']['dataField'] = 'name';

			$this->fields['rubric_template_id']['dataModel'] = 'RubricTemplate';
			$this->fields['rubric_template_id']['dataField'] = 'name';
		} else if($this->action == 'view') {
			$this->fields['institution_site_id']['visible'] = false;

			$this->fields['academic_period_id']['dataModel'] = 'AcademicPeriod';
			$this->fields['academic_period_id']['dataField'] = 'name';

			$this->fields['education_grade_id']['dataModel'] = 'EducationGrade';
			$this->fields['education_grade_id']['dataField'] = 'name';

			$this->fields['institution_site_section_id']['dataModel'] = 'InstitutionSiteSection';
			$this->fields['institution_site_section_id']['dataField'] = 'name';

			$this->fields['institution_site_class_id']['dataModel'] = 'InstitutionSiteClass';
			$this->fields['institution_site_class_id']['dataField'] = 'name';

			$this->fields['staff_id']['dataModel'] = 'Staff';
			$this->fields['staff_id']['dataField'] = 'name';

			$this->fields['rubric_template_id']['dataModel'] = 'RubricTemplate';
			$this->fields['rubric_template_id']['dataField'] = 'name';
		} else if($this->action == 'add' || $this->action == 'edit') {
			$institutionSiteId = $this->Session->read('InstitutionSite.id');
			$this->fields['institution_site_id']['type'] = 'hidden';
			$this->fields['institution_site_id']['value'] = $institutionSiteId;

			$templateOptions = $this->RubricTemplate->find('list', array(
				'order' => array('RubricTemplate.name')
			));
			$templateOptions = !empty($templateOptions) ? $templateOptions : array('0' => __('No Data'));
			$this->fields['rubric_template_id']['type'] = 'select';
			$this->fields['rubric_template_id']['options'] = $templateOptions;

			if ($this->request->is(array('post', 'put'))) {
				$data = $this->request->data;
				$selectedPeriod = $data['InstitutionSiteQualityRubric']['academic_period_id'];
				$selectedGrade = $data['InstitutionSiteQualityRubric']['education_grade_id'];
				$selectedSection = $data['InstitutionSiteQualityRubric']['institution_site_section_id'];
				$selectedClass = $data['InstitutionSiteQualityRubric']['institution_site_class_id'];

				if ($data['submit'] == 'reload') {
					$this->ControllerAction->autoProcess = false;
				} else {
					$this->ControllerAction->autoProcess = true;
				}
			} else {
				$selectedPeriod = null;
				$selectedGrade = null;
				$selectedSection = null;
				$selectedClass = null;
			}

			$academicPeriodOptions = $this->AcademicPeriod->getAcademicPeriodList();
			$selectedPeriod = array_key_exists($selectedPeriod, $academicPeriodOptions) ? $selectedPeriod : key($academicPeriodOptions);

			$gradeOptions = ClassRegistry::init('InstitutionSiteGrade')->getInstitutionSiteGradeOptions($institutionSiteId, $selectedPeriod);
			$selectedGrade = array_key_exists($selectedGrade, $gradeOptions) ? $selectedGrade : key($gradeOptions);

			$sectionOptions = $this->InstitutionSiteSection->getSectionOptions($selectedPeriod, $institutionSiteId, $selectedGrade);
			$selectedSection = array_key_exists($selectedSection, $sectionOptions) ? $selectedSection : key($sectionOptions);
			
			$classOptions = $this->InstitutionSiteClass->InstitutionSiteSectionClass->getClassOptions($selectedSection);
			$selectedClass = array_key_exists($selectedClass, $classOptions) ? $selectedClass : key($classOptions);

			$staffOptions = ClassRegistry::init('InstitutionSiteClassStaff')->getStaffs($selectedClass, 'list');

			$academicPeriodOptions = !empty($academicPeriodOptions) ? $academicPeriodOptions : array('0' => __('No Data'));
			$gradeOptions = !empty($gradeOptions) ? $gradeOptions : array('0' => __('No Data'));
			$sectionOptions = !empty($sectionOptions) ? $sectionOptions : array('0' => __('No Data'));
			$classOptions = !empty($classOptions) ? $classOptions : array('0' => __('No Data'));
			$staffOptions = !empty($staffOptions) ? $staffOptions : array('0' => __('No Data'));

			$this->fields['academic_period_id']['type'] = 'select';
			$this->fields['academic_period_id']['options'] = $academicPeriodOptions;
			$this->fields['academic_period_id']['attr'] = array('onchange' => "$('#reload').click()");

			$this->fields['education_grade_id']['type'] = 'select';
			$this->fields['education_grade_id']['options'] = $gradeOptions;
			$this->fields['education_grade_id']['attr'] = array('onchange' => "$('#reload').click()");

			$this->fields['institution_site_section_id']['type'] = 'select';
			$this->fields['institution_site_section_id']['options'] = $sectionOptions;
			$this->fields['institution_site_section_id']['attr'] = array('onchange' => "$('#reload').click()");

			$this->fields['institution_site_class_id']['type'] = 'select';
			$this->fields['institution_site_class_id']['options'] = $classOptions;
			$this->fields['institution_site_class_id']['attr'] = array('onchange' => "$('#reload').click()");

			$this->fields['staff_id']['type'] = 'select';
			$this->fields['staff_id']['options'] = $staffOptions;
			$this->fields['staff_id']['attr'] = array('onchange' => "$('#reload').click()");
		}

		$contentHeader = __('Rubrics');
		$this->controller->set(compact('contentHeader'));
	}

	public function afterAction() {
		if ($this->action == 'view') {
			$data = $this->controller->viewVars['data'];
			$evaluatorName = trim($data['CreatedUser']['first_name']) . ' ' . trim($data['CreatedUser']['last_name']);
			$data['InstitutionSiteQualityRubric']['evaluator'] = $evaluatorName;
			$this->controller->set('data', $data);
		} else if ($this->action == 'add') {
			$loginUser = $this->controller->Session->read('Auth.User');
			$evaluatorName = trim($loginUser['first_name']) . ' ' . trim($loginUser['last_name']);
			
			$this->request->data['InstitutionSiteQualityRubric']['evaluator'] = $evaluatorName;
		} else if ($this->action == 'edit') {
			$data = $this->request->data;
			$selectedPeriod = $data['InstitutionSiteQualityRubric']['academic_period_id'];
			$selectedGrade = $data['InstitutionSiteQualityRubric']['education_grade_id'];
			$selectedSection = $data['InstitutionSiteQualityRubric']['institution_site_section_id'];
			$selectedClass = $data['InstitutionSiteQualityRubric']['institution_site_class_id'];

			$this->contain('CreatedUser');
			$createdUser = $this->findById($data['InstitutionSiteQualityRubric']['id']);
			$evaluatorName = trim($createdUser['CreatedUser']['first_name']) . ' ' . trim($createdUser['CreatedUser']['last_name']);
			$this->request->data['InstitutionSiteQualityRubric']['evaluator'] = $evaluatorName;

			if ($this->request->is(array('post', 'put'))) {
			} else {
				$institutionSiteId = $this->Session->read('InstitutionSite.id');
				$academicPeriodOptions = $this->AcademicPeriod->getAcademicPeriodList();
				$selectedPeriod = array_key_exists($selectedPeriod, $academicPeriodOptions) ? $selectedPeriod : key($academicPeriodOptions);

				$gradeOptions = ClassRegistry::init('InstitutionSiteGrade')->getInstitutionSiteGradeOptions($institutionSiteId, $selectedPeriod);
				$selectedGrade = array_key_exists($selectedGrade, $gradeOptions) ? $selectedGrade : key($gradeOptions);

				$sectionOptions = $this->InstitutionSiteSection->getSectionOptions($selectedPeriod, $institutionSiteId, $selectedGrade);
				$selectedSection = array_key_exists($selectedSection, $sectionOptions) ? $selectedSection : key($sectionOptions);
				
				$classOptions = $this->InstitutionSiteClass->InstitutionSiteSectionClass->getClassOptions($selectedSection);
				$selectedClass = array_key_exists($selectedClass, $classOptions) ? $selectedClass : key($classOptions);

				$staffOptions = ClassRegistry::init('InstitutionSiteClassStaff')->getStaffs($selectedClass, 'list');

				$academicPeriodOptions = !empty($academicPeriodOptions) ? $academicPeriodOptions : array('0' => __('No Data'));
				$gradeOptions = !empty($gradeOptions) ? $gradeOptions : array('0' => __('No Data'));
				$sectionOptions = !empty($sectionOptions) ? $sectionOptions : array('0' => __('No Data'));
				$classOptions = !empty($classOptions) ? $classOptions : array('0' => __('No Data'));
				$staffOptions = !empty($staffOptions) ? $staffOptions : array('0' => __('No Data'));

				$this->fields['academic_period_id']['options'] = $academicPeriodOptions;
				$this->fields['education_grade_id']['options'] = $gradeOptions;
				$this->fields['institution_site_section_id']['options'] = $sectionOptions;
				$this->fields['institution_site_class_id']['options'] = $classOptions;
				$this->fields['staff_id']['options'] = $staffOptions;
			}
		}
	}
}
