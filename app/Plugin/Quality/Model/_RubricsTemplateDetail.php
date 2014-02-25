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

class RubricsTemplateDetail extends QualityAppModel {

    public $useTable = false;
    public $actsAs = array('ControllerAction');

    /* public $validate = array(
      'title' => array(
      'ruleRequired' => array(
      'rule' => 'notEmpty',
      'required' => true,
      'message' => 'Please enter a valid Title.'
      )
      )
      ); */

    public function rubricsTemplatesDetail($controller, $params) {
        $id = empty($params['pass'][0]) ? '' : $params['pass'][0];
        
        if(empty($id)){
            return $controller->redirect(array('action'=>'rubricsTemplates'));
        }
        
        /*$RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');
        $rubricTemplateInfo = $RubricsTemplate->getRubric($id);*/
       
        $controller->Navigation->addCrumb('Rubric', array('controller'=> 'Quality', 'action'=> 'rubricsTemplatesView',$id, 'plugin'=> 'Quality'));
        $controller->Navigation->addCrumb('Header List');
        $controller->set('subheader', 'Quality - Rubric Header List');
        $controller->set('modelName', $this->name);
     
    }

    public function rubricsTemplatesDetailView($controller, $params) {
        $controller->Navigation->addCrumb('Rubric Details');
        $controller->set('subheader', 'Quality - Rubric Details');
        $controller->set('modelName', $this->name);

        $this->SetupRubricsTemplateDetail($controller, $params);
    }

    public function rubricsTemplatesDetailEdit($controller, $params) {
        $controller->Navigation->addCrumb('Edit Rubric Details');
        $controller->set('subheader', 'Quality - Edit Rubric Details');
        $controller->set('modelName', $this->name);

        $this->SetupRubricsTemplateDetail($controller, $params);
    }

    private function SetupRubricsTemplateDetail($controller, $params) {
        $id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
        $data = array();

        $RubricsTemplateColumnInfo = ClassRegistry::init('Quality.RubricsTemplateColumnInfo');
        $columnHeaderData = $RubricsTemplateColumnInfo->getColumnsData($id);
        $count = 1 + count($columnHeaderData);
        $controller->set('columnHeaderData', $columnHeaderData);

        $controller->set('id', $id);
        $controller->set('totalColumns', $count);
        //$controller->set('totalRows', 6);


        $RubricsTemplateItem = ClassRegistry::init('Quality.RubricsTemplateItem');
        $RubricsTemplateAnswer = ClassRegistry::init('Quality.RubricsTemplateAnswer');
        $RubricsTemplateHeader = ClassRegistry::init('Quality.RubricsTemplateHeader');

        $headerData = $this->processRubricsHeaderData($controller, $params);

        foreach ($headerData as $rowHeader) {
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

             //  pr($controller->request->data); die;
            $controllerData = $controller->request->data['RubricsTemplateDetail'];

            if (!empty($controllerData['RubricsTemplate'])) {
                unset($controllerData['RubricsTemplate']);
            }

            if (!empty($controllerData['last_id'])) {
                unset($controllerData['last_id']);
            }
            //	pr($controllerData);
            foreach ($controllerData as $obj) {
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

                // pr('pass validation'); die;
                //Save all 
                if (count($data) == count($controllerData)) {
                    $RubricsTemplateHeader->saveAll($headerData, array('validate' => false));
                    $RubricsTemplateItem->saveAll($questionData, array('validate' => false));
                    $RubricsTemplateAnswer->saveAll($optionsData, array('validate' => false));
                    return $controller->redirect(array('action' => 'RubricsTemplatesDetailView', $id));
                } else {
                  //  pr("New logic on saving");
                    //pr($controllerData);
                    $_tempHeaderId = 0;
                    $_tempQuestionId = 0;
                    foreach ($controllerData as $obj) {
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
                $controller->request->data['RubricsTemplateDetail'] = $controllerData;
                $controller->Utility->alert($controller->Utility->getMessage('ADD_UPDATE_ERROR'), array('type' => 'error'));
            }
        } else {
            if (empty($controller->request->data['RubricsTemplateDetail'])) {

                $controller->request->data['RubricsTemplateDetail'] = $data;
                //pr($controller->request->data);
            }
        }
    }

    public function rubricsTemplatesDetailAjaxAddRow($controller, $params) {

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

    public function processRubricsHeaderData($controller, $params) {
        $id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
        $controller->paginate['page'] = empty($params['named']['page']) ? 1 : $params['named']['page'];
        // pr($this->name);
        // pr($id);
        $controller->Paginator->settings = $controller->paginate;

        $data = $controller->paginate($this->name, array('rubric_template_id' => $id));
        //pr($data);die;
        return $data;
    }

    public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
        //pr($conditions);die;
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
        
    }

}
