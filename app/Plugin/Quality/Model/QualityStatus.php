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

class QualityStatus extends QualityAppModel {

    //public $useTable = 'rubrics';
    public $actsAs = array('ControllerAction');
    public $belongsTo = array(
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
    );
    //public $hasMany = array('RubricsTemplateColumnInfo');

    public $validate = array(
       'rubric_template_id' => array(
            'ruleRequired' => array(
                'rule' => 'checkDropdownData',
                //  'required' => true,
                'message' => 'Please select a valid Name.'
            )
        ),
    );
    public $statusOptions = array('Date Disabled', 'Date Enabled');
    
    public function checkDropdownData($check) {
        $value = array_values($check);
        $value = $value[0];

        return !empty($value);
    }

    public function status($controller, $params) {
        $institutionId = $controller->Session->read('InstitutionId');
        
        $controller->Navigation->addCrumb('Status');
        $controller->set('subheader', 'Status');
        $controller->set('modelName', $this->name);

        $this->recursive = -1;
        $data = $this->getQualityStatuses();//$this->find('all');

        $controller->set('data', $data);
        $controller->set('statusOptions', $this->statusOptions);
        
        $RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');
        $rubricOptions = $RubricsTemplate->getRubricOptions();
        
        $controller->set('rubricOptions', $rubricOptions);
    }

    public function statusView($controller, $params) {
        $controller->Navigation->addCrumb('Status');
        $controller->set('subheader', 'Status');
        $controller->set('modelName', $this->name);

        $id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
        $data = $this->find('first', array('conditions' => array($this->name . '.id' => $id)));

        if (empty($data)) {
            $controller->redirect(array('action' => 'status'));
        }

        $RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');
        $RubricsTemplate->recursive = -1;
        $rubricTemplateInfo = $RubricsTemplate->findById($data[$this->name]['rubric_template_id']);
            
        $rubricName = $rubricTemplateInfo['RubricsTemplate']['name'];
        $controller->Session->write('QualityStatus.id', $id);
        $controller->set('rubricName', $rubricName);
        $controller->set('data', $data);
        $controller->set('statusOptions', $this->statusOptions);
    }

    public function statusAdd($controller, $params) {
        $controller->Navigation->addCrumb('Add Status');
        $controller->set('subheader', 'Add Status');
        $controller->set('modelName', $this->name);

        $controller->set('selectedYear', date("Y"));

        $this->_setupStatusForm($controller, $params);
    }

    public function statusEdit($controller, $params) {
        $controller->Navigation->addCrumb('Edit Status');
        $controller->set('subheader', 'Edit Status');
        $controller->set('modelName', $this->name);
        $controller->set('selectedYear', date("Y"));
        $this->_setupStatusForm($controller, $params);
        $this->render = 'add';
    }

    private function _setupStatusForm($controller, $params) {
        $controller->set('statusOptions', $this->statusOptions);
       // $institutionId = $controller->Session->read('InstitutionId');
        
        if ($controller->request->is('get')) {
            
            $id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
            
            $this->recursive = -1;
            $data = $this->findById($id);
               
            $RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');
            $rubricOptions = $RubricsTemplate->getRubricOptions();
            
            $controller->set('rubricOptions', $rubricOptions);
           
            if (!empty($data)) {
                $controller->request->data = $data;
                $controller->set('selectedYear', $data[$this->name]['year']);
            }
            else{
                //$controller->request->data[$this->name]['institution_id'] = $institutionId;
            
            }
        } else {
            // $controller->request->data[$this->name]['student_id'] = $controller->studentId;
         //pr($controller->request->data); die;
            if ($this->save($controller->request->data)) {
                if (empty($controller->request->data[$this->name]['id'])) {
                    $controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
                } else {
                    $controller->Utility->alert($controller->Utility->getMessage('UPDATE_SUCCESS'));
                }
                return $controller->redirect(array('action' => 'status'));
            }
        }
    }

    public function statusDelete($controller, $params) {
        if ($controller->Session->check('QualityStatus.id')) {
            $id = $controller->Session->read('QualityStatus.id');

            $data = $this->find('first', array('conditions' => array($this->name . '.id' => $id)));


            $name = $data[$this->name]['name'] . " (" . $data[$this->name]['year'] . ")";

            $this->delete($id);
            $controller->Utility->alert($name . ' have been deleted successfully.');
            $controller->Session->delete('QualityStatus.id');
            $controller->redirect(array('action' => 'status'));
        }
    }

    //SQL Function 
    public function getQualityStatuses() {
        $options['recursive'] = -1;
        $options['joins'] = array(
            array(
                'table' => 'rubrics_templates',
                'alias' => 'RubricsTemplate',
                'conditions' => array('RubricsTemplate.id = QualityStatus.rubric_template_id')
            )
        );
        $options['order'] = array('RubricsTemplate.name','QualityStatus.year');
        $options['fields'] = array('QualityStatus.*', 'RubricsTemplate.*');
        $data = $this->find('all', $options);
        
        return $data;
    }

    public function getRubricStatus($year, $rubricId){
        $data = $this->find('first', array('conditions'=>array('year'=>$year,'rubric_template_id'=> $rubricId), 'recurisve'=> -1));
        $enabled = 0;
        if(!empty($data)){
            $enabled = $data[$this->name]['status'];
        }
        
        return ($enabled == 1)? 'true' : 'false';
    }
}
