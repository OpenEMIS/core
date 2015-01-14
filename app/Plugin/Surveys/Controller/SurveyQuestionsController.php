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
	public $actsAs = array(
		'Reorder'
	);

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

		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
		$this->Navigation->addCrumb('Surveys', array('action' => 'index'));

		if($this->action == 'reorder') {
			$this->Navigation->addCrumb('Details', array('action' => 'index'));
			$this->Navigation->addCrumb('Reorder');
		} else if($this->action == 'preview') {
			$this->Navigation->addCrumb('Details', array('action' => 'index'));
			$this->Navigation->addCrumb('Preview');
		} else {
			$this->Navigation->addCrumb('Questions');
		}

		$this->set('contentHeader', __('Questions'));
	}

	public function index($selectedModule=0, $selectedTemplate=0) {
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

			$this->Session->write($this->SurveyTemplate->alias.'.id', $selectedTemplate);

			$this->set('templateOptions', $templateOptions);
			$this->set('selectedTemplate', $selectedTemplate);
			$this->set('templateData', $templateData);
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
		if ($this->CustomField2->Field->exists($id)) {
			$this->CustomField2->Field->contain(
				$this->CustomField2->Parent->alias,
				$this->CustomField2->FieldOption->alias,
				$this->CustomField2->TableRow->alias,
				$this->CustomField2->TableColumn->alias
			);
			$data = $this->CustomField2->Field->findById($id);
			$selectedFieldType = $data[$this->CustomField2->Field->alias]['type'];
			
			if ($this->request->is(array('post', 'put'))) {

				$data = $this->request->data;
				$selectedFieldType = $data[$this->CustomField2->Field->alias]['type'];
				//$data[$this->CustomField2->Field->alias]['is_mandatory'] = 0;
				//$data[$this->CustomField2->Field->alias]['is_unique'] = 0;

				if ($data['submit'] == 'reload') {
				} else if($data['submit'] == $this->CustomField2->FieldOption->alias) {
					$data[$this->CustomField2->FieldOption->alias][] =array(
						'id' => String::uuid(),
						'value' => '',
						'visible' => 1
					);
				} else if($data['submit'] == $this->CustomField2->TableRow->alias) {
					$data[$this->CustomField2->TableRow->alias][] =array(
						'id' => '',
						'name' => '',
						'visible' => 1,
					);
				} else if($data['submit'] == $this->CustomField2->TableColumn->alias) {
					$data[$this->CustomField2->TableColumn->alias][] =array(
						'id' => '',
						'name' => '',
						'visible' => 1,
					);
				} else {
					if(isset($this->request->data[$this->CustomField2->FieldOption->alias])) {
						foreach ($this->request->data[$this->CustomField2->FieldOption->alias] as $key => $value) {
							if(empty($value['value'])) {
								unset($this->request->data[$this->CustomField2->FieldOption->alias][$key]);
							}
						}
					}
					if(isset($this->request->data[$this->CustomField2->TableColumn->alias])) {
						foreach ($this->request->data[$this->CustomField2->TableColumn->alias] as $key => $value) {
							if(empty($value['name'])) {
								unset($this->request->data[$this->CustomField2->TableColumn->alias][$key]);
							}
						}
					}
					if(isset($this->request->data[$this->CustomField2->TableRow->alias])) {
						foreach ($this->request->data[$this->CustomField2->TableRow->alias] as $key => $value) {
							if(empty($value['name'])) {
								unset($this->request->data[$this->CustomField2->TableRow->alias][$key]);
							}
						}
					}
					if(isset($this->request->data[$this->CustomField2->Parent->alias])) {
						unset($this->request->data[$this->CustomField2->Parent->alias]);
					}

					$this->CustomField2->FieldOption->updateAll(
					    array($this->CustomField2->FieldOption->alias . '.visible' => 0),
					    array($this->CustomField2->FieldOption->alias . '.survey_question_id' => $id)
					);
					$this->CustomField2->TableRow->updateAll(
					    array($this->CustomField2->TableRow->alias . '.visible' => 0),
					    array($this->CustomField2->TableRow->alias . '.survey_question_id' => $id)
					);
					$this->CustomField2->TableColumn->updateAll(
					    array($this->CustomField2->TableColumn->alias . '.visible' => 0),
					    array($this->CustomField2->TableColumn->alias . '.survey_question_id' => $id)
					);

					if ($this->CustomField2->Field->saveAll($this->request->data)) {
						$this->Message->alert('general.edit.success');
						return $this->redirect(array('action' => 'view', $id));
					} else {
						$this->log($this->CustomField2->Field->validationErrors, 'debug');
						$this->Message->alert('general.edit.failed');
					}
				}


				$this->set('selectedFieldType', $selectedFieldType);
				
			} else {
				$this->request->data = $data;
			}

			$mandatoryDisabled = $this->CustomField2->getMandatoryDisabled($selectedFieldType);
			$uniqueDisabled = $this->CustomField2->getUniqueDisabled($selectedFieldType);
			$this->set('mandatoryDisabled', $mandatoryDisabled);
			$this->set('uniqueDisabled', $uniqueDisabled);
			//$this->request->data = $data;
			//$this->set('data', $data);
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'index'));
		}
    }

    public function delete() {
    	if ($this->Session->check($this->SurveyQuestion->alias . '.id')) {
			$id = $this->Session->read($this->SurveyQuestion->alias . '.id');
			if($this->SurveyQuestion->delete($id)) {
				$this->SurveyQuestionChoice->updateAll(
				    array('SurveyQuestionChoice.visible' => 0),
				    array('SurveyQuestionChoice.survey_question_id' => $id)
				);
				$this->SurveyTableRow->updateAll(
				    array('SurveyTableRow.visible' => 0),
				    array('SurveyTableRow.survey_question_id' => $id)
				);
				$this->SurveyTableColumn->updateAll(
				    array('SurveyTableColumn.visible' => 0),
				    array('SurveyTableColumn.survey_question_id' => $id)
				);
				//$this->SurveyQuestionChoice->deleteAll(array('SurveyQuestionChoice.survey_question_id' => $id));
				//$this->SurveyTableRow->deleteAll(array('SurveyTableRow.survey_question_id' => $id));
				//$this->SurveyTableColumn->deleteAll(array('SurveyTableColumn.survey_question_id' => $id));
				$this->Message->alert('general.delete.success');
			} else {
				$this->log($this->validationErrors, 'debug');
				$this->Message->alert('general.delete.failed');
			}
			$this->Session->delete($this->SurveyQuestion->alias . '.id');
			return $this->redirect(array('action' => 'index'));
		}
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

    public function preview($selectedModule=0, $selectedTemplate=0) {
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
    }    
}
?>