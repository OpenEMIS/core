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

		$academicPeriodTypeOptions = $this->AcademicPeriodType->getAcademicPeriodTypeList();
		$selectedAcademicPeriodType = key($academicPeriodTypeOptions);

		$this->set('academicPeriodTypeOptions', $academicPeriodTypeOptions);
		$this->set('selectedAcademicPeriodType', $selectedAcademicPeriodType);
		$this->set('contentHeader', __('Status'));
	}

	public function index() {
		$params = $this->params->named;

		$SurveyModule = ClassRegistry::init('SurveyModule');
		$modules = $SurveyModule->find('list' , array(
			'conditions' => array(
				'SurveyModule.visible' => 1
			),
			'order' => array(
				'SurveyModule.order',
				'SurveyModule.name'
			)
		));
		$selectedModule = isset($params['module']) ? $params['module'] : key($modules);

		$moduleOptions = array();
		foreach ($modules as $key => $module) {
			$moduleOptions['module:' . $key] = $module;
		}

		$statusOptions = array(
			'status:0' => 'Past',
			'status:1' => 'Current'
		);
		$selectedStatus = isset($params['status']) ? $params['status'] : 1;
		$data = $this->SurveyStatus->getSurveyStatusByModule($selectedModule, $selectedStatus);

		$this->set('modules', $modules);
		$this->set('moduleOptions', $moduleOptions);
		$this->set('selectedModule', $selectedModule);
		$this->set('statusOptions', $statusOptions);
		$this->set('selectedStatus', $selectedStatus);
		$this->set('data', $data);
    }

    public function view($id=0) {
    	$params = $this->params->named;

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
			$params['action'] = 'index';
			return $this->redirect($params);
		}
	}

	public function add() {
		$params = $this->params->named;

		$templateOptions = $this->SurveyTemplate->getTemplateListByModule($params['module']);
		$academicPeriodOptions = $this->SchoolYear->getAvailableYears();

		if ($this->request->is(array('post', 'put'))) {
			if ($this->SurveyStatus->saveAll($this->request->data)) {
				$this->Message->alert('general.add.success');
				$params['action'] = 'index';
				return $this->redirect($params);
			} else {
				$this->log($this->SurveyStatus->validationErrors, 'debug');
				$this->Message->alert('general.add.failed');
			}
		} else {
			$todayDate = date('d-m-Y');
			$this->request->data['SurveyStatus']['date_enabled'] = $todayDate;
			$this->request->data['SurveyStatus']['date_disabled'] = $todayDate;
		}

		$this->set('templateOptions', $templateOptions);
		$this->set('academicPeriodOptions', $academicPeriodOptions);
		$this->render('edit');
    }

    public function edit($id=0) {
		$params = $this->params->named;

		if ($this->SurveyStatus->exists($id)) {
			$templateOptions = $this->SurveyTemplate->getTemplateListByModule($params['module']);
			$academicPeriodOptions = $this->SchoolYear->getAvailableYears();

			$this->SurveyStatus->contain(array('SurveyTemplate', 'AcademicPeriod'));
			$data = $this->SurveyStatus->findById($id);

			if ($this->request->is(array('post', 'put'))) {
				if ($this->SurveyStatus->saveAll($this->request->data)) {
					$this->Message->alert('general.edit.success');
					$params = array_merge(array('action' => 'view', $id), $params);
					return $this->redirect($params);
				} else {
					$this->log($this->SurveyStatus->validationErrors, 'debug');
					$this->Message->alert('general.edit.failed');
				}
			} else {
				$this->request->data = $data;
			}

			$this->set('templateOptions', $templateOptions);
			$this->set('academicPeriodOptions', $academicPeriodOptions);
		} else {
			$this->Message->alert('general.notExists');
			$params['action'] = 'index';
			return $this->redirect($params);
		}
    }

    public function delete() {
    	if ($this->Session->check($this->SurveyStatus->alias . '.id')) {
    		$params = $this->params->named;
			$id = $this->Session->read($this->SurveyStatus->alias . '.id');

			if($this->SurveyStatus->delete($id)) {
				$this->Message->alert('general.delete.success');
			} else {
				$this->log($this->validationErrors, 'debug');
				$this->Message->alert('general.delete.failed');
			}

			$this->Session->delete($this->SurveyStatus->alias . '.id');
			$params['action'] = 'index';
			return $this->redirect($params);
		}
    }
}
?>