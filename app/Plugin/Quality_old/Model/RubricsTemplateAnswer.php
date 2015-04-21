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

class RubricsTemplateAnswer extends QualityAppModel {

    //public $useTable = 'rubrics';
    //public $actsAs = array('ControllerAction');
    
    public $belongsTo = array(
        //'Student',
        'RubricsTemplateItem' => array(
            'foreignKey' => 'rubric_template_item_id'
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
        'title' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please enter a valid Title asdasd.'
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
    
    public function rubricsTemplatesAnswerDeleteAll($ids){
        $this->unbindModel(array('belongsTo' => array('RubricsTemplateItem')));
        $listOfItemIds = implode(',', $ids);
        
        $data = $this->find('list', array('conditions' => array('rubric_template_item_id' => array($listOfItemIds)), 'fields'=> array('id','id'), 'recursive'=> -1));
     //   pr($this);
       // pr($data);die;
       if(!empty($data)){
            foreach ($data as $obj) {
                //pr($obj);
                $this->delete($obj);
            }
        }
       // pr($data);
    }


}
