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

	public $components = array('CustomField2');

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

		$fieldTypeOptions = $this->CustomField2->get('fieldType');
		$selectedFieldType = key($fieldTypeOptions);
		$mandatoryOptions = $this->CustomField2->get('mandatory');
		$selectedMandatory = 0;
		$mandatoryDisabled = $this->CustomField2->getMandatoryDisabled($selectedFieldType);
		$uniqueOptions = $this->CustomField2->get('unique');
		$selectedUnique = 0;
		$uniqueDisabled = $this->CustomField2->getUniqueDisabled($selectedFieldType);
		$visibleOptions = $this->CustomField2->get('visible');
		$selectedVisible = 1;

		$this->set('fieldTypeOptions', $fieldTypeOptions);
		$this->set('selectedFieldType', $selectedFieldType);
		$this->set('mandatoryOptions', $mandatoryOptions);
		$this->set('selectedMandatory', $selectedMandatory);
		$this->set('mandatoryDisabled', $mandatoryDisabled);
		$this->set('uniqueOptions', $uniqueOptions);
		$this->set('selectedUnique', $selectedUnique);
		$this->set('uniqueDisabled', $uniqueDisabled);
		$this->set('visibleOptions', $visibleOptions);
		$this->set('selectedVisible', $selectedVisible);

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
		if ($this->SurveyQuestion->exists($id)) {
			$data = $this->SurveyQuestion->findById($id);
			$this->Session->write($this->SurveyQuestion->alias.'.id', $id);
			$this->set('data', $data);
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'index'));
		}
	}

    public function add() {
    	if ($this->Session->check($this->SurveyTemplate->alias . '.id')) {
			$id = $this->Session->read($this->SurveyTemplate->alias . '.id');

			$templateData = $this->SurveyTemplate->findById($id);
			$this->set('templateData', $templateData);

	    	if ($this->request->is(array('post', 'put'))) {
	    		$data = $this->request->data;
	    		$selectedFieldType = $data['SurveyQuestion']['type'];

	    		if ($data['submit'] == 'reload') {
				} else if($data['submit'] == 'SurveyQuestionChoice') {
					$data['SurveyQuestionChoice'][] =array(
						'id' => String::uuid(),
						'value' => '',
						'default_choice' => 0,
						'visible' => 1,
						'survey_question_id' => $id
					);
				} else if($data['submit'] == 'SurveyTableRow') {
					$data['SurveyTableRow'][] =array(
						'id' => '',
						'name' => '',
						'visible' => 1,
						'survey_question_id' => $id
					);
				} else if($data['submit'] == 'SurveyTableColumn') {
					$data['SurveyTableColumn'][] =array(
						'id' => '',
						'name' => '',
						'visible' => 1,
						'survey_question_id' => $id
					);
	    		} else {
	    			if(isset($this->request->data['SurveyQuestionChoice'])) {
						foreach ($this->request->data['SurveyQuestionChoice'] as $key => $value) {
							if(empty($value['value'])) {
								unset($this->request->data['SurveyQuestionChoice'][$key]);
							}
						}
					}

		    		if ($this->SurveyQuestion->saveAll($this->request->data)) {
						$this->Message->alert('general.add.success');
						return $this->redirect(array('action' => 'index'));
					} else {
						$this->log($this->validationErrors, 'debug');
						$this->Message->alert('general.add.failed');
					}
	    		}

	    		$mandatoryDisabled = $this->CustomField2->getMandatoryDisabled($selectedFieldType);
				$uniqueDisabled = $this->CustomField2->getUniqueDisabled($selectedFieldType);
				$this->set('mandatoryDisabled', $mandatoryDisabled);
				$this->set('uniqueDisabled', $uniqueDisabled);
	    		$this->set('selectedFieldType', $selectedFieldType);
				$this->set('data', $data);
	    	}
		}
    }

    public function edit($id=0) {
		if ($this->SurveyQuestion->exists($id)) {
			$this->SurveyQuestion->recursive = -1;
			$this->SurveyQuestion->contain('SurveyTemplate', 'SurveyQuestionChoice', 'SurveyTableRow', 'SurveyTableColumn');
			$data = $this->SurveyQuestion->findById($id);
			$selectedFieldType = $data['SurveyQuestion']['type'];
			if ($this->request->is(array('post', 'put'))) {

				$data = $this->request->data;
				$selectedFieldType = $data['SurveyQuestion']['type'];
				$data['SurveyQuestion']['is_mandatory'] = 0;
				$data['SurveyQuestion']['is_unique'] = 0;

				if ($data['submit'] == 'reload') {
				} else if($data['submit'] == 'SurveyQuestionChoice') {
					$data['SurveyQuestionChoice'][] =array(
						'id' => String::uuid(),
						'value' => '',
						'default_choice' => 0,
						'visible' => 1,
						'survey_question_id' => $this->Session->read($this->SurveyTemplate->alias . '.id')
					);
				} else if($data['submit'] == 'SurveyTableRow') {
					$data['SurveyTableRow'][] =array(
						'id' => '',
						'name' => '',
						'visible' => 1,
						'survey_question_id' => $id
					);
				} else if($data['submit'] == 'SurveyTableColumn') {
					$data['SurveyTableColumn'][] =array(
						'id' => '',
						'name' => '',
						'visible' => 1,
						'survey_question_id' => $id
					);
				} else {
					if(isset($this->request->data['SurveyQuestionChoice'])) {
						foreach ($this->request->data['SurveyQuestionChoice'] as $key => $value) {
							if(empty($value['value'])) {
								unset($this->request->data['SurveyQuestionChoice'][$key]);
							}
						}
					}
					if(isset($this->request->data['SurveyTableColumn'])) {
						foreach ($this->request->data['SurveyTableColumn'] as $key => $value) {
							if(empty($value['name'])) {
								unset($this->request->data['SurveyTableColumn'][$key]);
							}
						}
					}
					if(isset($this->request->data['SurveyTableRow'])) {
						foreach ($this->request->data['SurveyTableRow'] as $key => $value) {
							if(empty($value['name'])) {
								unset($this->request->data['SurveyTableRow'][$key]);
							}
						}
					}
					if(isset($this->request->data['SurveyTemplate'])) {
						unset($this->request->data['SurveyTemplate']);
					}

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

					if ($this->SurveyQuestion->saveAll($this->request->data)) {
						$this->Message->alert('general.edit.success');
						return $this->redirect(array('action' => 'view', $id));
					} else {
						$this->log($this->validationErrors, 'debug');
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
			$this->set('data', $data);
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
		
		//$this->fixOrder($model, $conditions);
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

    public function preview($id=0) {
    	$templateData = $this->SurveyTemplate->findById($id);
    	$data = $this->SurveyQuestion->getSurveyQuestionData($id);
		$model = 'SurveyQuestion';
    	$modelOption = 'SurveyQuestionChoice';
    	$modelValue = 'InstitutionSiteSurveyAnswer';
    	$modelRow = 'SurveyTableRow';
    	$modelColumn = 'SurveyTableColumn';
    	$modelCell = 'InstitutionSiteSurveyTableCell';
		$action = 'edit';

		$this->set('templateData', $templateData);
		$this->set('data', $data);
		$this->set('model', $model);
		$this->set('modelOption', $modelOption);
		$this->set('modelValue', $modelValue);
		$this->set('modelRow', $modelRow);
		$this->set('modelColumn', $modelColumn);
		$this->set('modelCell', $modelCell);
		$this->set('action', $action);
    }    
}
?>