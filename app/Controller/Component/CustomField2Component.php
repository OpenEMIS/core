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

class CustomField2Component extends Component {
	private $controller;

	public $components = array('Session', 'Message');

	// Is called before the controller's beforeFilter method.
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
		
		$models = $this->settings['models'];
		foreach ($models as $key => $model) {
			if (!is_null($model)) {
				$this->{$key} = ClassRegistry::init($model);
			} else {
				$this->{$key} = null;
			}

			$modelInfo = explode('.', $model);
			$base = count($modelInfo) == 1 ? $modelInfo[0] : $modelInfo[1];
			$this->controller->set('Custom_' . $key, $base);
		}
	}

	// Is called after the controller's beforeFilter method but before the controller executes the current action handler.
	public function startup(Controller $controller) {
		$fieldTypeOptions = $this->get('fieldType');
		$selectedFieldType = key($fieldTypeOptions);
		$mandatoryOptions = $this->get('mandatory');
		$selectedMandatory = 0;
		$mandatoryDisabled = $this->getMandatoryDisabled($selectedFieldType);
		$uniqueOptions = $this->get('unique');
		$selectedUnique = 0;
		$uniqueDisabled = $this->getUniqueDisabled($selectedFieldType);
		$visibleOptions = $this->get('visible');
		$selectedVisible = 1;

		$controller->set('fieldTypeOptions', $fieldTypeOptions);
		$controller->set('selectedFieldType', $selectedFieldType);
		$controller->set('mandatoryOptions', $mandatoryOptions);
		$controller->set('selectedMandatory', $selectedMandatory);
		$controller->set('mandatoryDisabled', $mandatoryDisabled);
		$controller->set('uniqueOptions', $uniqueOptions);
		$controller->set('selectedUnique', $selectedUnique);
		$controller->set('uniqueDisabled', $uniqueDisabled);
		$controller->set('visibleOptions', $visibleOptions);
		$controller->set('selectedVisible', $selectedVisible);
	}

	public function get($code) {
		$options = array(
			'fieldType' => array(
				1 => __('Label'),
				2 => __('Text'),
				3 => __('Dropdown'),
				4 => __('Checkbox'),
				5 => __('Textarea'),
				6 => __('Number'),
				7 => __('Table')
			),
			'mandatory' => array(
				1 => __('Yes'),
				0 => __('No')
			),
			'unique' => array(
				1 => __('Yes'),
				0 => __('No')
			),
			'visible' => array(
				1 => __('Yes'),
				0 => __('No')
			)
		);
		
		$index = explode('.', $code);
		foreach($index as $i) {
			if(isset($options[$i])) {
				$option = $options[$i];
			} else {
				$option = array('[Option Not Found]');
				break;
			}
		}
		return $option;
	}

    public function getMandatoryDisabled($fieldTypeId=1) {
		$arrMandatory = array(2,5,6);
		if(in_array($fieldTypeId, $arrMandatory)) {
			$result = '';
		} else {
			$result = 'disabled';
		}

		return $result;
    }

	public function getUniqueDisabled($fieldTypeId=1) {
		$arrUnique = array(2,6);
		if(in_array($fieldTypeId, $arrUnique)) {
			$result = '';
		} else {
			$result = 'disabled';
		}
		return $result;
    }

    public function view($id) {
    	$params = $this->controller->params->named;
    	if ($this->Field->exists($id)) {
			$data = $this->Field->findById($id);
			$this->Session->write($this->Field->alias.'.id', $id);
			$this->controller->set('data', $data);
		} else {
			$this->Message->alert('general.notExists');

			$params['action'] = 'index';
			return $this->controller->redirect($params);
		}
    }

    public function add() {
    	$params = $this->controller->params->named;

    	foreach ($params as $key => $value) {
    		$this->controller->set('Custom_' . ucfirst($key) . 'Id', $value);
    	}

		$parentName = $this->Parent->field('name', $params['parent']);

		$this->controller->set('parentName', $parentName);

    	if ($this->controller->request->is(array('post', 'put'))) {
    		$data = $this->controller->request->data;
    		$selectedFieldType = $data[$this->Field->alias]['type'];

    		if ($data['submit'] == 'reload') {
    			
			} else if($data['submit'] == $this->FieldOption->alias) {
				$data[$this->FieldOption->alias][] =array(
					'id' => String::uuid(),
					'value' => '',
					'default_option' => 0,
					'visible' => 1,
					//'survey_question_id' => $id
				);
			}/* else if($data['submit'] == 'SurveyTableRow') {
				$data['SurveyTableRow'][] =array(
					'id' => '',
					'name' => '',
					'visible' => 1,
					//'survey_question_id' => $id
				);
			} else if($data['submit'] == 'SurveyTableColumn') {
				$data['SurveyTableColumn'][] =array(
					'id' => '',
					'name' => '',
					'visible' => 1,
					//'survey_question_id' => $id
				);
    		}*/ else {
    			if(isset($this->controller->request->data[$this->FieldOption->alias])) {
					foreach ($this->controller->request->data[$this->FieldOption->alias] as $key => $value) {
						if(empty($value['value'])) {
							unset($this->controller->request->data[$this->FieldOption->alias][$key]);
						}
					}
				}

	    		if ($this->Field->saveAll($this->controller->request->data)) {
					$this->Message->alert('general.add.success');
					$params['action'] = 'index';
					return $this->controller->redirect($params);
				} else {
					$this->log($this->Field->validationErrors, 'debug');
					$this->Message->alert('general.add.failed');
				}
    		}

    		$mandatoryDisabled = $this->getMandatoryDisabled($selectedFieldType);
			$uniqueDisabled = $this->getUniqueDisabled($selectedFieldType);
			$this->controller->set('mandatoryDisabled', $mandatoryDisabled);
			$this->controller->set('uniqueDisabled', $uniqueDisabled);
    		$this->controller->set('selectedFieldType', $selectedFieldType);
			$this->controller->set('data', $data);
    	}

    	if (!empty($this->controller->params->plugin)) {
    		$this->controller->render('../../../../View/Elements/custom_fields/edit');
    	} else {
			$this->controller->render('/Elements/custom_fields/edit');
    	}    	
    }

    public function edit($id = 0) {

    }
}
