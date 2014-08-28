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

class InstitutionSiteCustomField extends AppModel {
	public $actsAs = array(
		'CustomField' => array('module' => 'InstitutionSite'), // has to be before FieldOptionBehavior to override postAdd and postEdit
		'FieldOption', 
		'ControllerAction'
	);
	public $hasMany = array(
		'InstitutionSiteCustomFieldOption' => array('order' => 'order'),
		'InstitutionSiteCustomValue'
	);
	public $belongsTo = array(
		'InstitutionSiteType',
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
	
	public function getSubOptions() {
		$options = $this->InstitutionSiteType->findList();
		array_unshift($options, __('All'));
		return $options;
	}
	
	public function getOptionFields() {
		parent::getOptionFields();
		
		$this->fields['type']['type'] = 'select';
		$this->fields['type']['options'] = $this->getCustomFieldTypes();
		$this->fields['type']['visible'] = array('index' => true, 'view' => true, 'edit' => true);
		$this->fields['type']['attr'] = array('onchange' => "$('#reload').click()");
		$this->fields['institution_site_type_id']['type'] = 'select';
		$this->fields['institution_site_type_id']['options'] = $this->getSubOptions();
		$this->setFieldOrder('institution_site_type_id', 4);
		
		$this->fields['options'] = array(
			'type' => 'element',
			'element' => '../FieldOption/CustomField/options',
			'visible' => true
		);
		$this->setFieldOrder('options', 7);
		
		return $this->fields;
	}
	
	/*
	public function getRender($controller) {
		$views = array();
		
		$modelOption = $this->alias . 'Option';
		if ($controller->action == 'view') {
			$data = $controller->viewVars['data'];
			$id = $data[$this->alias]['id'];
			$options = $this->{$modelOption}->findAllByInstitutionSiteCustomFieldId($id, array(), array("$modelOption.visible" => 'DESC', "$modelOption.order"));
			foreach ($options as $obj) {
				$data[$modelOption][] = $obj[$modelOption];
			}
			$controller->set('data', $data);
		} else if ($controller->action == 'edit') {
			if ($controller->request->is('get')) {
				$data = $controller->request->data;
				$id = $data[$this->alias]['id'];
				
				$options = $this->{$modelOption}->findAllByInstitutionSiteCustomFieldId($id, array(), array("$modelOption.visible" => 'DESC', "$modelOption.order"));
				foreach ($options as $obj) {
					$controller->request->data[$modelOption][] = $obj[$modelOption];
				}
			}
		}
		
		return $views;
	}
	
	public function postAdd($controller) {
		$selectedOption = $controller->params->pass[0];
		$modelOption = $this->alias . 'Option';
		if (isset($controller->request->data['submit'])) {
			$submit = $controller->request->data['submit'];
			
			switch ($submit) {
				case $modelOption:
					$obj = array('value' => '');
					if (!isset($controller->request->data[$submit])) {
						$controller->request->data[$submit] = array();
					}
					
					$obj['order'] = count($controller->request->data[$submit]);
					$controller->request->data[$submit][] = $obj;
					break;
					
				case 'Save':
					$data = $controller->request->data;
					
					$models = array($modelOption);
					// remove all records that doesn't have values
					foreach ($models as $m) {
						if (isset($data[$m])) {
							$x = $data[$m];
							foreach ($x as $i => $obj) {
								if (empty($obj['value'])) {
									unset($controller->request->data[$m][$i]);
								}
							}
						}
					}
					if ($this->saveAll($controller->request->data)) {
						$controller->Message->alert('general.add.success');
						return $controller->redirect(array('controller' => $controller->name, 'action' => 'view', $selectedOption, $this->getLastInsertID()));
					} else {
						$this->log($this->validationErrors, 'error');
						$controller->Message->alert('general.add.failed');
					}
					break;
				
				default:
					break;
			}
		}
		return true;
	}
	
	public function postEdit($controller) {
		$selectedOption = $controller->params->pass[0];
		$modelOption = $this->alias . 'Option';
		if (isset($controller->request->data['submit'])) {
			$submit = $controller->request->data['submit'];
			
			switch ($submit) {
				case $modelOption:
					$obj = array('value' => '');
					if (!isset($controller->request->data[$submit])) {
						$controller->request->data[$submit] = array();
					}
					$obj['order'] = count($controller->request->data[$submit]);
					$controller->request->data[$submit][] = $obj;
					break;
					
				case 'Save':
					$data = $controller->request->data;
					$id = $data[$this->alias]['id'];
					$models = array($modelOption);
					foreach ($models as $m) {
						if (isset($data[$m])) {
							$x = $data[$m];
							foreach ($x as $i => $obj) {
								if (empty($obj['value'])) {
									unset($controller->request->data[$m][$i]);
								}
							}
						}
					}
					
					if ($this->saveAll($controller->request->data)) {
						$controller->Message->alert('general.edit.success');
						return $controller->redirect(array('controller' => $controller->name, 'action' => 'view', $selectedOption, $id));
					} else {
						$this->log($this->validationErrors, 'error');
						$controller->Message->alert('general.edit.failed');
					}
					break;
				
				default:
					break;
			}
		}
		return true;
	}
	*/
	
	public function getConditionId() {
		return 'institution_site_type_id';
	}
	
	public function findList($options=array()) {
		$_options = array(
			'conditions' => array()
		);
		if(!empty($options)) {
			$_options = array_merge($_options, $options);
		}
		$class = $this->alias;
		$data = $this->find('all', array(
			'recursive' => 0,
			'conditions' => $_options['conditions'],
			'order' => array('InstitutionSiteType.order', $class . '.order')
		));
		$list = array();
		foreach($data as $obj) {
			$field = $obj[$class];
			$siteType = $obj['InstitutionSiteType'];
			$typeName = __('All');
			if($field['institution_site_type_id'] > 0) {
				$typeName = $siteType['name'];
			}
			if(!array_key_exists($typeName, $list)) {
				$list[$typeName] = array();
			}
			$list[$typeName][$field['id']] = $field['name'];
		}
		return $list;
	}
	
	public function additional($controller, $params) {
        $controller->Navigation->addCrumb('More');
		
		$this->unbindModel(array('hasMany' => array('InstitutionSiteCustomValue')));
        $data = $this->find('all', array(
			'conditions' => array(
				'InstitutionSiteCustomField.visible' => 1, 
				'InstitutionSiteCustomField.institution_site_type_id' => (array($controller->institutionSiteObj['InstitutionSite']['institution_site_type_id'], 0))
			),
			'order' => array('InstitutionSiteCustomField.institution_site_type_id', 'InstitutionSiteCustomField.order')
		));
        $this->InstitutionSiteCustomValue->unbindModel(array('belongsTo' => array('InstitutionSite')));
        $dataValues = $this->InstitutionSiteCustomValue->find('all', array('conditions' => array('InstitutionSiteCustomValue.institution_site_id' => $controller->institutionSiteId)));
        $tmp = array();
        foreach ($dataValues as $obj) {
            $tmp[$obj['InstitutionSiteCustomField']['id']][] = $obj['InstitutionSiteCustomValue'];
        }
        $dataValues = $tmp;
        $controller->set('data', $data);
        $controller->set('dataValues', $tmp);
    }
	
	public function additionalEdit($controller, $params) {
        $controller->Navigation->addCrumb('Edit More');

        if ($controller->request->is('post')) {
            //pr($this->data);
            //die();
            $arrFields = array('textbox', 'dropdown', 'checkbox', 'textarea');
           
            // Note to Preserve the Primary Key to avoid exhausting the max PK limit
             
			
            foreach ($arrFields as $fieldVal) {
                if (!isset($controller->request->data['InstitutionSiteCustomValue'][$fieldVal]))
                    continue;
                foreach ($controller->request->data['InstitutionSiteCustomValue'][$fieldVal] as $key => $val) {
                    if ($fieldVal == "checkbox") {
                        $arrCustomValues = $this->InstitutionSiteCustomValue->find('list', array('fields' => array('value'), 'conditions' => array('InstitutionSiteCustomValue.institution_site_id' => $controller->institutionSiteId, 'InstitutionSiteCustomValue.institution_site_custom_field_id' => $key)));

                        $tmp = array();
                        if (count($arrCustomValues) > count($val['value'])) //if db has greater value than answer, remove
                            foreach ($arrCustomValues as $pk => $intVal) {
                                //pr($val['value']); echo "$intVal";
                                if (!in_array($intVal, $val['value'])) {
                                    //echo "not in db so remove \n";
                                    $this->InstitutionSiteCustomValue->delete($pk);
                                }
                            }
                        $ctr = 0;
                        if (count($arrCustomValues) < count($val['value'])) //if answer has greater value than db, insert
                            foreach ($val['value'] as $intVal) {
                                //pr($val['value']); echo "$intVal";
                                if (!in_array($intVal, $arrCustomValues)) {
                                    $this->InstitutionSiteCustomValue->create();
                                    $arrV['institution_site_custom_field_id'] = $key;
                                    $arrV['value'] = $val['value'][$ctr];
                                    $arrV['institution_site_id'] = $controller->institutionSiteId;
                                    $this->InstitutionSiteCustomValue->save($arrV);
                                    unset($arrCustomValues[$ctr]);
                                }
                                $ctr++;
                            }
                    } else { // if editing reuse the Primary KEY; so just update the record
                        $x = $this->InstitutionSiteCustomValue->find('first', array('fields' => array('id', 'value'), 'conditions' => array('InstitutionSiteCustomValue.institution_site_id' => $controller->institutionSiteId, 'InstitutionSiteCustomValue.institution_site_custom_field_id' => $key)));
                        $this->InstitutionSiteCustomValue->create();
                        if ($x)
                            $this->InstitutionSiteCustomValue->id = $x['InstitutionSiteCustomValue']['id'];
                        $arrV['institution_site_custom_field_id'] = $key;
                        $arrV['value'] = $val['value'];
                        $arrV['institution_site_id'] = $controller->institutionSiteId;
						
                        $this->InstitutionSiteCustomValue->save($arrV);
                    }
                }
            }
            $controller->redirect(array('action' => 'additional'));
        }
		
		$this->unbindModel(array('hasMany' => array('InstitutionSiteCustomValue')));
        $data = $this->find('all', array('conditions' => array('InstitutionSiteCustomField.visible' => 1, 'InstitutionSiteCustomField.institution_site_type_id' => (array($controller->institutionSiteObj['InstitutionSite']['institution_site_type_id'], 0))), 'order' => array('InstitutionSiteCustomField.institution_site_type_id', 'InstitutionSiteCustomField.order')));

        $this->InstitutionSiteCustomValue->unbindModel(array('belongsTo' => array('InstitutionSite')));
        $dataValues = $this->InstitutionSiteCustomValue->find('all', array('conditions' => array('InstitutionSiteCustomValue.institution_site_id' => $controller->institutionSiteId)));
        $tmp = array();
        foreach ($dataValues as $obj) {
            $tmp[$obj['InstitutionSiteCustomField']['id']][] = $obj['InstitutionSiteCustomValue'];
        }
        $dataValues = $tmp;
        $controller->set('data', $data);
        $controller->set('dataValues', $tmp);
    }
}
