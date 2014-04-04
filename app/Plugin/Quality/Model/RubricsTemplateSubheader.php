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

class RubricsTemplateSubheader extends QualityAppModel {

    //public $useTable = 'rubrics';
    public $actsAs = array('ControllerAction');
    public $belongsTo = array(
        //'Student',
        'RubricsTemplateHeader' => array(
            'foreignKey' => 'rubric_template_header_id'
        ),
        'ModifiedUser' => array(
            'className' => 'SecurityUser',
            'foreignKey' => 'modified_user_id'
        ),
        'CreatedUser' => array(
            'className' => 'SecurityUser',
            'foreignKey' => 'created_user_id'
        )
    );
    //public $hasMany = array('RubricsTemplateColumnInfo');
    public $rubricTemplateHeaderData = array();
    public $rubricId;
    public $validate = array(
        'title' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please enter a valid Title.'
            )
        )/* ,
              'pass_mark' => array(
              'ruleRequired' => array(
              'rule' => 'notEmpty',
              'required' => true,
              'message' => 'Please enter a valid Pass Mark.'
              )
              ) */
    );

    public function beforeAction($controller, $action) {
        $rubricTemplateHeaderId = empty($controller->params['pass'][0]) ? '' : $controller->params['pass'][0];
        if (empty($rubricTemplateHeaderId)) {
            return $controller->redirect(array('action' => 'rubricsTemplates'));
        }

        $RubricsTemplateHeader = ClassRegistry::init('Quality.RubricsTemplateHeader');
        $this->rubricTemplateHeaderData = $RubricsTemplateHeader->getRubricTemplate($rubricTemplateHeaderId);
        $this->rubricId = $this->rubricTemplateHeaderData['RubricsTemplateHeader']['rubric_template_id'];

        $sectionHeaderTitle = trim($this->rubricTemplateHeaderData['RubricsTemplateHeader']['title']);


        $RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');
        $rubricTemplateData = $RubricsTemplate->getRubric($this->rubricId);
        $rubricName = trim($rubricTemplateData['RubricsTemplate']['name']);

        $controller->Navigation->addCrumb('Rubric', array('controller' => 'Quality', 'action' => 'rubricsTemplates', 'plugin' => 'Quality'));
        $controller->Navigation->addCrumb($rubricName, array('controller' => 'Quality', 'action' => 'rubricsTemplatesHeader', $this->rubricId, 'plugin' => 'Quality'));

        $controller->set('modelName', $this->name);

        if ($action == 'rubricsTemplatesSubheaderView') {
            $controller->Navigation->addCrumb($sectionHeaderTitle);
        }
    }

    public function rubricsTemplatesSubheaderView($controller, $params) {
        $this->_setupRubricsTemplateDetail($controller, $params, 'view');
    }

    public function rubricsTemplatesSubheaderEdit($controller, $params) {
        $controller->Navigation->addCrumb('Edit - ' . $this->rubricTemplateHeaderData['RubricsTemplateHeader']['title']);
        $controller->set('modelName', $this->name);

        $this->_setupRubricsTemplateDetail($controller, $params, 'edit');
    }

    private function _setupRubricsTemplateDetail($controller, $params, $type) {
        $rubricTemplateHeaderId = empty($params['pass'][0]) ? 0 : $params['pass'][0];
        $data = array();

        $RubricsTemplateHeader = ClassRegistry::init('Quality.RubricsTemplateHeader');
        $rubricTemplateHeaderData = $RubricsTemplateHeader->getRubricTemplate($rubricTemplateHeaderId);

        // $rubricTemplateId = $rubricTemplateHeaderData['RubricsTemplateHeader']['rubric_template_id'];
        $headerCounter = $this->rubricTemplateHeaderData['RubricsTemplateHeader']['order'];

        $controller->set('subheader', $this->rubricTemplateHeaderData['RubricsTemplateHeader']['title']);
        //$controller->set('sectionHeaderTitle', $sectionHeaderTItle);

        $controller->Session->write('RubricsTemplate.id', $this->rubricId);
        $controller->Session->write('RubricsTemplateHeader.id', $rubricTemplateHeaderId);

        $RubricsTemplateColumnInfo = ClassRegistry::init('Quality.RubricsTemplateColumnInfo');
        $columnHeaderData = $RubricsTemplateColumnInfo->getColumnsData($this->rubricId);
        $count = 1 + count($columnHeaderData);
        $controller->set('columnHeaderData', $columnHeaderData);

        $controller->set('rubricTemplateId', $this->rubricId);
        $controller->set('rubricTemplateHeaderId', $rubricTemplateHeaderId);
        $controller->set('totalColumns', $count);
        //$controller->set('totalRows', 6);


        $RubricsTemplateItem = ClassRegistry::init('Quality.RubricsTemplateItem');
        $RubricsTemplateAnswer = ClassRegistry::init('Quality.RubricsTemplateAnswer');
        //  $RubricsTemplateSubheader = ClassRegistry::init('Quality.RubricsTemplateSubheader');

        $headerData = $this->find('all', array('conditions' => array('rubric_template_header_id' => $rubricTemplateHeaderId), 'recursive' => -1, 'order' => 'order')); //$this->processRubricsHeaderData($controller, $params);
        // pr($headerData);

        $subheaderCounter = 1;
        foreach ($headerData as $rowHeader) {
            //pr($rowHeader);
            if (!empty($rowHeader)) {
                $_tempSubHeaderDisplayNum = $headerCounter . '.' . $subheaderCounter;
                $rowHeader['RubricsTemplateSubheader']['display_num'] = $_tempSubHeaderDisplayNum;
                array_push($data, $rowHeader);


                //Retrive Questions 
                $questionsData = $RubricsTemplateItem->find('all', array('conditions' => array('rubric_template_subheader_id' => $rowHeader[$this->name]['id']), 'recursive' => -1));

                if (!empty($questionsData)) {
                    $itemCounter = 1;
                    foreach ($questionsData as $question) {
                        $question['RubricsTemplateItem']['display_num'] = $_tempSubHeaderDisplayNum . '.' . $itemCounter;
                        $ansData = $RubricsTemplateAnswer->find('all', array('conditions' => array('rubric_template_item_id' => $question['RubricsTemplateItem']['id']), 'recursive' => -1));

                        //$_tempData = array();
                        $_tempData = $question;
                        foreach ($ansData as $ansObj) {
                            $_tempData['RubricsTemplateAnswer'][] = $ansObj['RubricsTemplateAnswer'];
                        }
                        //$question['options'] = $ansData;
                        array_push($data, $_tempData);
                        $itemCounter++;
                    }
                }

                $subheaderCounter++;
            }
        }

        if ($controller->request->is('post')) {
            $headerData = array();
            $questionData = array();
            $optionsData = array();

            if (empty($controller->request->data['RubricsTemplateDetail'])) {
                $controllerData = array();
            } else {
                $controllerData = $controller->request->data['RubricsTemplateDetail'];
            }
            /*
              if (!empty($controllerData['RubricsTemplate'])) {
              unset($controllerData['RubricsTemplate']);
              }

              if (!empty($controllerData['last_id'])) {
              unset($controllerData['last_id']);
              } */

            // pr($controllerData); 
            $firstObj = array();
            foreach ($controllerData as $obj) {
                if (empty($firstObj)) {
                    $firstObj = $obj;
                }
                if (array_key_exists('RubricsTemplateSubheader', $obj)) {
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
            $this->set($headerData);
            $RubricsTemplateItem->set($questionData);
            $RubricsTemplateAnswer->set($optionsData);
            $saveError = false;


            if (isset($firstObj['RubricsTemplateSubheader']['title'])) {

                if (empty($headerData) || empty($questionData) || empty($optionsData)) {
                    $controller->Utility->alert('Please ensure both header and criteria are added.', array('type' => 'error'));
                } else if ($this->saveAll($headerData, array('validate' => 'only')) &&
                        $RubricsTemplateItem->saveAll($questionData, array('validate' => 'only')) &&
                        $RubricsTemplateAnswer->saveAll($optionsData, array('validate' => 'only'))) {


                    /* if (count($data) == count($controllerData)) {//Save all 
                      $this->saveAll($headerData, array('validate' => false));
                      $RubricsTemplateItem->saveAll($questionData, array('validate' => false));
                      $RubricsTemplateAnswer->saveAll($optionsData, array('validate' => false));
                      } else { */
                    //Save New entries 
                    $_tempHeaderId = 0;
                    $_tempQuestionId = 0;

                    foreach ($controllerData as $obj) {
                        if (array_key_exists('RubricsTemplateSubheader', $obj)) {
                            //array_push($headerData, $obj);
                            if (!array_key_exists('rubric_template_header_id', $obj['RubricsTemplateSubheader'])) {
                                $obj['RubricsTemplateSubheader']['rubric_template_header_id'] = $rubricTemplateHeaderId;
                            }
                            if (!isset($obj['RubricsTemplateSubheader']['id'])) {
                                $this->create();
                            }
                            // pr($obj);
                            if ($this->save($obj, false)) {
                                $_tempHeaderId = $this->id;
                            } else {
                                //show error
                                $saveError = true;
                                break;
                            }
                        } else {
                            if (array_key_exists('RubricsTemplateItem', $obj)) {
                                //if (!array_key_exists('rubric_template_subheader_id', $obj['RubricsTemplateItem'])) {
                                if (empty($_tempHeaderId)) {
                                    $saveError = true;
                                    break;
                                }
                                $obj['RubricsTemplateItem']['rubric_template_subheader_id'] = $_tempHeaderId;
                                // }

                                if (!isset($obj['RubricsTemplateItem']['id'])) {
                                    $RubricsTemplateItem->create();
                                }

                                if ($RubricsTemplateItem->save($obj, false)) {
                                    $_tempQuestionId = $RubricsTemplateItem->id;
                                } else {
                                    //show error
                                    $saveError = true;
                                    break;
                                }
                            }
                            if (array_key_exists('RubricsTemplateAnswer', $obj)) {
                                foreach ($obj['RubricsTemplateAnswer'] as $option) {
                                    if (!array_key_exists('rubric_template_item_id', $option)) {
                                        $option['rubric_template_item_id'] = $_tempQuestionId;
                                    }
                                    if (!isset($option['id'])) {
                                        $RubricsTemplateAnswer->create();
                                    }

                                    if ($RubricsTemplateAnswer->save($option, false)) {
                                        
                                    } else {

                                        $saveError = true;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    //  } //End Save New entries

                    if ($saveError) {
                        //show error
                        $controller->Utility->alert($controller->Utility->getMessage('ADD_UPDATE_ERROR'), array('type' => 'error'));
                    } else {
                        //show success
                        $controller->Utility->alert($controller->Utility->getMessage('UPDATE_SUCCESS'));
                    }
                    return $controller->redirect(array('action' => 'rubricsTemplatesSubheaderView', $rubricTemplateHeaderId));
                } else {
                    $controller->request->data['RubricsTemplateDetail'] = $controllerData;
                    $controller->Utility->alert($controller->Utility->getMessage('ADD_UPDATE_ERROR'), array('type' => 'error'));
                }
            } else {
                $controller->Utility->alert($controller->Utility->getMessage('RUBRIC_FIRST_POS'), array('type' => 'error'));
            }
        } else {// GET
            if (empty($controller->request->data['RubricsTemplateDetail'])) {

                $controller->request->data['RubricsTemplateDetail'] = $data;
                //pr($controller->request->data);

                if (empty($controller->request->data['RubricsTemplateDetail']) && $type != 'edit') {
                    $controller->Utility->alert($controller->Utility->getMessage('NO_RECORD'), array('type' => 'info'));
                }
            }
        }
    }

    public function rubricsTemplatesSubheaderAjaxAddRow($controller, $params) {

        if ($controller->request->is('ajax')) {
            $rubricTemplateHeaderId = !empty($controller->data['id']) ? $controller->data['id'] : 0;
            $lastId = !empty($controller->data['last_id']) ? $controller->data['last_id'] : 0;
            if (!empty($params['pass']) && $params['pass'][0]) {
                $type = $params['pass'][0];
                $controller->set('type', $type);
                $controller->set('lastId', $lastId);
                //	$controller->set('rubricId', $rubricId);
                $processItem = array();

                $RubricsTemplateHeader = ClassRegistry::init('Quality.RubricsTemplateHeader');
                $rubricTemplateHeaderData = $RubricsTemplateHeader->getRubricTemplate($rubricTemplateHeaderId);
                $rubricTemplateId = $rubricTemplateHeaderData['RubricsTemplateHeader']['rubric_template_id'];
                $RubricsTemplateColumnInfo = ClassRegistry::init('Quality.RubricsTemplateColumnInfo');
                if ($type == 'header') {
                    $processItem['RubricsTemplateSubheader'] = '';
                    // $processItem['RubricsTemplateSubheader']['rubric_template_header_id'] = $rubricTemplateHeaderId;

                    $totalColumns = $RubricsTemplateColumnInfo->getTotalCriteriaById($rubricTemplateId);
                    $controller->set('totalColumns', ($totalColumns + 1));
                } else {
                    $columnHeaderData = $RubricsTemplateColumnInfo->getColumnsData($rubricTemplateId);
                    $processItem['columnHeader'] = $columnHeaderData;

                    $count = 1 + count($columnHeaderData);
                    //$controller->set('columnHeaderData', $columnHeaderData);
                    $controller->set('totalColumns', $count);
                }
                //pr($processItem);
                $controller->set('processItem', $processItem);
                $controller->set('message', $controller->Utility->getMessage('RUBRIC_ROW_ADDED'));
            }
        }
    }

    public function rubricsTemplatesSubheaderDeleteAll($ids) {
        $this->unbindModel(array('belongsTo' => array('RubricsTemplateHeader')));
        $listOfHeaderIds = implode(',', $ids);
        //  pr($listOfHeaderIds);

        $data = $this->find('list', array('conditions' => array('rubric_template_header_id' => $listOfHeaderIds), 'fields' => array('id', 'id')));
        if (!empty($data)) {
            $RubricsTemplateItem = ClassRegistry::init('Quality.RubricsTemplateItem');
            $RubricsTemplateItem->rubricsTemplatesItemDeleteAll($data);

            foreach ($data as $obj) {
                // pr($obj);
               $this->delete($obj);
            }
        }
        // pr($data);
    }

    //Pagination
    /*  public function processRubricsHeaderData($controller, $params) {
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
      $RubricsTemplateSubheader = ClassRegistry::init('Quality.RubricsTemplateSubheader');
      $data = $RubricsTemplateSubheader->find('all', array(
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
