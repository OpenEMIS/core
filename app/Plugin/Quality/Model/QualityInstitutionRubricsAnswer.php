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

class QualityInstitutionRubricsAnswer extends QualityAppModel {

    public $actsAs = array('ControllerAction');
    public $belongsTo = array(
        //'Student',
        'QualityInstitutionRubric' => array(
            'foreignKey' => 'quality_institution_rubric_id'
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

    public $validate = array(
        'rubric_template_answer_id' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                //  'required' => true,
                'message' => 'Please select a valid Name.'
            )
        ),
    );

    public function beforeAction($controller, $action) {
        $rubricTemplateId = empty($controller->params['pass'][1]) ? '' : $controller->params['pass'][1];
        
        if (empty($rubricTemplateId)){
            return $controller->redirect(array('action' => 'qualityRubric'));
        }
                
        $RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');
        $rubricTemplateData = $RubricsTemplate->getRubric($rubricTemplateId);
        
        if (empty($rubricTemplateData)){
            return $controller->redirect(array('action' => 'qualityRubric'));
        }
                
        $rubricName = trim($rubricTemplateData['RubricsTemplate']['name']);
        
      //  $controller->Navigation->addCrumb('Rubrics', array('controller' => 'Quality', 'action' => 'qualityRubric', 'plugin' => 'Quality'));
        $controller->Navigation->addCrumb($rubricName, array('controller' => 'Quality', 'action' => 'qualityRubricView',$rubricTemplateId, 'plugin' => 'Quality'));
        
    }
    public function qualityRubricAnswerView($controller, $params) {
        $controller->set('modelName', $this->name);
        
        $controller->set('editable', $controller->Session->read('QualityRubric.editable'));
        $this->_SetupRubricsTemplateDetail($controller, $params);
    }

    private function _SetupRubricsTemplateDetail($controller, $params) {

        $selectedQualityRubricId = empty($params['pass'][0]) ? '' : $params['pass'][0];
        $rubricTemplateId = empty($params['pass'][1]) ? '' : $params['pass'][1];
        $rubricTemplateHeaderId = empty($params['pass'][2]) ? 0 : $params['pass'][2];

        $RubricsTemplateHeader = ClassRegistry::init('Quality.RubricsTemplateHeader');
        $rubricTemplateData = $RubricsTemplateHeader->getRubricTemplate($rubricTemplateHeaderId);
        $headerCounter = $rubricTemplateData['RubricsTemplateHeader']['order'];
        
        $controller->Navigation->addCrumb($rubricTemplateData['RubricsTemplateHeader']['title']);
        $controller->set('subheader', $headerCounter.". ".$rubricTemplateData['RubricsTemplateHeader']['title']);
        $controller->Session->write('RubricsTemplate.id', $rubricTemplateId);
        $controller->Session->write('RubricsTemplateHeader.id', $rubricTemplateHeaderId);

        $RubricsTemplateColumnInfo = ClassRegistry::init('Quality.RubricsTemplateColumnInfo');
        $columnHeaderData = $RubricsTemplateColumnInfo->getColumnsData($rubricTemplateId);
        $count = 1 + count($columnHeaderData);

        $RubricsTemplateItem = ClassRegistry::init('Quality.RubricsTemplateItem');
        $RubricsTemplateAnswer = ClassRegistry::init('Quality.RubricsTemplateAnswer');
        $RubricsTemplateSubheader = ClassRegistry::init('Quality.RubricsTemplateSubheader');

        $selectedAnswers = $this->getSelectedHeaderAnswers($selectedQualityRubricId, $rubricTemplateHeaderId);

        if ($controller->request->is('post')) {
            if (empty($controller->request->data)) {
                $controller->Utility->alert($controller->Utility->getMessage('NO_RECORD_SAVED'), array('type' => 'warn'));
            } else {
                $postData = $controller->request->data['SelectedAnswer'];

                foreach ($postData as $key => $obj) {
                    
                    if(!empty($obj['QualityInstitutionRubricsAnswer']['rubric_template_answer_id'])){
                        $obj['QualityInstitutionRubricsAnswer']['quality_institution_rubric_id'] = $selectedQualityRubricId;
                        $obj['QualityInstitutionRubricsAnswer']['rubric_template_header_id'] = $rubricTemplateHeaderId;

                        $postData[$key] = $obj;
                    }
                    else{
                        unset($postData[$key]);
                    }
                    
                }
                
                if ($this->saveAll($postData)) {
                    $controller->Utility->alert($controller->Utility->getMessage('UPDATE_SUCCESS'));
                    $controller->redirect(array('action' => 'qualityRubricHeader', $selectedQualityRubricId, $rubricTemplateId));
                } else {
                    $controller->Utility->alert($controller->Utility->getMessage('ADD_UPDATE_ERROR'), array('type' => 'error'));
                }
            }
        } else {
            if (empty($controller->request->data['SelectedAnswer'])) {
                $controller->request->data['SelectedAnswer'] = $selectedAnswers;
            }
        }

        $headerData = $RubricsTemplateSubheader->find('all', array('conditions' => array('rubric_template_header_id' => $rubricTemplateHeaderId), 'recursive' => -1, 'order' => 'order')); //$this->processRubricsHeaderData($controller, $params);
        $data = array();
        $subheaderCounter = 1;
        foreach ($headerData as $rowHeader) {
            if (!empty($rowHeader)) {
                $_tempSubHeaderDisplayNum = $headerCounter . '.' . $subheaderCounter;
                $rowHeader['RubricsTemplateSubheader']['display_num'] = $_tempSubHeaderDisplayNum;
                array_push($data, $rowHeader);

                //Retrive Questions 
                $questionsData = $RubricsTemplateItem->find('all', array('conditions' => array('rubric_template_subheader_id' => $rowHeader['RubricsTemplateSubheader']['id']), 'recursive' => -1));

                if (!empty($questionsData)) {
                    $itemCounter = 1;
                    foreach ($questionsData as $question) {
                        $question['RubricsTemplateItem']['display_num'] = $_tempSubHeaderDisplayNum . '.' . $itemCounter;
                        $ansData = $RubricsTemplateAnswer->find('all', array('conditions' => array('rubric_template_item_id' => $question['RubricsTemplateItem']['id']), 'recursive' => -1));

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
        $controller->set('columnHeaderData', $columnHeaderData);
        $controller->set('rubricTemplateId', $rubricTemplateId);
        $controller->set('rubricTemplateHeaderId', $rubricTemplateHeaderId);
        $controller->set('selectedQualityRubricId', $selectedQualityRubricId);
        $controller->set('totalColumns', $count);
        //$controller->set('totalRows', 6);
        $controller->request->data['RubricsTemplateDetail'] = $data;
        
       
        if(empty($data)){
            return  $controller->redirect(array('action' => 'qualityRubricHeader',$selectedQualityRubricId,$rubricTemplateId));
        }
    }

    //SQL function 
    public function getTotalCount($institutionSiteId, $rubricTemplateId,$qualityInstitutionRubricsid) {
        $data = $this->find('all', array(
            'fields' => array('COUNT(QualityInstitutionRubricsAnswer.rubric_template_header_id) as total', 'QualityInstitutionRubricsAnswer.rubric_template_header_id'),
            'group' => array('QualityInstitutionRubricsAnswer.rubric_template_header_id'),
            'conditions' => array(
                'QualityInstitutionRubric.institution_site_id' => $institutionSiteId,
                'QualityInstitutionRubric.rubric_template_id' => $rubricTemplateId,
                'QualityInstitutionRubricsAnswer.quality_institution_rubric_id' => $qualityInstitutionRubricsid
            )
        ));

        return $data;
    }

    public function getSelectedHeaderAnswers($qualityRubricId, $rubricTemplateHeaderId) {
        $data = $this->find('all', array(
            'conditions' => array('quality_institution_rubric_id' => $qualityRubricId, 'rubric_template_header_id' => $rubricTemplateHeaderId),
            'recursive' => -1,
            'fields' => array('id', 'rubric_template_item_id', 'rubric_template_answer_id')
        ));

        $returnData = array();
        foreach ($data as $obj) {
            $returnData[$obj['QualityInstitutionRubricsAnswer']['rubric_template_item_id']] = $obj;
        }

        return $returnData;
    }

}
