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

class QualityInstitutionRubricHeader extends QualityAppModel {

    public $useTable = false;
    public $actsAs = array('ControllerAction');
   /* public $belongsTo = array(
        //'Student',
        //'RubricsTemplateHeader',
        'ModifiedUser' => array(
            'className' => 'SecurityUser',
            'foreignKey' => 'modified_user_id'
        ),
        'CreatedUser' => array(
            'className' => 'SecurityUser',
            'foreignKey' => 'created_user_id'
        )
    );*/
    //public $hasMany = array('RubricsTemplateColumnInfo');

   /* public $validate = array(
       'rubric_template_id' => array(
            'ruleRequired' => array(
                'rule' => 'checkDropdownData',
                //  'required' => true,
                'message' => 'Please select a valid Name.'
            )
        ),
    );
  */
    public function qualityRubricHeader($controller, $params){
        $controller->Navigation->addCrumb('Headers');
        
        $institutionSiteId = $controller->Session->read('InstitutionSiteId');
        $id = empty($params['pass'][0]) ? '' : $params['pass'][0];
        $rubricId = empty($params['pass'][1]) ? '' : $params['pass'][1];

        if (empty($rubricId)|| empty($id)) {
            return $controller->redirect(array('action' => 'rubricsTemplates'));
        }
        
        
    
       // $RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');
      //   pr($currentCompletedData);
        
        $RubricsTemplateHeader = ClassRegistry::init('Quality.RubricsTemplateHeader');
        $questionStatusData = $RubricsTemplateHeader->getAllQuestionsStatus($institutionSiteId, $rubricId);
        
        $data = $RubricsTemplateHeader->getRubricHeaders($rubricId, 'all');
        
        $editable =  $controller->Session->read('QualityRubric.editable');
      
        if(empty($editable)){pr('here');
            $QualityInstitutionRubric = ClassRegistry::init('Quality.QualityInstitutionRubric');
            $rubricHeaderData = $QualityInstitutionRubric->find('first', array('conditions' => array('QualityInstitutionRubric.id' => $id)));

            $SchoolYear = ClassRegistry::init('SchoolYear');
            $year = $SchoolYear->findById($rubricHeaderData['QualityInstitutionRubric']['school_year_id']);

            $QualityStatus = ClassRegistry::init('Quality.QualityStatus');
            $editiable = $QualityStatus->getRubricStatus($year['SchoolYear']['name'],$rubricHeaderData['QualityInstitutionRubric']['rubric_template_id']);

            $controller->Session->write('QualityRubric.editable', $editiable);
        }
        
        $controller->set('subheader', 'Quality - Rubric Headers');
        $controller->set('rubricId', $rubricId);
        $controller->set('id', $id);
        $controller->set('data', $data);
        $controller->set('questionStatusData', $questionStatusData);
        $controller->set('modelName', 'RubricsTemplateHeader');
    }
    
    public function qualityRubricHeaderView($controller, $params){
        $this->render = false;
    }
    
    
}