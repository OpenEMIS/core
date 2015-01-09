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
		parent::beforeFilter();
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
		$this->Navigation->addCrumb('Surveys', array('action' => 'index'));
		$this->Navigation->addCrumb('Templates');

		$moduleOptions = $this->SurveyModule->getModuleList();
    	$this->set('moduleOptions', $moduleOptions);
    	$this->set('contentHeader', __('Templates'));
	}

	public function index() {
		$data = $this->SurveyTemplate->getSurveyTemplateData();
		$this->set('data', $data);
    }

    public function view($id=0) {
		if ($this->SurveyTemplate->exists($id)) {
			$data = $this->SurveyTemplate->findById($id);
			$this->Session->write($this->SurveyTemplate->alias.'.id', $id);
			$this->set('data', $data);
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'index'));
		}
	}

    public function add() {
		if ($this->request->is(array('post', 'put'))) {
			if ($this->SurveyTemplate->saveAll($this->request->data)) {
				$this->Message->alert('general.add.success');
				return $this->redirect(array('action' => 'index'));
			} else {
				$this->log($this->validationErrors, 'debug');
				$this->Message->alert('general.add.failed');
			}
		}
    }

    public function edit($id=0) {
		if ($this->SurveyTemplate->exists($id)) {
			$data = $this->SurveyTemplate->findById($id);
			if ($this->request->is(array('post', 'put'))) {
				if ($this->SurveyTemplate->saveAll($this->request->data)) {
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
    }

    public function delete() {
    	if ($this->Session->check($this->SurveyTemplate->alias.'.id')) {
			$id = $this->Session->read($this->SurveyTemplate->alias.'.id');
			if($this->SurveyTemplate->delete($id)) {
				$this->Message->alert('general.delete.success');
			} else {
				$this->log($this->validationErrors, 'debug');
				$this->Message->alert('general.delete.failed');
			}
			$this->Session->delete($this->SurveyTemplate->alias.'.id');
			return $this->redirect(array('action' => 'index'));
		}
    }
}
?>