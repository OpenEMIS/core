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

class SurveyStatusesController extends SurveysAppController {
	public $uses = array(
		'Surveys.SurveyStatus',
		'Surveys.SurveyStatusPeriod',
		'Surveys.SurveyTemplate',
		'Surveys.SurveyModule',
		'AcademicPeriodType',
		'SchoolYear'
	);

	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
		$this->Navigation->addCrumb('Surveys', array('action' => 'index'));
		$this->Navigation->addCrumb('Status');

		$moduleOptions = $this->SurveyModule->getModuleList();
    	$this->set('moduleOptions', $moduleOptions);
		$templateOptions = $this->SurveyTemplate->getTemplateList();
		$academicPeriodTypeOptions = $this->AcademicPeriodType->getAcademicPeriodTypeList();
		$selectedAcademicPeriodType = key($academicPeriodTypeOptions);

		$this->set('moduleOptions', $moduleOptions);
		$this->set('templateOptions', $templateOptions);
		$this->set('academicPeriodTypeOptions', $academicPeriodTypeOptions);
		$this->set('selectedAcademicPeriodType', $selectedAcademicPeriodType);
		$this->set('contentHeader', __('Status'));
	}

	public function index($selectedSurveyStatus = 1) {
		$surveyStatusOptions = array(0 => 'Past', 1 => 'Current');
		$data = $this->SurveyStatus->getSurveyStatusData($selectedSurveyStatus);

		$this->set('surveyStatusOptions', $surveyStatusOptions);
		$this->set('selectedSurveyStatus', $selectedSurveyStatus);
		$this->set('data', $data);
    }

    public function view($id=0) {
		$academicPeriodOptions = $this->SchoolYear->getAvailableYears();

		if ($this->SurveyStatus->exists($id)) {
			$this->SurveyStatus->contain(array('SurveyTemplate', 'AcademicPeriodType', 'AcademicPeriod', 'ModifiedUser', 'CreatedUser'));
			$data = $this->SurveyStatus->findById($id);
			$arrAcademicPeriod = array();
			if(sizeof($data['AcademicPeriod']) > 0) {
				foreach ($data['AcademicPeriod'] as $key => $value) {
					$arrAcademicPeriod[] = $value['name'];
				}
			}
			$data['AcademicPeriod']['list'] = implode(", ", $arrAcademicPeriod);

			$this->Session->write($this->SurveyStatus->alias.'.id', $id);
			$this->set('data', $data);
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'index'));
		}

		$this->set('academicPeriodOptions', $academicPeriodOptions);
	}

	public function add() {
		$academicPeriodOptions = $this->SchoolYear->getAvailableYears();

		if ($this->request->is(array('post', 'put'))) {
			if ($this->SurveyStatus->saveAll($this->request->data)) {
				$this->Message->alert('general.add.success');
				return $this->redirect(array('action' => 'index'));
			} else {
				$this->log($this->validationErrors, 'debug');
				$this->Message->alert('general.add.failed');
			}
		}
		
		if(isset($this->params['pass'][0])) {
			$selectedAcademicPeriodType = $this->params['pass'][0];
			$this->set('selectedAcademicPeriodType', $selectedAcademicPeriodType);
		}

		$this->set('academicPeriodOptions', $academicPeriodOptions);
    }

    public function edit($id=0) {
		$academicPeriodOptions = $this->SchoolYear->getAvailableYears();

		if ($this->SurveyStatus->exists($id)) {
			$this->SurveyStatus->contain(array('SurveyTemplate', 'AcademicPeriod'));
			$data = $this->SurveyStatus->findById($id);

			if ($this->request->is(array('post', 'put'))) {
				if ($this->SurveyStatus->saveAll($this->request->data)) {
					$this->Message->alert('general.edit.success');
					return $this->redirect(array('action' => 'view', $id));
				} else {
					$this->log($this->validationErrors, 'debug');
					$this->Message->alert('general.edit.failed');
				}
				$data = $this->request->data;
			} else {
				$this->request->data = $data;
			}
			$this->set('data', $data);
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'index'));
		}

		$this->set('academicPeriodOptions', $academicPeriodOptions);
    }

    public function delete() {
    	if ($this->Session->check($this->SurveyStatus->alias . '.id')) {
			$id = $this->Session->read($this->SurveyStatus->alias . '.id');
			if($this->SurveyStatus->delete($id)) {
				$this->SurveyStatusPeriod->deleteAll(array('SurveyStatusPeriod.survey_status_id' => $id));
				$this->Message->alert('general.delete.success');
			} else {
				$this->log($this->validationErrors, 'debug');
				$this->Message->alert('general.delete.failed');
			}
			$this->Session->delete($this->SurveyStatus->alias . '.id');
			return $this->redirect(array('action' => 'index'));
		}
    }
}
?>