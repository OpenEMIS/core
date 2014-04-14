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

// App::uses('StudentsAppModel', 'Model');

class StudentExtracurricular extends StudentsAppModel {

    public $actsAs = array('ControllerAction');
    public $belongsTo = array(
        'Student',
        'SchoolYear',
        'ExtracurricularType',
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
                'message' => 'Please enter a valid Title.'
            )
        ),
        'hours' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please enter a valid Hours.'
            )
        ),
        'start_date' => array(
            'ruleNotLater' => array(
                'rule' => array('compareDate', 'end_date'),
                'message' => 'Start Date cannot be later than End Date'
            ),
        )
    );

    public function compareDate($field = array(), $compareField = null) {
        $startDate = new DateTime(current($field));
        $endDate = new DateTime($this->data[$this->name][$compareField]);
        return $endDate > $startDate;
    }

    public function getAllList($type, $value) {
        $options['conditions'] = array('StudentExtracurricular.' . $type => $value);
        $options['joins'] = array(
            array(
                'table' => 'extracurricular_types',
                'alias' => 'ExtracurricularType',
                'conditions' => array('ExtracurricularType.id = StudentExtracurricular.extracurricular_type_id')
            ),
            array(
                'table' => 'school_years',
                'alias' => 'SchoolYears',
                'conditions' => array('SchoolYears.id = StudentExtracurricular.school_year_id')
            )
        );
        $options['fields'] = array('StudentExtracurricular.*', 'ExtracurricularType.name', 'SchoolYears.name', 'ModifiedUser.*', 'CreatedUser.*');

        $data = $this->find('all', $options);

        return $data;
    }

    public function autocomplete($search) {
        $search = sprintf('%%%s%%', $search);
        $data = $this->find('list', array(
            'recursive' => -1,
            'fields' => array('StudentExtracurricular.id', 'StudentExtracurricular.name'),
            'conditions' => array(
                'OR' => array(
                    'StudentExtracurricular.name LIKE' => $search,
                )
            ),
            'order' => array('StudentExtracurricular.name'),
            'group' => array('StudentExtracurricular.name')
        ));
        return $data;
    }

    public function beforeAction($controller, $action) {
        $controller->set('model', $this->alias);
    }
    
    public function getDisplayFields($controller) {
        
        //ContactOption = ClassRegistry::init('ContactOption');
        //$contactOptions = $ContactOption->findList();
        
        $fields = array(
            'model' => $this->alias,
            'fields' => array(
                array('field' => 'name', 'model' => 'SchoolYear'),
                array('field' => 'name', 'model' => 'ExtracurricularType', 'labelKey' => 'general.type'),
                array('field' => 'name'),
                array('field' => 'start_date'),
                array('field' => 'end_date'),
                array('field' => 'hours'),
                array('field' => 'points'),
                array('field' => 'location'),
                array('field' => 'comment'),
                /*array('field' => 'id', 'type' => 'hidden'),
                array('field' => 'contact_option_id', 'model' => 'ContactType', 'type' => 'select', 'options' => $contactOptions),
                array('field' => 'name', 'model' => 'ContactType'),
                array('field' => 'value'),
                array('field' => 'preferred', 'type' => 'select', 'options' => $controller->Option->get('yesno')),*/
                array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
                array('field' => 'modified', 'edit' => false),
                array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
                array('field' => 'created', 'edit' => false)
            )
        );
        return $fields;
    }
    
    public function extracurricular($controller, $params) {
        $controller->Navigation->addCrumb('Extracurricular');
        $header = __('Extracurricular');
        $data = $this->getAllList('student_id', $controller->studentId);
        $controller->set(compact('data', 'header'));
    }

    public function extracurricularView($controller, $params) {
        $id = isset($params['pass'][0])?$params['pass'][0]:0;
        $data = $this->findById($id);//$this->getAllList('id', $id);
        if (empty($data)) {
            $controller->Message->alert('general.noData');
            return $controller->redirect(array('action' => 'extracurricular'));
        }
        
        $controller->Navigation->addCrumb('Extracurricular Details');
        $header = __('Extracurricular');

        //pr($data);
        $controller->Session->write('StudentExtracurricularId', $id);
         $fields = $this->getDisplayFields($controller);
        $controller->set(compact('header', 'data', 'fields'));
    }

    public function extracurricularAdd($controller, $params) {
        $this->Navigation->addCrumb('Add Extracurricular');

        $yearList = $this->SchoolYear->getYearList();
        $yearId = $this->getAvailableYearId($yearList);
        $typeList = $this->ExtracurricularType->findList(array('fields' => array('id', 'name'), 'conditions' => array('visible' => '1'), 'orderBy' => 'name'));


        $this->set('selectedYear', $yearId);
        $this->set('years', $yearList);
        $this->set('types', $typeList);
        if ($this->request->isPost()) {
            $data = $this->request->data;
            $data['StudentExtracurricular']['student_id'] = $this->studentId;
            if ($this->StudentExtracurricular->save($data)) {
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'extracurricular'));
            }
        }
    }

    public function extracurricularEdit($controller, $params) {
        $id = $this->params['pass'][0];
        $this->Navigation->addCrumb('Edit Extracurricular Details');

        if ($this->request->is('get')) {
            $data = $this->StudentExtracurricular->find('first', array('conditions' => array('StudentExtracurricular.id' => $id)));

            if (!empty($data)) {
                $this->request->data = $data;
            }
        } else {
            $data = $this->data;
            $data['StudentExtracurricular']['student_id'] = $this->studentId;
            $data['StudentExtracurricular']['id'] = $id;
            if ($this->StudentExtracurricular->save($data)) {
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'extracurricularView', $data['StudentExtracurricular']['id']));
            }
        }

        $yearList = $this->SchoolYear->getYearList();
        $yearId = $this->getAvailableYearId($yearList);
        $typeList = $this->ExtracurricularType->findList(array('fields' => array('id', 'name'), 'conditions' => array('visible' => '1'), 'orderBy' => 'name'));

        $this->set('selectedYear', $yearId);
        $this->set('years', $yearList);
        $this->set('types', $typeList);

        $this->set('id', $id);
    }

    public function extracurricularDelete($controller, $params) {
        if ($this->Session->check('StudentId') && $this->Session->check('StudentExtracurricularId')) {
            $id = $this->Session->read('StudentExtracurricularId');
            $studentId = $this->Session->read('StudentId');
            $name = $this->StudentExtracurricular->field('name', array('StudentExtracurricular.id' => $id));

            $this->StudentExtracurricular->delete($id);
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'extracurricular'));
        }
    }

}

?>
