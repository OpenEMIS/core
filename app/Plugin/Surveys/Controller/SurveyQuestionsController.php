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

class SurveyQuestionsController extends SurveysAppController {
	public $uses = array(
		'Surveys.SurveyQuestion',
		'Surveys.SurveyQuestionChoice',
		'Surveys.SurveyTableRow',
		'Surveys.SurveyTableColumn',
		'Surveys.SurveyTemplate'
	);

	public $components = array(
		'CustomField2' => array(
			'models' => array(
				'Module' => 'Surveys.SurveyModule',
				'Parent' => 'Surveys.SurveyTemplate',
				'Field' => 'Surveys.SurveyQuestion',
				'FieldOption' => 'Surveys.SurveyQuestionChoice',
				'TableRow' => 'Surveys.SurveyTableRow',
				'TableColumn' => 'Surveys.SurveyTableColumn'
			)
		)
	);

	public function beforeFilter() {
		parent::beforeFilter();
		$params = $this->params->named;

		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
		$this->Navigation->addCrumb('Surveys', array('action' => 'index'));

		if($this->action == 'reorder') {
			$params['action'] = 'index';
			$this->Navigation->addCrumb('Questions', $params);
			$this->Navigation->addCrumb('Reorder');
		} else if($this->action == 'preview') {
			$params['action'] = 'index';
			$this->Navigation->addCrumb('Questions', $params);
			$this->Navigation->addCrumb('Preview');
		} else {
			$this->Navigation->addCrumb('Questions');
		}

		$this->set('contentHeader', __('Questions'));
	}

	public function index() {
		$params = $this->params->named;

		$SurveyModule = ClassRegistry::init('SurveyModule');
		$modules = $SurveyModule->find('list' , array(
			'conditions' => array('SurveyModule.visible' => 1),
			'order' => array('SurveyModule.order')
		));
		$selectedModule = isset($params['module']) ? $params['module'] : key($modules);

		$moduleOptions = array();
		foreach ($modules as $key => $module) {
			$moduleOptions['module:' . $key] = $module;
		}

		$templates = $this->SurveyTemplate->find('list', array(
			'conditions' => array(
				'SurveyTemplate.survey_module_id' => $selectedModule
			),
			'order' => array(
				'SurveyTemplate.name'
			)
		));

		if(!empty($templates)) {
			$selectedTemplate = isset($params['parent']) ? $params['parent'] : key($templates);

			$templateOptions = array();
			foreach ($templates as $key => $template) {
				$templateOptions['parent:' . $key] = $template;
			}

			$this->SurveyQuestion->contain();
			$data = $this->SurveyQuestion->find('all', array(
				'conditions' => array(
					'SurveyQuestion.survey_template_id' => $selectedTemplate
				),
				'order' => array(
					'SurveyQuestion.order', 
					'SurveyQuestion.name'
				)
			));
			$this->Session->write($this->SurveyTemplate->alias.'.id', $selectedTemplate);

			$this->set('templateOptions', $templateOptions);
			$this->set('selectedTemplate', $selectedTemplate);
			$this->set('data', $data);
		} else {
			$this->Message->alert('general.noData');
		}

		$this->set('moduleOptions', $moduleOptions);
		$this->set('selectedModule', $selectedModule);
    }

    public function view($id=0) {
    	$this->CustomField2->view($id);
	}

    public function add() {
    	$this->CustomField2->add();
    }

    public function edit($id=0) {
    	$this->CustomField2->edit($id);
    }

    public function delete() {
    	$this->CustomField2->delete();
    }

	public function reorder($id=0) {
		$templateData = $this->SurveyTemplate->findById($id);
		$data = $this->SurveyQuestion->getSurveyQuestionData($id);

		$this->set('templateData', $templateData);
		$this->set('data', $data);
		$this->set('id', $id);
    }

    public function moveOrder($templateId=0) {
		$data = $this->request->data;
		$conditions = array('SurveyQuestion.survey_template_id' => $templateId);

		$id = $data['SurveyQuestion']['id'];
		$idField = 'SurveyQuestion.id';
		$orderField = 'SurveyQuestion.order';
		$move = $data['SurveyQuestion']['move'];
		$order = $this->SurveyQuestion->field('order', array('id' => $id));
		$idConditions = array_merge(array($idField => $id), $conditions);
		$updateConditions = array_merge(array($idField . ' <>' => $id), $conditions);
		
		$this->fixOrder($conditions);
		if($move === 'up') {
			$this->SurveyQuestion->updateAll(array($orderField => $order-1), $idConditions);
			$updateConditions[$orderField] = $order-1;
			$this->SurveyQuestion->updateAll(array($orderField => $order), $updateConditions);
		} else if($move === 'down') {
			$this->SurveyQuestion->updateAll(array($orderField => $order+1), $idConditions);
			$updateConditions[$orderField] = $order+1;
			$this->SurveyQuestion->updateAll(array($orderField => $order), $updateConditions);
		} else if($move === 'first') {
			$this->SurveyQuestion->updateAll(array($orderField => 1), $idConditions);
			$updateConditions[$orderField . ' <'] = $order;
			$this->SurveyQuestion->updateAll(array($orderField => $orderField . ' + 1'), $updateConditions);
		} else if($move === 'last') {
			$count = $this->SurveyQuestion->find('count', array('conditions' => $conditions));
			$this->SurveyQuestion->updateAll(array($orderField => $count), $idConditions);
			$updateConditions[$orderField . ' >'] = $order;
			$this->SurveyQuestion->updateAll(array($orderField => $orderField . ' - 1'), $updateConditions);
		}

		return $this->redirect(array('action' => 'reorder', $templateId));
    }

    public function fixOrder($conditions) {
		$count = $this->SurveyQuestion->find('count', array('conditions' => $conditions));
		if($count > 0) {
			$list = $this->SurveyQuestion->find('list', array(
				'conditions' => $conditions,
				'order' => array('SurveyQuestion.order')
			));
			$order = 1;
			foreach($list as $id => $name) {
				$this->SurveyQuestion->id = $id;
				$this->SurveyQuestion->saveField('order', $order++);
			}
		}
	}

    public function preview() {
		$params = $this->params->named;

		$SurveyModule = ClassRegistry::init('SurveyModule');
		$modules = $SurveyModule->find('list' , array(
			'conditions' => array('SurveyModule.visible' => 1),
			'order' => array('SurveyModule.order')
		));
		$selectedModule = isset($params['module']) ? $params['module'] : key($modules);

		$moduleOptions = array();
		foreach ($modules as $key => $module) {
			$moduleOptions['module:' . $key] = $module;
		}

		$templates = $this->SurveyTemplate->find('list', array(
			'conditions' => array(
				'SurveyTemplate.survey_module_id' => $selectedModule
			),
			'order' => array(
				'SurveyTemplate.name'
			)
		));

		if(!empty($templates)) {
			$selectedTemplate = isset($params['parent']) ? $params['parent'] : key($templates);
			$templateOptions = array();
			foreach ($templates as $key => $template) {
				$templateOptions['parent:' . $key] = $template;
			}

			$this->SurveyTemplate->contain();
			$template = $this->SurveyTemplate->findById($selectedTemplate);
			$this->SurveyQuestion->contain(array('SurveyQuestionChoice', 'SurveyTableRow', 'SurveyTableColumn'));
			$data = $this->SurveyQuestion->find('all', array(
				'conditions' => array(
					'SurveyQuestion.survey_template_id' => $selectedTemplate,
					'SurveyQuestion.visible' => 1
				),
				'order' => array(
					'SurveyQuestion.order', 
					'SurveyQuestion.name'
				)
			));
			$model = 'SurveyQuestion';
			$modelOption = 'SurveyQuestionChoice';
			$modelValue = 'InstitutionSiteSurveyAnswer';
			$modelRow = 'SurveyTableRow';
			$modelColumn = 'SurveyTableColumn';
			$modelCell = 'InstitutionSiteSurveyTableCell';
			$action = 'edit';

			$this->Session->write($this->SurveyTemplate->alias.'.id', $selectedTemplate);

			$this->set('templateOptions', $templateOptions);
			$this->set('selectedTemplate', $selectedTemplate);
			$this->set('template', $template);
			$this->set('data', $data);
			$this->set('model', $model);
			$this->set('modelOption', $modelOption);
			$this->set('modelValue', $modelValue);
			$this->set('modelRow', $modelRow);
			$this->set('modelColumn', $modelColumn);
			$this->set('modelCell', $modelCell);
			$this->set('action', $action);
		} else {
			$this->Message->alert('general.noData');
		}

		$this->set('moduleOptions', $moduleOptions);
		$this->set('selectedModule', $selectedModule);

		/*
		pr($selectedModule);
		pr($selectedTemplate);
		pr($moduleOptions);
		pr($templateOptions);
		die;

		$SurveyModule = ClassRegistry::init('SurveyModule');
		$moduleOptions = $SurveyModule->find('list' , array(
			'conditions' => array('SurveyModule.visible' => 1),
			'order' => array('SurveyModule.order')
		));
		$selectedModule = $selectedModule == 0 ? key($moduleOptions) : $selectedModule;
		$templateOptions = $this->SurveyTemplate->getTemplateListByModule($selectedModule);

		if(!empty($templateOptions)) {
			$selectedTemplate = $selectedTemplate == 0 ? key($templateOptions) : $selectedTemplate;
			$templateData = $this->SurveyTemplate->findById($selectedTemplate);
			$data = $this->SurveyQuestion->getSurveyQuestionData($selectedTemplate);
			$model = 'SurveyQuestion';
			$modelOption = 'SurveyQuestionChoice';
			$modelValue = 'InstitutionSiteSurveyAnswer';
			$modelRow = 'SurveyTableRow';
			$modelColumn = 'SurveyTableColumn';
			$modelCell = 'InstitutionSiteSurveyTableCell';
			$action = 'edit';

			$this->Session->write($this->SurveyTemplate->alias.'.id', $selectedTemplate);

			$this->set('templateOptions', $templateOptions);
			$this->set('selectedTemplate', $selectedTemplate);
			$this->set('templateData', $templateData);
			$this->set('data', $data);
			$this->set('model', $model);
			$this->set('modelOption', $modelOption);
			$this->set('modelValue', $modelValue);
			$this->set('modelRow', $modelRow);
			$this->set('modelColumn', $modelColumn);
			$this->set('modelCell', $modelCell);
			$this->set('action', $action);
		} else {
			$this->Message->alert('general.noData');
		}

		$this->set('moduleOptions', $moduleOptions);
		$this->set('selectedModule', $selectedModule);
		*/
    }    
}
?>