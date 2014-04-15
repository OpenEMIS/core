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

    /*public function getAllList($type, $value) {
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
        $options['recursive'] = -1;
        $data = $this->find('all', $options);

        return $data;
    }*/

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
        
        $fields = array(
            'model' => $this->alias,
            'fields' => array(
                array('field' => 'name', 'model' => 'SchoolYear'),
                array('field' => 'name', 'model' => 'ExtracurricularType', 'labelKey' => 'general.type'),
                array('field' => 'name', 'labelKey' => 'general.title'),
                array('field' => 'start_date', 'type' => 'datepicker'),
                array('field' => 'end_date', 'type' => 'datepicker'),
                array('field' => 'hours'),
                array('field' => 'points'),
                array('field' => 'location'),
                array('field' => 'comment'),
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
        $data = $this->find('all', array('conditions' => array('student_id' => $controller->studentId), 'order' => 'SchoolYear.start_date')); //$this->getAllList('student_id', $controller->studentId);
      
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
        $header = __('Details');

        $controller->Session->write('StudentExtracurricularId', $id);
         $fields = $this->getDisplayFields($controller);
        $controller->set(compact('header', 'data', 'fields'));
    }

    public function extracurricularAdd($controller, $params) {
        $controller->Navigation->addCrumb('Add Extracurricular');
        $header = __('Add Extracurricular');
        
        if ($controller->request->is('post') || $controller->request->is('put')) {
            $data = $controller->request->data;
            
            $data[$this->alias]['start_date'] = date('Y-m-d', strtotime($data[$this->alias]['start_date']));
            $data[$this->alias]['end_date'] = date('Y-m-d', strtotime($data[$this->alias]['end_date']));
            $data[$this->alias]['student_id'] = $controller->studentId;
            if ($this->save($data)) {
                $controller->Message->alert('general.add.success');
                return $controller->redirect(array('action' => 'extracurricular'));
            }
        }
        
        $SchoolYear = ClassRegistry::init('SchoolYear');
        $ExtracurricularType = ClassRegistry::init('ExtracurricularType');
        $yearOptions = $SchoolYear->getYearList();
        $yearId = isset($params['pass'][0])?$params['pass'][0] : key($yearOptions);//$this->getAvailableYearId($yearList);
        $typeOptions = $ExtracurricularType->findList(array('orderBy' => 'name'));

        $controller->set(compact('header','yearOptions','yearId', 'typeOptions'));
    }

    public function extracurricularEdit($controller, $params) {
        $id = isset($params['pass'][0])? $params['pass'][0] : 0;
        $controller->Navigation->addCrumb('Edit Extracurricular');
        $header = __('Edit Extracurricular');
       
        if ($controller->request->is('post') || $controller->request->is('put')) {
            $data = $controller->data;
            $data[$this->alias]['student_id'] = $controller->studentId;
            $data[$this->alias]['start_date'] = date('Y-m-d', strtotime($data[$this->alias]['start_date']));
            $data[$this->alias]['end_date'] = date('Y-m-d', strtotime($data[$this->alias]['end_date']));
            if ($this->save($data)) {
                $controller->Message->alert('general.add.success');
                return $controller->redirect(array('action' => 'extracurricularView', $data['StudentExtracurricular']['id']));
            }
        }
        else{
            $data = $this->findById($id);
            
            if (empty($data)) {
                $controller->Message->alert('general.noData');
                return $controller->redirect(array('action' => 'extracurricular'));
            }
            $controller->request->data = $data;
        }

        $SchoolYear = ClassRegistry::init('SchoolYear');
        $ExtracurricularType = ClassRegistry::init('ExtracurricularType');
        $yearOptions = $SchoolYear->getYearList();
        $yearId = isset($params['pass'][0])?$params['pass'][0] : key($yearOptions);
        $typeOptions = $ExtracurricularType->findList(array('orderBy' => 'name'));

        $controller->set(compact('header','yearOptions','yearId', 'typeOptions'));
    }

    public function extracurricularDelete($controller, $params) {
        if ($controller->Session->check('StudentId') && $controller->Session->check('StudentExtracurricularId')) {
            $id = $controller->Session->read('StudentExtracurricularId');
            $studentId = $controller->Session->read('StudentId');
           
            if($this->delete($id)) {
                $controller->Message->alert('general.delete.success');
            } else {
                $controller->Message->alert('general.delete.failed');
            }
            
            $controller->Session->delete('StudentExtracurricularId');
            $controller->redirect(array('action' => 'extracurricular'));
        }
    }

    public function extracurricularSearchAutoComplete($controller, $params) {
        $this->render = false;
        if ($controller->request->is('get')) {
            if ($controller->request->is('ajax')) {
                
                $search = $params->query['term'];
                $result = $this->autocomplete($search);
                return json_encode($result);
            }
        }
    }
}

?>
