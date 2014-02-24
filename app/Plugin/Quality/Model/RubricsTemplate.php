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

class RubricsTemplate extends QualityAppModel {

    //public $useTable = 'rubrics';
    //public $belongsTo = array('RubricsTemplateHeader');
    public $actsAs = array('ControllerAction', 'Quality.RubricsSetup');
    public $belongsTo = array(
        //'Student',
        'ModifiedUser' => array(
            'className' => 'SecurityUser',
            'foreignKey' => 'modified_user_id'
        ),
        'CreatedUser' => array(
            'className' => 'SecurityUser',
            'foreignKey' => 'created_user_id'
        )
    );
    public $hasMany = array(
        'RubricsTemplateHeader' => array(
            'foreignKey' => 'rubric_template_id',
        ),
        'RubricsTemplateColumnInfo' => array(
            'foreignKey' => 'rubric_template_id',
        )
    );
    public $validate = array(
        'name' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please enter a valid Name.'
            )
        ),
        'pass_mark' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please enter a valid Pass Mark.'
            )
        )
    );
    public $weighthingsOptions = array(1 => 'Points', 2 => 'Percentage');

    public function rubricsTemplates($controller, $params) {
        $controller->Navigation->addCrumb('Rubrics');
        $controller->set('subheader', 'Quality - Rubrics');
        $controller->set('modelName', $this->name);

        $this->recursive = -1;
        $data = $this->find('all');

        $controller->set('data', $data);
    }

    public function rubricsTemplatesView($controller, $params) {
        $controller->Navigation->addCrumb('Rubric Infomations');
        $controller->set('subheader', 'Quality - Rubric Infomations');
        $controller->set('modelName', $this->name);

        $id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
        $data = $this->find('first', array('conditions' => array($this->name . '.id' => $id), 'recursive' => 0));
        //pr($data);
        if (empty($data)) {
            $controller->redirect(array('action' => 'rubricsTemplates'));
        }
        $controller->Session->write('RubricsTemplate.id', $id);
        $controller->set('data', $data);
        $controller->set('weighthingsOptions', $this->weighthingsOptions);
    }

    public function rubricsTemplatesAdd($controller, $params) {
        $controller->Navigation->addCrumb('Add Rubric');
        $controller->set('subheader', 'Quality - Add Rubric');
        $controller->set('modelName', $this->name);

        $this->setupRubricsTemplate($controller, $params, 'add');
    }

    public function rubricsTemplatesEdit($controller, $params) {
        $this->render = 'add';

        $controller->Navigation->addCrumb('Edit Rubric');
        $controller->set('subheader', 'Quality - Edit Rubric');
        $controller->set('modelName', $this->name);

        $this->setupRubricsTemplate($controller, $params, 'edit');
    }

    public function setupRubricsTemplate($controller, $params, $type) {
        $institutionId = $controller->Session->read('InstitutionId');

        $controller->set('weighthingsOptions', $this->weighthingsOptions);

        if ($controller->request->is('get')) {
            //pr($controller->request->data);
            $id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
            $controller->set('id', $id);
            $this->recursive = -1;
            $data = $this->findById($id);
            if (!empty($data)) {
                $controller->request->data = $data;
            } else {
                $controller->request->data[$this->name]['institution_id'] = $institutionId;
            }
        } else {//post
            // pr($controller->request->data);
            if ($this->saveAll($controller->request->data)) {

                if ($type == 'add') {
                    $controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
                    return $controller->redirect(array('action' => 'rubricsTemplates'));
                } else {
                    $controller->Utility->alert($controller->Utility->getMessage('UPDATE_SUCCESS'));
                    return $controller->redirect(array('action' => 'rubricsTemplatesView', $this->id));
                }
                //pr($controller->request->data);
            }
        }
    }

    public function rubricsTemplatesDelete($controller, $params) {
        if ($controller->Session->check('RubricsTemplate.id')) {
            $id = $controller->Session->read('RubricsTemplate.id');

            $data = $this->find('first', array('conditions' => array($this->name . '.id' => $id)));


            $name = $data[$this->name]['name'];

            $this->delete($id);
            $controller->Utility->alert($name . ' have been deleted successfully.');
            $controller->Session->delete('RubricsTemplate.id');
            $controller->redirect(array('action' => 'rubricsTemplates'));
        }
    }

    //SQL Function 
    /* public function getRubricsTemplateList($id, $mode = 'list') {
      $data = $this->find($mode, array('recursive' => -1, 'conditions' => array('institution_id' => $id)));
      return $data;
      } */

    public function getRubricOptions($order = 'name') {
        $data = $this->find('list', array('order' => 'RubricsTemplate.'.$order, 'recursive' => -1));

        return $data;
    }

    public function getRubric($id) {
        $data = $this->find('first', array('conditions' => array('id' => $id), 'recursive' => -1));

        return $data;
    }

    public function getRubricTemplateWeightingInfo() {

        $options['recursive'] = -1;
        $options['joins'] = array(
            array(
                'table' => 'rubrics_template_headers',
                'alias' => 'RubricTemplateHeader',
                'conditions' => array('RubricsTemplate.id = RubricTemplateHeader.rubric_template_id')
            ),
            array(
                'table' => 'rubrics_template_subheaders',
                'alias' => 'RubricTemplateSubheader',
                'type' => 'LEFT',
                'conditions' => array('RubricTemplateSubheader.rubric_template_header_id = RubricTemplateHeader.id')
            ),
            array(
                'table' => 'rubrics_template_items',
                'alias' => 'RubricTemplateItem',
                'type' => 'LEFT',
                'conditions' => array('RubricTemplateItem.rubric_template_subheader_id = RubricTemplateSubheader.id')
            )
        );
        $options['fields'] = array('RubricsTemplate.id', 'RubricsTemplate.weighthings', 'RubricsTemplate.pass_mark', 'Count(RubricTemplateItem.id) AS totalQuestion');
        $options['group'] = array('RubricsTemplate.id');
        $data = $this->find('all', $options);


        $RubricTemplateColumnInfo = ClassRegistry::init('Quality.RubricsTemplateColumnInfo');
        $weightings = $RubricTemplateColumnInfo->getMaxWeighting();

        $list = array();
        foreach ($data AS $obj) {
            $list[$obj['RubricsTemplate']['id']]['WeightingType'] = ($obj['RubricsTemplate']['weighthings'] == 1) ? 'point' : 'percent';
            $list[$obj['RubricsTemplate']['id']]['PassMark'] = $obj['RubricsTemplate']['pass_mark'];
            $list[$obj['RubricsTemplate']['id']]['TotalWeighting'] = $weightings[$obj['RubricsTemplate']['id']] * $obj[0]['totalQuestion'];
        }
        return $list;
    }

    public function getInstitutionQAReportHeader() {
        $header = array('School Year', 'Institution Site Name', 'Institution Site Code', 'Class', 'Grade');

        $RubricsTemplateHeader = ClassRegistry::init('Quality.RubricsTemplateHeader');
        $rubricOptions = $this->getRubricOptions('id');
//pr($rubricOptions);
        if (!empty($rubricOptions)) {
            foreach ($rubricOptions as $key => $item) {
                $headerOptions = $RubricsTemplateHeader->getRubricHeaders($key);
//pr($headerOptions);
                if (!empty($headerOptions)) {
                    array_unshift($headerOptions, 'Rubric Name');
                    $headerOptions[] = 'Total Weighting';
                    $headerOptions[] = 'Pass/Fail';
                    $header = array_merge($header, $headerOptions);
                }
            }

            $headerOptions = array();
            $headerOptions[] = 'Grand Total Weighting';
            //  $headerOptions[] = 'Pass/Fail';
            $header = array_merge($header, $headerOptions);
        }
       // pr($header);
        return $header;
        
    }

    public function processDataToCSVFormat($data) {
        $tempArray = array();
        $classId = '';
        $rubricName = '';
        $rubricId = '';
        $rubricCounter = 0;
        $rubricHeaderCounter = 0;

        $dataCount = count($data);

        $rubricTotal = 0;
        $rubricsGrandTotal = 0;

        $rubricTemplateWeightingInfo = $this->getRubricTemplateWeightingInfo();

        foreach ($data AS $num => $row) {
            $currentClassId = $row['InstitutionSiteClass']['id'];
            $currentRubricName = $row['RubricTemplate']['name'];
            $currentRubricId = $row['RubricTemplate']['id'];

            if (!empty($classId) && !empty($rubricName) && $classId == $currentClassId /* && $rubricName == $currentRubricName */) {

                foreach ($row as $key => $value) {
                    if ($key == 'RubricTemplate') {
                        if ($rubricName != $currentRubricName) {
                            $selectedWeightingInfo = $rubricTemplateWeightingInfo[$rubricId];
                            $passFail = 'Fail';
                            
                            //if ($selectedWeightingInfo['WeightingType'] == 'percent') {
                            $rubricTotal = round(($rubricTotal / $selectedWeightingInfo['TotalWeighting']) * 100, 2);
                            //}

                            if ($rubricTotal >= $selectedWeightingInfo['PassMark']) {
                                $passFail = 'Pass';
                            }

                            $rubricsGrandTotal += $rubricTotal;

                            $tempArray[$rubricCounter - 1]['TotalRubric' . '_' . $rubricHeaderCounter]['value'] = $rubricTotal;
                            $tempArray[$rubricCounter - 1]['PassFail' . '_' . $rubricHeaderCounter]['value'] = $passFail;
                            $tempArray[$rubricCounter - 1][$key . '_' . $rubricHeaderCounter]['name'] = $value['name'];

                            //   pr($rubricName . " || " . $currentRubricName);
                            $rubricName = $currentRubricName;
                            $rubricId = $currentRubricId;
                            $rubricTotal = 0;
                        }
                    }

                    if ($key == '0') {
                        $this->calculateRubricScore($value, $rubricTemplateWeightingInfo, $rubricHeaderCounter, $rubricId, $rubricCounter - 1, $rubricTotal, $tempArray);
                    }
                }
            } else {
                $classId = $currentClassId;
                $rubricHeaderCounter = 0; //Reset value

                $rubricId = $currentRubricId;
                foreach ($row as $key => $value) {
                    if ($key == '0') {
                        $this->calculateRubricScore($value, $rubricTemplateWeightingInfo, $rubricHeaderCounter, $rubricId, $rubricCounter, $rubricTotal, $tempArray);
                    } else if ($key == 'InstitutionSiteClass') {
                        $tempArray[$rubricCounter][$key]['name'] = $value['name'];
                    } else if ($key == 'RubricTemplate') {
                        if (!empty($rubricName) && $rubricName != $currentRubricName) {

                            $selectedWeightingInfo = $rubricTemplateWeightingInfo[$rubricId];
                            $passFail = 'Fail';
                            //if ($selectedWeightingInfo['WeightingType'] == 'percent') {
                            $rubricTotal = round(($rubricTotal / $selectedWeightingInfo['TotalWeighting']) * 100, 2);
                            // }
                            if ($rubricTotal >= $selectedWeightingInfo['PassMark']) {
                                $passFail = 'Pass';
                            }
                            $rubricsGrandTotal += $rubricTotal;

                            //if ($selectedWeightingInfo['WeightingType'] == 'percent') {
                            $rubricsGrandTotal = round($rubricsGrandTotal / count($rubricTemplateWeightingInfo), 2);
                            //}

                            $tempArray[$rubricCounter - 1]['TotalRubric' . '_' . $rubricHeaderCounter]['value'] = $rubricTotal;
                            $tempArray[$rubricCounter - 1]['PassFail' . '_' . $rubricHeaderCounter]['value'] = $passFail;
                            $tempArray[$rubricCounter - 1]['GrandTotal']['value'] = $rubricsGrandTotal;

                            $rubricTotal = 0;
                            $rubricsGrandTotal = 0;
                        }
                        $tempArray[$rubricCounter][$key . '_' . $rubricHeaderCounter]['name'] = $value['name'];
                    } else if ($key == 'RubricTemplateHeader') {
                        
                    } else {
                        $tempArray[$rubricCounter][$key] = $value;
                    }
                }
                $rubricName = $currentRubricName;

                $rubricCounter = count($tempArray);
            }
            $rubricHeaderCounter ++;

            if ($num == $dataCount - 1) {
                $passFail = 'Fail';
                $selectedWeightingInfo = $rubricTemplateWeightingInfo[$rubricId];
                
                if ($selectedWeightingInfo['WeightingType'] == 'percent') {
                    $rubricTotal = ($rubricTotal / $selectedWeightingInfo['TotalWeighting']) * 100;
                }

                if ($rubricTotal >= $selectedWeightingInfo['PassMark']) {
                    $passFail = 'Pass';
                }

                $rubricsGrandTotal += $rubricTotal;
                if ($selectedWeightingInfo['WeightingType'] == 'percent') {
                    $rubricsGrandTotal = round($rubricsGrandTotal / count($rubricTemplateWeightingInfo), 2);
                }

                $tempArray[$rubricCounter - 1]['TotalRubric' . '_' . $rubricHeaderCounter]['value'] = $rubricTotal;
                $tempArray[$rubricCounter - 1]['PassFail' . '_' . $rubricHeaderCounter]['value'] = $passFail;
                $tempArray[$rubricCounter - 1]['GrandTotal']['value'] = $rubricsGrandTotal;
            }
        }


        return $tempArray;
    }

    private function calculateRubricScore($value, $rubricTemplateWeightingInfo, $rubricHeaderCounter, $rubricId, $rubricCounter, &$rubricTotal, &$tempArray) {
        foreach ($value as $sumValue) {
            $_sumValue = (empty($sumValue) ? 0 : $sumValue);
            $rubricTotal += $_sumValue;
            $selectedWeightingInfo = $rubricTemplateWeightingInfo[$rubricId];
            // if ($selectedWeightingInfo['WeightingType'] == 'percent') {
            $_sumValue = round(($_sumValue / $selectedWeightingInfo['TotalWeighting']) * 100, 2);
            //}
            $tempArray[$rubricCounter]['total_' . $rubricHeaderCounter]['value'] = $_sumValue;
        }
    }

    /*
      public function rubricsTemplatesDetailsView($controller, $params) {
      $controller->Navigation->addCrumb('Rubric Details');
      $controller->set('subheader', 'Quality - Rubric Details');
      $controller->set('modelName', $this->name);

      $this->setupRubricsTemplateDetail($controller, $params);
      }

      public function rubricsTemplatesDetailsEdit($controller, $params) {
      $controller->Navigation->addCrumb('Edit Rubric Details');
      $controller->set('subheader', 'Quality - Edit Rubric Details');
      $controller->set('modelName', $this->name);

      $this->setupRubricsTemplateDetail($controller, $params);
      }

      private function setupRubricsTemplateDetail($controller, $params) {
      $id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
      $data = array();

      $RubricsTemplateColumnInfo = ClassRegistry::init('Quality.RubricsTemplateColumnInfo');
      $columnHeaderData = $RubricsTemplateColumnInfo->getColumnsData($id);
      $count = 1 + count($columnHeaderData);
      $controller->set('columnHeaderData', $columnHeaderData);

      $controller->set('id', $id);
      $controller->set('totalColumns', $count);
      //$controller->set('totalRows', 6);

      $headerData = $this->_processRubricsHeaderData($controller, $params);

      $RubricsTemplateItem = ClassRegistry::init('Quality.RubricsTemplateItem');
      $RubricsTemplateAnswer = ClassRegistry::init('Quality.RubricsTemplateAnswer');
      $RubricsTemplateHeader = ClassRegistry::init('Quality.RubricsTemplateHeader');

      foreach ($headerData as  $rowHeader) {
      //pr($rowHeader);
      if (!empty($rowHeader)) {
      array_push($data, $rowHeader);

      //Retrive Questions
      $questionsData = $RubricsTemplateItem->find('all', array('conditions' => array('rubric_template_header_id' => $rowHeader['RubricsTemplateHeader']['id']), 'recursive' => -1));

      if (!empty($questionsData)) {
      //pr($questionsData);
      foreach ($questionsData as $question) {
      $ansData = $RubricsTemplateAnswer->find('all', array('conditions' => array('rubric_template_item_id' => $question['RubricsTemplateItem']['id']), 'recursive' => -1));

      //$_tempData = array();
      $_tempData = $question;
      foreach ($ansData as $ansObj) {
      $_tempData['RubricsTemplateAnswer'][] = $ansObj['RubricsTemplateAnswer'];
      }
      //$question['options'] = $ansData;
      array_push($data, $_tempData);
      }
      }
      }
      }

      if ($controller->request->is('post')) {
      $headerData = array();
      $questionData = array();
      $optionsData = array();

      if (!empty($controller->request->data['RubricsTemplate'])) {
      unset($controller->request->data['RubricsTemplate']);
      }
      //	pr($controller->request->data);
      foreach ($controller->request->data as $obj) {
      if (array_key_exists('RubricsTemplateHeader', $obj)) {
      array_push($headerData, $obj);
      } else {
      if (array_key_exists('RubricsTemplateItem', $obj)) {
      $questionData[]['RubricsTemplateItem'] = $obj['RubricsTemplateItem'];
      }
      if (array_key_exists('RubricsTemplateAnswer', $obj)) {
      foreach ($obj['RubricsTemplateAnswer'] as $option) {
      $optionsData[]['RubricsTemplateAnswer'] = $option;
      }
      }
      }
      }

      $RubricsTemplateHeader->set($headerData);
      $RubricsTemplateItem->set($questionData);
      $RubricsTemplateAnswer->set($optionsData);

      if ($RubricsTemplateHeader->saveAll($headerData, array('validate' => 'only')) &&
      $RubricsTemplateItem->saveAll($questionData, array('validate' => 'only')) &&
      $RubricsTemplateAnswer->saveAll($optionsData, array('validate' => 'only'))) {
      //Save all
      if (count($data) == count($controller->request->data)) {
      $RubricsTemplateHeader->saveAll($headerData, array('validate' => false));
      $RubricsTemplateItem->saveAll($questionData, array('validate' => false));
      $RubricsTemplateAnswer->saveAll($optionsData, array('validate' => false));
      return $controller->redirect(array('action' => 'rubricsTemplatesDetailsView', $id));
      } else {
      pr("New logic on saving");
      //pr($controller->request->data);

      //Half way donw on the saving
      $_tempHeaderId = 0;
      $_tempQuestionId = 0;
      foreach ($controller->request->data as $obj) {
      if (array_key_exists('RubricsTemplateHeader', $obj)) {
      //array_push($headerData, $obj);
      if (!array_key_exists('rubric_template_id', $obj['RubricsTemplateHeader'])) {
      $obj['RubricsTemplateHeader']['rubric_template_id'] = $id;
      }
      if (!isset($obj['RubricsTemplateHeader']['id'])) {
      $RubricsTemplateHeader->create();
      }
      $RubricsTemplateHeader->save($obj, false);
      $_tempHeaderId = $RubricsTemplateHeader->id;
      //							pr('-- Save Header --');
      //							pr('Header id : '.$_tempHeaderId);
      } else {
      if (array_key_exists('RubricsTemplateItem', $obj)) {
      if (!array_key_exists('rubric_template_header_id', $obj['RubricsTemplateItem'])) {
      $obj['RubricsTemplateItem']['rubric_template_header_id'] = $_tempHeaderId;
      }

      if (!isset($obj['RubricsTemplateItem']['id'])) {
      $RubricsTemplateItem->create();
      }

      $RubricsTemplateItem->save($obj, false);
      $_tempQuestionId = $RubricsTemplateItem->id;
      //								pr('-- Save Question --');
      //								pr('Question id : '.$_tempQuestionId);
      //pr($obj);
      //$questionData[]['RubricsTemplateItem'] = $obj['RubricsTemplateItem'];
      }
      if (array_key_exists('RubricsTemplateAnswer', $obj)) {
      foreach ($obj['RubricsTemplateAnswer'] as $option) {
      if (!array_key_exists('rubric_template_item_id', $option)) {
      $option['rubric_template_item_id'] = $_tempQuestionId;
      }
      if (!isset($option['id'])) {
      $RubricsTemplateAnswer->create();
      }

      $RubricsTemplateAnswer->save($option, false);
      //									pr('-- Save Answer --');
      //	$optionsData[]['RubricsTemplateAnswer'] = $option;
      }
      }
      }
      }
      }

      // return $controller->redirect(array('action'=> 'rubricsTemplatesDetailsView', $id));
      } else {
      $controller->Utility->alert($controller->Utility->getMessage('ADD_UPDATE_ERROR'), array('type' => 'error'));
      }
      } else {
      if (empty($controller->request->data)) {
      $controller->request->data = $data;
      }
      }
      }

      public function rubricsTemplatesAjaxAddRow($controller, $params) {

      if ($controller->request->is('ajax')) {
      $rubricId = !empty($controller->data['rubric_id']) ? $controller->data['rubric_id'] : 0;
      $lastId = !empty($controller->data['last_id']) ? $controller->data['last_id'] : 0;
      if (!empty($params['pass']) && $params['pass'][0]) {
      $type = $params['pass'][0];
      $controller->set('type', $type);
      $controller->set('lastId', $lastId);
      //	$controller->set('rubricId', $rubricId);
      $processItem = array();
      $processItem['rubric_template_id'] = $rubricId;

      $RubricsTemplateColumnInfo = ClassRegistry::init('Quality.RubricsTemplateColumnInfo');
      if ($type == 'header') {
      $totalColumns = $RubricsTemplateColumnInfo->getTotalCriteriaById($rubricId);
      $controller->set('totalColumns', ($totalColumns + 1));
      } else {
      $columnHeaderData = $RubricsTemplateColumnInfo->getColumnsData($rubricId);
      $processItem['columnHeader'] = $columnHeaderData;

      $count = 1 + count($columnHeaderData);
      //$controller->set('columnHeaderData', $columnHeaderData);
      $controller->set('totalColumns', $count);
      }
      //pr($processItem);
      $controller->set('processItem', $processItem);
      }
      }
      }

      private function _processRubricsHeaderData($controller, $params) {
      $id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
      $controller->paginate['page'] = empty($params['named']['page']) ? 1 : $params['named']['page'];
      //pr($controller->paginate);
      $controller->Paginator->settings = $controller->paginate;
      $data = $controller->paginate($this->name, array('rubric_template_id' => $id));

      return $data;
      }

      public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
      //pr($conditions);
      $RubricsTemplateHeader = ClassRegistry::init('Quality.RubricsTemplateHeader');
      $data = $RubricsTemplateHeader->find('all', array(
      'recursive' => -1,
      //'fields' => $fields,
      'joins' => array(),
      'conditions' => $conditions,
      'limit' => $limit,
      'offset' => (($page - 1) * $limit),
      'group' => null,
      'order' => $order
      ));

      return $data;
      }

      public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {

      } */
}
