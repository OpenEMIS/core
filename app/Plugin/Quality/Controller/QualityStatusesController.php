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

class QualityStatusesController extends QualityAppController {
	public $uses = array(
		'Quality.QualityStatus',
		'Quality.RubricTemplate',
		'AcademicPeriodLevel',
		'AcademicPeriod'
	);

	public $components = array(
		'ControllerAction' => array('model' => 'Quality.QualityStatus')
	);

	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
		$this->Navigation->addCrumb('Quality', array('controller' => 'QualityStatuses', 'action' => 'index', 'plugin' => 'Quality'));
		$this->Navigation->addCrumb('Status');

		$this->QualityStatus->fields['rubric_template_id']['hyperlink'] = true;
		$this->QualityStatus->fields['status']['visible'] = false;
		$this->QualityStatus->fields['academic_periods'] = array(
			'type' => 'chosen_select',
			'id' => 'AcademicPeriod.AcademicPeriod',
			'placeholder' => __('Select academic periods'),
			'visible' => true
		);
		$this->ControllerAction->setFieldOrder('rubric_template_id', 1);
		$this->ControllerAction->setFieldOrder('academic_period_level_id', 2);
		$this->ControllerAction->setFieldOrder('academic_periods', 3);

		if ($this->action == 'index') {
			$this->QualityStatus->fields['academic_periods']['visible'] = false;
			$this->QualityStatus->fields['rubric_template_id']['dataModel'] = 'RubricTemplate';
			$this->QualityStatus->fields['rubric_template_id']['dataField'] = 'name';

			$this->QualityStatus->fields['academic_period_level_id']['dataModel'] = 'AcademicPeriodLevel';
			$this->QualityStatus->fields['academic_period_level_id']['dataField'] = 'name';

			$this->QualityStatus->fields['academic_periods']['dataModel'] = 'AcademicPeriod';
			$this->QualityStatus->fields['academic_periods']['dataField'] = 'name';
		} else if ($this->action == 'view') {
			$this->QualityStatus->fields['rubric_template_id']['dataModel'] = 'RubricTemplate';
			$this->QualityStatus->fields['rubric_template_id']['dataField'] = 'name';

			$this->QualityStatus->fields['academic_period_level_id']['dataModel'] = 'AcademicPeriodLevel';
			$this->QualityStatus->fields['academic_period_level_id']['dataField'] = 'name';

			$this->QualityStatus->fields['academic_periods']['dataModel'] = 'AcademicPeriod';
			$this->QualityStatus->fields['academic_periods']['dataField'] = 'name';
		} else if($this->action == 'add' || $this->action == 'edit') {
			$templateOptions = $this->RubricTemplate->find('list');
			$this->QualityStatus->fields['rubric_template_id']['type'] = 'select';
			$this->QualityStatus->fields['rubric_template_id']['options'] = $templateOptions;

			$periodLevelOptions = $this->AcademicPeriodLevel->find('list', array(
				'fields' => array('AcademicPeriodLevel.id', 'AcademicPeriodLevel.name'),
				'order' => array('AcademicPeriodLevel.level'),
			));
			$this->QualityStatus->fields['academic_period_level_id']['type'] = 'select';
			$this->QualityStatus->fields['academic_period_level_id']['options'] = $periodLevelOptions;
			$this->QualityStatus->fields['academic_period_level_id']['attr'] = array(
				'onchange' => "$('#reload').click()"
			);

			if ($this->request->is(array('post', 'put'))) {
				$data = $this->request->data;
				$selectedPeriodLevel = $data['QualityStatus']['academic_period_level_id'];

				if ($data['submit'] == 'reload') {
					$this->ControllerAction->autoProcess = false;
				} else {
					$this->ControllerAction->autoProcess = true;
				}
			} else {
				$selectedPeriodLevel = key($periodLevelOptions);
			}

			$academicPeriodOptions = $this->AcademicPeriod->getAcademicPeriodByLevel($selectedPeriodLevel);
			$this->QualityStatus->fields['academic_periods']['options'] = $academicPeriodOptions;
		}

		$this->set('contentHeader', 'Status');
	}

	public function index() {
		$named = $this->params->named;

		$templates = $this->RubricTemplate->find('list', array(
			'order' => array('RubricTemplate.name')
		));
		$selectedTemplate = isset($named['template']) ? $named['template'] : key($templates);

		$templateOptions = array();
		foreach ($templates as $key => $rubricTemplate) {
			$templateOptions['template:' . $key] = $rubricTemplate;
		}

		if (empty($templateOptions)) {
			$this->Message->alert('RubricTemplate.noTemplate');
		} else {
			$this->QualityStatus->contain('RubricTemplate', 'AcademicPeriodLevel', 'AcademicPeriod');
			$data = $this->QualityStatus->find('all', array(
				'conditions' => array(
					'QualityStatus.rubric_template_id' => $selectedTemplate
				),
				'order' => array(
					'QualityStatus.date_disabled', 'QualityStatus.date_enabled'
				)
			));

			$this->set(compact('data', 'templateOptions', 'selectedTemplate'));
		}
	}
}
