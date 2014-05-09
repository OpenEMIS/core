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

App::uses('AppModel', 'Model');

class InstitutionSitePosition extends AppModel {
	public $actsAs = array('ControllerAction');
    public $status = array('Disabled', 'Enabled');
    public $workType = array('Non-Teaching', 'Teaching');
    public $belongsTo = array(
        //'Student',
        /* 'RubricsTemplate' => array(
          'foreignKey' => 'rubric_template_id'
          ), */
		
		'PositionTitle' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'staff_position_title_id'
		),
		'PositionGrade' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'staff_Position_grade_id'
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
        'position_no' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please enter a valid Number.'
            )
        ),
        'staff_position_title_id' => array(
            'ruleRequired' => array(
                'rule' => 'checkDropdownData',
                //  'required' => true,
                'message' => 'Please select a valid Title.'
            )
        ),
        'staff_position_grade_id' => array(
            'ruleRequired' => array(
                'rule' => 'checkDropdownData',
                //  'required' => true,
                'message' => 'Please select a valid Grade.'
            )
        ),
            /* 'teacher_position_step_id' => array(
              'ruleRequired' => array(
              'rule' => 'checkDropdownData',
              //  'required' => true,
              'message' => 'Please select a valid Step.'
              )
              ), */
    );

    public function checkDropdownData($check) {
        $value = array_values($check);
        $value = $value[0];

        return !empty($value);
    }

    
    /*public function getInstitutionSitePositionList() {
        $options['recursive'] = -1;
        $data = $this->find('all', $options);
        $list = array();
        if (!empty($data)) {
            $StaffPositionTitle = ClassRegistry::init('StaffPositionTitle');
            $TeacherPositionTitle = ClassRegistry::init('TeacherPositionTitle');

            $staffOptions = $StaffPositionTitle->findList(true);
            $teacherOptions = $TeacherPositionTitle->findList(true);
            
            foreach ($data as $obj){
                $posInfo = $obj['InstitutionSitePosition'];
                //pr($posInfo);
                
                if($posInfo['staff_position_title_type'] == 't'){
                   // pr($teacherOptions[$posInfo['staff_position_title_id']]);
                    $list[$posInfo['id']] = sprintf('%s - %s',$posInfo['position_no'], $teacherOptions[$posInfo['staff_position_title_id']]);
                }
                else{
                    //pr($staffOptions[$posInfo['staff_position_title_id']]);
                    $list[$posInfo['id']] = sprintf('%s - %s',$posInfo['position_no'], $staffOptions[$posInfo['staff_position_title_id']]);
                }
            }
        }

        return $list;
       // pr($data);
    }*/
	
	public function getDisplayFields($controller) {
        $fields = array(
            'model' => $this->alias,
            'fields' => array(
				array('field' => 'position_no', 'labelKey' => 'Position.number'),
                array('field' => 'name', 'model' => 'PositionTitle', 'labelKey' => 'general.title'),
				array('field' => 'name', 'model' => 'PositionGrade', 'labelKey' => 'general.grade'),
				array('field' => 'type', 'type' => 'select', 'options' => $controller->Option->get('yesno'), 'labelKey' => 'Position.teaching'),
				array('field' => 'status', 'type' => 'select', 'options' => $controller->Option->get('enableOptions')),
                array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
                array('field' => 'modified', 'edit' => false),
                array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
                array('field' => 'created', 'edit' => false)
            )
        );
        return $fields;
    }
	
	public function getDisplayFieldsHistory($controller) {
        $fields = array(
            'model' => $this->alias,
            'fields' => array(
				array('field' => 'position_no', 'labelKey' => 'Position.number'),
                array('field' => 'name', 'model' => 'PositionTitle', 'labelKey' => 'general.title'),
				array('field' => 'name', 'model' => 'PositionGrade', 'labelKey' => 'general.grade'),
				array('field' => 'type', 'type' => 'select', 'options' => $controller->Option->get('yesno'), 'labelKey' => 'Position.teaching'),
				array('field' => 'status', 'type' => 'select', 'options' => $controller->Option->get('enableOptions'))
            )
        );
        return $fields;
    }

	public function beforeAction($controller, $action) {
        $controller->set('model', $this->alias);
    }
	
	public function positions($controller, $params) {
        $controller->Navigation->addCrumb('Positions');
		$header = __('Positions');
        $this->unbindModel(array('belongsTo' => array('ModifiedUser', 'CreatedUser')));
        $data = $this->findAllByInstitutionSiteId($controller->institutionSiteId);

        $controller->set(compact('header', 'data'));
    }

    public function positionsAdd($controller, $params) {
        $controller->Navigation->addCrumb('Add Position');
     //   $type = 'add';
        $controller->set('header', __('Add Position'));
        $this->setup_add_edit_form($controller, $params);
    }

    public function positionsEdit($controller, $params)  {
        $controller->Navigation->addCrumb('Edit Position');
		$controller->set('header', __('Edit Position'));
        $this->setup_add_edit_form($controller, $params);

        $this->render('add');
    }

    public function positionsView($controller, $params) {
		$controller->Navigation->addCrumb('Position Details');
		$id = isset($params['pass'][0]) ? $params['pass'][0] : 0;
		$header = __('Position Details');
		
		$this->recursive = 1;
		$data = $this->findById($id);
		if (empty($data)) {
		   $controller->Message->alert('general.noData');
		   return $controller->redirect(array('action' => 'leaves'));
	   }
	   
	   $controller->Session->write('InstitutionSitePositionId', $id);
        $fields = $this->getDisplayFields($controller);
        $controller->set(compact('data', 'header', 'fields', 'id'));
    }

    public function positionsDelete($controller, $params) {
        if ($controller->Session->check('InstitutionSitePositionId')) {
            $id = $controller->Session->read('InstitutionSitePositionId');
            if ($this->delete($id)) {
                $controller->Message->alert('general.delete.success');
            } else {
                $controller->Message->alert('general.delete.failed');
            }
			$controller->Session->delete('InstitutionSitePositionId');
            $controller->redirect(array('action' => 'positions'));
        }
    }
	
	function setup_add_edit_form($controller, $params) {
        $id = empty($this->params['pass'][0]) ? 0 : $this->params['pass'][0];
        if ($controller->request->is('post') || $controller->request->is('put')) {
            $controller->request->data[$this->alias]['institution_site_id'] = $controller->institutionSiteId;
            if ($this->save($controller->request->data)) {
                $controller->Message->alert('general.add.success');
                return $controller->redirect(array('action' => 'positions'));
            }
        } else {
            $this->recursive = -1;
            $data = $this->findById($id);
            if (!empty($data)) {
                $controller->request->data = $data;
            }
			else{
				$controller->request->data[$this->alias]['status'] = 1;
			}
        }


        $positionTitleptions = $this->PositionTitle->getList();
        $positionGradeOptions = $this->PositionGrade->getList();
		$yesnoOptions = $controller->Option->get('yesno');
		$enableOptions = $controller->Option->get('enableOptions');
		
		$controller->set(compact('positionTitleptions', 'positionGradeOptions', 'yesnoOptions','enableOptions'));
    }
	
	public function positionsHistory($controller, $params) {
		$controller->Navigation->addCrumb('Position History');
		$id = isset($params['pass'][0]) ? $params['pass'][0] : 0;
		$header = __('Position History');
		
		$this->recursive = 1;
		$data = $this->findById($id);
		if (empty($data)) {
		   $controller->Message->alert('general.noData');
		   return $controller->redirect(array('action' => 'leaves'));
	   }
	   
	   $controller->Session->write('InstitutionSitePositionId', $id);
        $fields = $this->getDisplayFieldsHistory($controller);
        $controller->set(compact('data', 'header', 'fields', 'id'));
    }
}
