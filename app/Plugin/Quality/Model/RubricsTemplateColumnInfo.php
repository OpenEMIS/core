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

class RubricsTemplateColumnInfo extends QualityAppModel {

    //public $useTable = 'rubrics';
    public $actsAs = array('ControllerAction');
    public $belongsTo = array(
        //'Student',
        'RubricsTemplate' => array(
            'foreignKey' => 'rubric_template_id'
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
    public $validate = array(
        'name' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please enter a valid Name.'
            )
        ),
        'weighting' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please enter a valid Weighting.'
            )
        )
    );

    public function beforeAction($controller, $action) {
        $id = empty($controller->params['pass'][0]) ? '' : $controller->params['pass'][0];

        if (empty($id)) {
            return $controller->redirect(array('action' => 'rubricsTemplates'));
        }
        
        $RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');
        $rubricTemplateData = $RubricsTemplate->getRubric($id);
        $rubricName = trim($rubricTemplateData['RubricsTemplate']['name']);
        
        
        
        $controller->set('modelName', $this->name);
        
        $controller->Navigation->addCrumb('Rubric', array('controller' => 'Quality', 'action' => 'rubricsTemplates', 'plugin' => 'Quality'));
        $controller->Navigation->addCrumb($rubricName, array('controller' => 'Quality', 'action' => 'rubricsTemplatesHeader',$id, 'plugin' => 'Quality'));
    }

    public function rubricsTemplatesCriteria($controller, $params) {
        $controller->Navigation->addCrumb('Setup Rubric Criteria');
        $controller->set('subheader', 'Setup Rubric Criteria');
        $controller->set('modelName', $this->name);

        $id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
        $controller->set('id', $id);

        $rubricTemplateHeaderId = empty($params['pass'][1]) ? 0 : $params['pass'][1];
        $controller->set('rubricTemplateHeaderId', $rubricTemplateHeaderId);

        //$this->recursive = -1;
        $data = $this->getColumnsData($id); //('all', array('conditions' => array('rubric_template_id'=>$id)));
        $controller->Session->write('RubricsCriteria.order', count($data));
        $controller->set('data', $data);
        $controller->set('id', $id);
        // pr($_SESSION);
    }

    public function rubricsTemplatesCriteriaOrder($controller, $params) {
        $controller->Navigation->addCrumb('Reorder Criteria');
        $controller->set('subheader', 'Reorder Criteria');
        $controller->set('modelName', $this->name);

        $id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
        $controller->set('id', $id);

        $rubricTemplateHeaderId = empty($params['pass'][1]) ? 0 : $params['pass'][1];
        $controller->set('rubricTemplateHeaderId', $rubricTemplateHeaderId);

        //$this->recursive = -1;
        // pr($_SESSION);

        if ($controller->request->is('post')) {
            if (!empty($controller->request->data)) {
                if ($this->saveAll($controller->request->data[$this->name], array('validate' => false))) {
                    //    pr('save');
                    $controller->Utility->alert($controller->Utility->getMessage('UPDATE_SUCCESS'));
                    $controller->redirect(array('action' => 'rubricsTemplatesCriteria', $id, $rubricTemplateHeaderId));
                }
            } else {
                $controller->Utility->alert($controller->Utility->getMessage('NO_RECORD_SAVED'), array('type' => 'info'));
                $controller->redirect(array('action' => 'rubricsTemplatesCriteria', $id, $rubricTemplateHeaderId));
            }
        }

        $data = $this->getColumnsData($id); //('all', array('conditions' => array('rubric_template_id'=>$id)));
        $controller->set('data', $data);
        $controller->set('id', $id);
    }

    public function rubricsTemplatesCriteriaView($controller, $params) {
        $controller->Navigation->addCrumb('Criteria Details');
        $controller->set('subheader', 'Criteria Details');
        $controller->set('modelName', $this->name);

        $id = empty($params['pass'][0]) ? 0 : $params['pass'][0]; //rubrics template id
        $rubricTemplateHeaderId = empty($params['pass'][1]) ? 0 : $params['pass'][1];

        $criteriaId = empty($params['pass'][2]) ? 0 : $params['pass'][2]; //criteria id
        //
        
        $controller->Session->write('RubricsTemplateCriteria.id', $criteriaId);

        //$id = empty($params['pass'][0])? 0:$params['pass'][0]; //Criteria id
        $controller->set('id', $id);
        $controller->set('rubricTemplateHeaderId', $rubricTemplateHeaderId);

        $data = $this->find('first', array('conditions' => array($this->name . '.id' => $criteriaId)));

        if (empty($data)) {
            $controller->redirect(array('action' => 'rubricsTemplatesCriteria'));
        }

        $controller->set('data', $data);
        //$controller->set('weighthingsOptions', $this->weighthingsOptions);
    }

    public function rubricsTemplatesCriteriaAdd($controller, $params) {
        $controller->Navigation->addCrumb('Add Criteria');
        $controller->set('subheader', 'Add Criteria');
        $controller->set('modelName', $this->name);

        $this->_setupRubricCriteria($controller, $params, 'add');
    }

    public function rubricsTemplatesCriteriaEdit($controller, $params) {
        $controller->Navigation->addCrumb('Edit Criteria');
        $controller->set('subheader', 'Edit Criteria');
        $controller->set('modelName', $this->name);

        $this->_setupRubricCriteria($controller, $params, 'edit');
        $this->render = 'add';
    }

    private function _setupRubricCriteria($controller, $params, $type) {
        $id = empty($params['pass'][0]) ? 0 : $params['pass'][0]; //rubrics template id
        $rubricTemplateHeaderId = empty($params['pass'][1]) ? 0 : $params['pass'][1];
        $controller->set('rubricTemplateHeaderId', $rubricTemplateHeaderId);
        $criteriaId = empty($params['pass'][2]) ? 0 : $params['pass'][2]; //criteria id
        $controller->set('id', $id);

        if ($controller->request->is('get')) {
            //pr($controller->request->data);
            $this->recursive = -1;

            $data = $this->findById($criteriaId);
            if ($type == 'add') {
                $data[$this->name]['order'] = $controller->Session->read('RubricsCriteria.order') + 1;
            }
            $controller->request->data = $data;
        } else {//post
            if ($this->saveAll($controller->request->data)) {
                $controller->Utility->alert($controller->Utility->getMessage('UPDATE_SUCCESS'));

                if ($type == 'add') {
                    return $controller->redirect(array('action' => 'rubricsTemplatesCriteria', $id, $rubricTemplateHeaderId));
                } else {
                    return $controller->redirect(array('action' => 'rubricsTemplatesCriteriaView', $id, $rubricTemplateHeaderId, $criteriaId));
                }
            }
        }
    }

    public function rubricsTemplatesCriteriaDelete($controller, $params) {
        if ($controller->Session->check('RubricsTemplateCriteria.id')) {
            $id = $controller->Session->read('RubricsTemplateCriteria.id');
            $rubricHeaderid = $controller->Session->read('RubricsTemplateHeader.id');
            $rubricid = $controller->Session->read('RubricsTemplate.id');
            $data = $this->find('first', array('conditions' => array($this->name . '.id' => $id)));
            $name = '"' . $data[$this->name]['name'] . '"';

            $this->delete($id);
            $controller->Utility->alert($name . ' have been deleted successfully.');
            $controller->Session->delete('RubricsTemplateCriteria.id');
            $controller->redirect(array('action' => 'rubricsTemplatesCriteria', $rubricid, $rubricHeaderid));
        }
    }

    //Data retriving
    public function getTotalCriteriaById($id) {
        $this->recursive = -1;
        $count = $this->find('count', array(
            'conditions' => array('rubric_template_id' => $id)
        ));

        return $count;
    }

    public function getColumnsData($id, $recerusive = -1) {
        $this->recursive = $recerusive;
        $data = $this->find('all', array('conditions' => array('rubric_template_id' => $id), 'order' => 'order'));
        return $data;
    }

    public function getMaxWeighting() {
        $data = $this->find('all', array('fields' => array('id', 'rubric_template_id', 'MAX(weighting) as maxWeight'), 'group' => array('rubric_template_id')));
        $list = array();
        foreach ($data as $obj) {
            $list[$obj['RubricsTemplateColumnInfo']['rubric_template_id']] = $obj[0]['maxWeight'];
        }
        return $list;
    }

}
