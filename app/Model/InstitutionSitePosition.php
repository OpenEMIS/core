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
   // public $status = array('Disabled', 'Enabled');
   // public $workType = array('Non-Teaching', 'Teaching');
    public $belongsTo = array(
        //'Student',
        /* 'RubricsTemplate' => array(
          'foreignKey' => 'rubric_template_id'
          ), */
		'StaffPositionTitle',
		'StaffPositionGrade',
		/*'PositionTitle' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'staff_position_title_id'
		),
		'PositionGrade' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'staff_Position_grade_id'
		),*/
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

    
    public function getInstitutionSitePositionList($institutionId = false, $status = false) {
		$options['recursive'] = -1;
		$conditions = array();
		if ($institutionId !== false) {
			$conditions['institution_site_id'] = $institutionId;
		}
		if ($status !== false) {
			$conditions['status'] = $status;
		}
		if (!empty($conditions)) {
			$options['conditions'] = $conditions;
		}
		$data = $this->find('all', $options);
		$list = array();
		if (!empty($data)) {
			$staffOptions = $this->StaffPositionTitle->findList(true);
			foreach ($data as $obj) {
				$posInfo = $obj['InstitutionSitePosition'];
				$list[$posInfo['id']] = sprintf('%s - %s', $posInfo['position_no'], $staffOptions[$posInfo['staff_position_title_id']]);
			}
		}

		return $list;
	}

	public function getDisplayFields($controller) {
        $fields = array(
            'model' => $this->alias,
            'fields' => array(
				array('field' => 'position_no', 'labelKey' => 'Position.number'),
                array('field' => 'name', 'model' => 'StaffPositionTitle', 'labelKey' => 'general.title'),
				array('field' => 'name', 'model' => 'StaffPositionGrade', 'labelKey' => 'general.grade'),
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
                array('field' => 'name', 'model' => 'StaffPositionTitle', 'labelKey' => 'general.title'),
				array('field' => 'name', 'model' => 'StaffPositionGrade', 'labelKey' => 'general.grade'),
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
		$enableOptions = $controller->Option->get('enableOptions');
        $controller->set(compact('header', 'data', 'enableOptions'));
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
		$this->render = 'add';
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
        $id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
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
        $positionTitleOptions = $this->StaffPositionTitle->findList(true);
        $positionGradeOptions = $this->StaffPositionGrade->findList(true);
		$yesnoOptions = $controller->Option->get('yesno');
		$enableOptions = $controller->Option->get('enableOptions');
		
		$controller->set(compact('positionTitleOptions', 'positionGradeOptions', 'yesnoOptions','enableOptions'));
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
		
		$InstitutionSiteStaff = ClassRegistry::init('InstitutionSiteStaff');
		$staffList = $InstitutionSiteStaff->findAllByInstitutionSitePositionIdAndInstitutionSiteId($id,$controller->institutionSiteId, array('Staff.first_name','Staff.middle_name','Staff.last_name','Staff.id','Staff.identification_no', 'InstitutionSiteStaff.id','InstitutionSiteStaff.FTE','InstitutionSiteStaff.start_date','InstitutionSiteStaff.end_date'));
		//pr($id);

		$controller->Session->write('InstitutionSitePositionId', $id);
		$fields = $this->getDisplayFieldsHistory($controller);
		$controller->set(compact('data', 'header', 'fields', 'id', 'staffList'));
    }
	
	public function positionsHistoryEdit($controller, $params) {
		$controller->Navigation->addCrumb('Edit Position History');
		$id = isset($params['pass'][0]) ? $params['pass'][0] : 0;
		$header = __('Edit Position History');

		$this->recursive = 1;
		$data = $this->findById($id);
		if (empty($data)) {
		   $controller->Message->alert('general.noData');
		   return $controller->redirect(array('action' => 'positionsHistory'));
		}
		
		$InstitutionSiteStaff = ClassRegistry::init('InstitutionSiteStaff');
		$staffList = $InstitutionSiteStaff->findAllByInstitutionSitePositionIdAndInstitutionSiteId($id,$controller->institutionSiteId, array('Staff.first_name','Staff.middle_name','Staff.last_name','Staff.id','Staff.identification_no','InstitutionSiteStaff.start_date','InstitutionSiteStaff.end_date', 'InstitutionSiteStaff.id'));
		
		$controller->Session->write('InstitutionSitePositionId', $id);
		$fields = $this->getDisplayFieldsHistory($controller);
		$controller->set(compact('data', 'header', 'fields', 'id', 'staffList'));
	}
	
	public function positionsStaffEdit($controller, $params) {
		//$this->render = false;
		$controller->Navigation->addCrumb('Edit Staff Position');
		$id = isset($params['pass'][0]) ? $params['pass'][0] : 0;
		$header = __('Edit Staff Position');

		$InstitutionSiteStaff = ClassRegistry::init('InstitutionSiteStaff');

		$InstitutionSiteStaff->unbindModel(array('belongsTo' => array('StaffType', 'InstitutionSite', 'StaffStatus')));
		$data = $InstitutionSiteStaff->findById($id, array('fields' => 'Staff.first_name,Staff.middle_name,Staff.last_name,Staff.identification_no,InstitutionSiteStaff.id,InstitutionSiteStaff.staff_status_id,InstitutionSiteStaff.start_date,InstitutionSiteStaff.FTE,InstitutionSiteStaff.end_date,InstitutionSitePosition.id,InstitutionSitePosition.staff_position_title_id'));
		$positionData = $this->StaffPositionTitle->findById($data['InstitutionSitePosition']['staff_position_title_id'], array('fields' => 'StaffPositionTitle.name'));

		$calFTE = $data['InstitutionSiteStaff']['FTE']*100;
		
		$FTEOtpions = $InstitutionSiteStaff->getFTEOptions($data['InstitutionSitePosition']['id'], array('startDate' => $data['InstitutionSiteStaff']['start_date'], 'endDate' => $data['InstitutionSiteStaff']['end_date'], 'FTE_value'=> $calFTE,'includeSelfNum'=> true));
		$data = array_merge($data, $positionData);

		$StaffStatus = ClassRegistry::init('StaffStatus');
		$statusOptions = $StaffStatus->findList(true);
		//$staffTypeDefault = $this->StaffType->getDefaultValue();
		//pr($staffTypeOptions);
		$controller->Session->write('InstitutionSiteStaffId', $id);
				
		if ($controller->request->is(array('post', 'put'))) {
			$postData = $controller->request->data;

			$postData['InstitutionSiteStaff']['start_year'] = date('Y', strtotime($postData['InstitutionSiteStaff']['start_date']));
			
			$enabledEndDate = $postData['InstitutionSiteStaff']['enable_end_date'];
			if(!$enabledEndDate){
				$postData['InstitutionSiteStaff']['end_date'] = null;
				$postData['InstitutionSiteStaff']['end_year'] = null;
			}
			else{
				$postData['InstitutionSiteStaff']['end_year'] = date('Y', strtotime($postData['InstitutionSiteStaff']['end_date']));
			}
			$postData['InstitutionSiteStaff']['FTE'] = $postData['InstitutionSiteStaff']['FTE']/100;
			$InstitutionSiteStaff->validate = array_merge(
					$InstitutionSiteStaff->validate, array(
				'start_date' => array(
					'ruleNotLater' => array(
						'rule' => array('compareDate', 'end_date'),
						'message' => 'Start Date cannot be later than Ended Date'
					),
				)
					)
			);
			$InstitutionSiteStaff->validator()->remove('search');
			$InstitutionSiteStaff->validator()->remove('institution_site_position_id');
			if ($InstitutionSiteStaff->saveAll($postData)) {
				$controller->Message->alert('general.add.success');
				return $controller->redirect(array('action' => 'positionsHistory', $data['InstitutionSitePosition']['id']));
			} else {
				$controller->request->data = $data;
				$controller->request->data['InstitutionSiteStaff']['start_date'] = $postData['InstitutionSiteStaff']['start_date'];
				$controller->request->data['InstitutionSiteStaff']['end_date'] = $postData['InstitutionSiteStaff']['end_date'];
			}
		} else {
			$data['InstitutionSiteStaff']['FTE'] = $calFTE;
			$controller->request->data = $data;
		}

		$controller->set(compact('header', 'FTEOtpions','statusOptions'));
	}

	public function positionsAjaxGetFTE($controller, $params) {
		if ($controller->request->is('ajax')) {
			$this->render = false;
			
			$id =  $controller->request->query['InstitutionSiteStaffId'];
			pr($id);
			$startDate = $controller->request->query['startDate'];
			$endDate = $controller->request->query['endDate'];

			$InstitutionSiteStaff = ClassRegistry::init('InstitutionSiteStaff');

			$InstitutionSiteStaff->unbindModel(array('belongsTo' => array('StaffType', 'InstitutionSite', 'StaffStatus')));
			$data = $InstitutionSiteStaff->findById($id, array('fields' => 'InstitutionSitePosition.id'));

			$FTEOtpions = $InstitutionSiteStaff->getFTEOptions($data['InstitutionSitePosition']['id'], array('startDate' => $startDate, 'endDate' =>$endDate));
		
			$returnString = '';

			foreach ($FTEOtpions as $obj) {
				$returnString .= '<option value="' . $obj . '">' . $obj . '</option>';
			}
			echo $returnString;
		}
	}
}
