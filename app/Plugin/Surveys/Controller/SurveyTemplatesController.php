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

class SurveyTemplatesController extends SurveysAppController {
	public $uses = array(
		'Surveys.SurveyTemplate',
		'Surveys.SurveyModule'
	);

	public function beforeFilter() {
		$this->Auth->allow('templatelist');
		parent::beforeFilter();
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
		$this->Navigation->addCrumb('Surveys', array('action' => 'index'));
		$this->Navigation->addCrumb('Templates');

    	$this->set('contentHeader', __('Templates'));
	}

	public function index() {
		$params = $this->params->named;

		$modules = $this->SurveyModule->getModuleList();
		$selectedModule = isset($params['module']) ? $params['module'] : key($modules);

		$moduleOptions = array();
		foreach ($modules as $key => $module) {
			$moduleOptions['module:' . $key] = $module;
		}

		$this->SurveyTemplate->contain('SurveyModule');
    	$data = $this->SurveyTemplate->find('all', array(
			'conditions' => array(
				'SurveyTemplate.survey_module_id' => $selectedModule
			),
			'order' => array(
				'SurveyTemplate.name'
			)
		));

		$this->set('moduleOptions', $moduleOptions);
		$this->set('selectedModule', $selectedModule);
		$this->set('data', $data);
    }

    public function view($id=0) {
		$params = $this->params->named;

		if ($this->SurveyTemplate->exists($id)) {
			$this->SurveyTemplate->contain('SurveyModule', 'ModifiedUser', 'CreatedUser');
			$data = $this->SurveyTemplate->findById($id);
			$this->Session->write($this->SurveyTemplate->alias.'.id', $id);
			$this->set('data', $data);
		} else {
			$this->Message->alert('general.notExists');
			$params['action'] = 'index';
			return $this->redirect($params);
		}
	}

    public function add() {
		$params = $this->params->named;

		$moduleOptions = $this->SurveyModule->getModuleList();
		$selectedModule = isset($params['module']) ? $params['module'] : key($moduleOptions);

		if ($this->request->is(array('post', 'put'))) {
			if ($this->SurveyTemplate->saveAll($this->request->data)) {
				$this->Message->alert('general.add.success');
				$params['action'] = 'index';
				return $this->redirect($params);
			} else {
				$this->log($this->SurveyTemplate->validationErrors, 'debug');
				$this->Message->alert('general.add.failed');
			}
		} else {
			$this->request->data['SurveyTemplate']['survey_module_id'] = $selectedModule;
		}

		$this->set('moduleOptions', $moduleOptions);
		$this->render('edit');
    }

    public function edit($id=0) {
    	$params = $this->params->named;

		if ($this->SurveyTemplate->exists($id)) {
			$moduleOptions = $this->SurveyModule->getModuleList();

			$this->SurveyTemplate->contain();
			$data = $this->SurveyTemplate->findById($id);

			if ($this->request->is(array('post', 'put'))) {
				if ($this->SurveyTemplate->saveAll($this->request->data)) {
					$this->Message->alert('general.edit.success');
					$params = array_merge(array('action' => 'view', $id), $params);
					return $this->redirect($params);
				} else {
					$this->log($this->SurveyTemplate->validationErrors, 'debug');
					$this->Message->alert('general.edit.failed');
				}
			} else {
				$this->request->data = $data;
			}

			$this->set('moduleOptions', $moduleOptions);
		} else {
			$this->Message->alert('general.notExists');
			$params['action'] = 'index';
			return $this->redirect($params);
		}
    }

    public function delete() {
    	if ($this->Session->check($this->SurveyTemplate->alias.'.id')) {
    		$params = $this->params->named;
			$id = $this->Session->read($this->SurveyTemplate->alias.'.id');

			if($this->SurveyTemplate->delete($id)) {
				$this->Message->alert('general.delete.success');
			} else {
				$this->log($this->SurveyTemplate->validationErrors, 'debug');
				$this->Message->alert('general.delete.failed');
			}

			$this->Session->delete($this->SurveyTemplate->alias.'.id');
			$params['action'] = 'index';
			return $this->redirect($params);
		}
    }

    public function templatelist() {
    	$this->autoRender = false;
    	$data = $this->SurveyTemplate->find('list');
    	return json_encode($data);
    }

    public function download($id) {
    	//$this->CustomField2->download($id);
    }
}
?>
