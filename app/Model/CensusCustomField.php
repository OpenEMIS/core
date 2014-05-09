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

class CensusCustomField extends AppModel {

	public $actsAs = array('FieldOption', 'ControllerAction');
	public $hasMany = array('CensusCustomFieldOption' => array('order' => 'order'));
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
		$siteTypeOptions = $this->getSubOptions();
		$fieldTypeOptions = $this->getCustomFieldTypes();
		$fieldType = array('field' => 'type', 'type' => 'select', 'options' => $fieldTypeOptions);
		$siteType = array('field' => $this->getConditionId(), 'type' => 'select', 'options' => $siteTypeOptions);
		$this->removeOptionFields(array('international_code', 'national_code'));
		$this->addOptionField($fieldType, 'after', 'name');
		$this->addOptionField($siteType, 'after', 'type');
		$fields = $this->Behaviors->dispatchMethod($this, 'getOptionFields');
		return $fields;
	}

	public function getConditionId() {
		return 'institution_site_type_id';
	}

	public function findList($options = array()) {
		$_options = array(
			'conditions' => array()
		);
		if (!empty($options)) {
			$_options = array_merge($_options, $options);
		}
		$class = $this->alias;
		$data = $this->find('all', array(
			'recursive' => 0,
			'conditions' => $_options['conditions'],
			'order' => array('InstitutionSiteType.order', $class . '.order')
		));
		$list = array();
		foreach ($data as $obj) {
			$field = $obj[$class];
			$siteType = $obj['InstitutionSiteType'];
			$typeName = __('All');
			if ($field['institution_site_type_id'] > 0) {
				$typeName = $siteType['name'];
			}
			if (!array_key_exists($typeName, $list)) {
				$list[$typeName] = array();
			}
			$list[$typeName][$field['id']] = $field['name'];
		}
		return $list;
	}

	public function otherforms($controller, $params) {
		$controller->Navigation->addCrumb('Other Forms');

		$yearList = $controller->SchoolYear->getYearList();
		$selectedYear = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : key($yearList);
		$arrCensusInfra = array();

		$p = $controller->InstitutionSite->field('institution_site_type_id', array('InstitutionSite.id' => $controller->institutionSiteId));
		$data = $controller->CensusGrid->find('all', array('conditions' => array('CensusGrid.institution_site_type_id' => array($p, 0), 'CensusGrid.visible' => 1), 'order' => array('CensusGrid.institution_site_type_id', 'CensusGrid.order')));

		//pr($data);
		foreach ($data as &$arrDataVal) {
			$dataAnswer = $controller->CensusGridValue->find('all', array('conditions' => array('CensusGridValue.institution_site_id' => $controller->institutionSiteId, 'CensusGridValue.census_grid_id' => $arrDataVal['CensusGrid']['id'], 'CensusGridValue.school_year_id' => $selectedYear)));

			$tmp = array();
			foreach ($dataAnswer as $arrV) {
				$tmp[$arrV['CensusGridValue']['census_grid_x_category_id']][$arrV['CensusGridValue']['census_grid_y_category_id']] = $arrV['CensusGridValue'];
			}
			$dataAnswer = $tmp;
			$arrDataVal['answer'] = $dataAnswer;
		}

		//pr($data);

		/*		 * *
		 * CustomFields
		 */
		$site = $controller->InstitutionSite->findById($controller->institutionSiteId);
		$datafields = $controller->CensusCustomField->find('all', array('conditions' => array('CensusCustomField.institution_site_type_id' => array($site['InstitutionSite']['institution_site_type_id'], 0)), 'order' => array('CensusCustomField.institution_site_type_id', 'CensusCustomField.order')));
		//$datafields = $controller->CensusCustomField->find('all',array('conditions'=>array('CensusCustomField.institution_site_type_id'=>$site['InstitutionSite']['institution_site_type_id']), 'order'=>'CensusCustomField.order'));
		//pr($datafields); echo "d2";
		$controller->CensusCustomValue->unbindModel(
				array('belongsTo' => array('InstitutionSite'))
		);
		$datavalues = $controller->CensusCustomValue->find('all', array('conditions' => array('CensusCustomValue.institution_site_id' => $controller->institutionSiteId, 'CensusCustomValue.school_year_id' => $selectedYear)));
		$tmp = array();
		foreach ($datavalues as $arrV) {
			$tmp[$arrV['CensusCustomField']['id']][] = $arrV['CensusCustomValue'];
		}
		$datavalues = $tmp;
		//pr($datafields);

		$controller->set('datafields', $datafields);
		$controller->set('datavalues', $tmp);
		$controller->set('data', $data);
		$controller->set('selectedYear', $selectedYear);
		$controller->set('yearList', $yearList);
		$controller->set('isEditable', $controller->CensusVerification->isEditable($controller->institutionSiteId, $selectedYear));
	}

	public function otherformsEdit($controller, $params) {
		$controller->Navigation->addCrumb('Edit Other Forms');

		if ($controller->request->is('post')) {
			//pr($controller->request->data);die;
			$schoolYearId = $controller->request->data['CensusGridValue']['school_year_id'];
			unset($controller->request->data['CensusGridValue']['school_year_id']);

			foreach ($controller->request->data['CensusGridValue'] as $k => &$arrVal) {
				if ($arrVal['value'] == '' && $arrVal['id'] == '') {
					unset($controller->request->data['CensusGridValue'][$k]);
				} elseif ($arrVal['value'] == '' && $arrVal['id'] != '') {//if there's an ID but value was set to blank == delete the record
					$controller->CensusGridValue->delete($arrVal['id']);
					unset($controller->request->data['CensusGridValue'][$k]);
				} else {
					$arrVal['school_year_id'] = $schoolYearId;
					$arrVal['institution_site_id'] = $controller->institutionSiteId;
				}
			}

			//pr($controller->request->data);die;
			if (count($controller->request->data['CensusGridValue']) > 0) {

				$controller->CensusGridValue->saveAll($controller->request->data['CensusGridValue']);
			}

			/**
			 * Note to Preserve the Primary Key to avoid exhausting the max PK limit
			 */
			$arrFields = array('textbox', 'dropdown', 'checkbox', 'textarea');
			foreach ($arrFields as $fieldVal) {
				if (!isset($controller->request->data['CensusCustomValue'][$fieldVal]))
					continue;
				foreach ($controller->request->data['CensusCustomValue'][$fieldVal] as $key => $val) {
					if ($fieldVal == "checkbox") {

						$arrCustomValues = $controller->CensusCustomValue->find('list', array('fields' => array('value'), 'conditions' => array('CensusCustomValue.school_year_id' => $schoolYearId, 'CensusCustomValue.institution_site_id' => $controller->institutionSiteId, 'CensusCustomValue.census_custom_field_id' => $key)));

						$tmp = array();
						if (count($arrCustomValues) > count($val['value'])) //if db has greater value than answer, remove
							foreach ($arrCustomValues as $pk => $intVal) {
								//pr($val['value']); echo "$intVal";
								if (!in_array($intVal, $val['value'])) {
									//echo "not in db so remove \n";
									$controller->CensusCustomValue->delete($pk);
								}
							}
						$ctr = 0;
						if (count($arrCustomValues) < count($val['value'])) { //if answer has greater value than db, insert
							//pr($arrCustomValues);pr($val['value']);echo $key;die;
							foreach ($val['value'] as $intVal) {
								//pr($val['value']); echo "$intVal";
								if (!in_array($intVal, $arrCustomValues)) {
									$controller->CensusCustomValue->create();
									$arrV['census_custom_field_id'] = $key;
									$arrV['value'] = $val['value'][$ctr];
									$arrV['school_year_id'] = $schoolYearId;
									$arrV['institution_site_id'] = $controller->institutionSiteId;
									$controller->CensusCustomValue->save($arrV);
									unset($arrCustomValues[$ctr]);
								}
								$ctr++;
							}
						}
					} else { // if editing reuse the Primary KEY; so just update the record
						$x = $controller->CensusCustomValue->find('first', array('fields' => array('id', 'value'), 'conditions' => array('CensusCustomValue.school_year_id' => $schoolYearId, 'CensusCustomValue.institution_site_id' => $controller->institutionSiteId, 'CensusCustomValue.census_custom_field_id' => $key)));


						$controller->CensusCustomValue->create();
						if ($x)
							$controller->CensusCustomValue->id = $x['CensusCustomValue']['id'];
						$arrV['census_custom_field_id'] = $key;
						$arrV['value'] = $val['value'];
						$arrV['school_year_id'] = $schoolYearId;
						$arrV['institution_site_id'] = $controller->institutionSiteId;

						$controller->CensusCustomValue->save($arrV);
					}
				}
			}
			$controller->redirect(array('action' => 'otherforms', $schoolYearId));
		}

		$arrCensusInfra = array();
		$yearList = $controller->SchoolYear->getAvailableYears();
		$selectedYear = $controller->getAvailableYearId($yearList);
		$editable = $controller->CensusVerification->isEditable($controller->institutionSiteId, $selectedYear);
		if (!$editable) {
			$controller->redirect(array('action' => 'otherforms', $selectedYear));
		} else {
			$p = $controller->InstitutionSite->field('institution_site_type_id', array('InstitutionSite.id' => $controller->institutionSiteId));
			$data = $controller->CensusGrid->find('all', array('conditions' => array('CensusGrid.institution_site_type_id' => array($p, 0), 'CensusGrid.visible' => 1), 'order' => array('CensusGrid.institution_site_type_id', 'CensusGrid.order')));
			//$data = $controller->CensusGrid->find('all',array('conditions'=>array('CensusGrid.institution_site_type_id'=>$p, 'CensusGrid.visible' => 1), 'order' => 'CensusGrid.order'));

			foreach ($data as &$arrDataVal) {
				$dataAnswer = $controller->CensusGridValue->find('all', array('conditions' => array('CensusGridValue.institution_site_id' => $controller->institutionSiteId, 'CensusGridValue.census_grid_id' => $arrDataVal['CensusGrid']['id'], 'CensusGridValue.school_year_id' => $selectedYear)));

				$tmp = array();
				foreach ($dataAnswer as $arrV) {
					$tmp[$arrV['CensusGridValue']['census_grid_x_category_id']][$arrV['CensusGridValue']['census_grid_y_category_id']] = $arrV['CensusGridValue'];
				}
				$dataAnswer = $tmp;
				$arrDataVal['answer'] = $dataAnswer;
			}

			/*			 * *
			 * CustomFields
			 */
			$site = $controller->InstitutionSite->findById($controller->institutionSiteId);
			//$data = $controller->CensusGrid->find('all',array('conditions'=>array('CensusGrid.institution_site_type_id'=>array($p,0), 'CensusGrid.visible' => 1), 'order' => array('CensusGrid.institution_site_type_id','CensusGrid.order')));
			//$datafields = $controller->CensusCustomField->find('all',array('conditions'=>array('CensusCustomField.institution_site_type_id'=>$site['InstitutionSite']['institution_site_type_id'])));
			$datafields = $controller->CensusCustomField->find('all', array('conditions' => array('CensusCustomField.institution_site_type_id' => array($site['InstitutionSite']['institution_site_type_id'], 0)), 'order' => array('CensusCustomField.institution_site_type_id', 'CensusCustomField.order')));
			//pr($datafields); echo "d2";
			$controller->CensusCustomValue->unbindModel(
					array('belongsTo' => array('InstitutionSite'))
			);
			$datavalues = $controller->CensusCustomValue->find('all', array('conditions' => array('CensusCustomValue.institution_site_id' => $controller->institutionSiteId, 'CensusCustomValue.school_year_id' => $selectedYear)));
			$tmp = array();
			foreach ($datavalues as $arrV) {
				$tmp[$arrV['CensusCustomField']['id']][] = $arrV['CensusCustomValue'];
			}
			$datavalues = $tmp;

			//pr($datafields);

			$controller->set('datafields', $datafields);
			$controller->set('datavalues', $tmp);
			$controller->set('data', $data);
			$controller->set('selectedYear', $selectedYear);
			$controller->set('yearList', $yearList);
		}
	}

}
