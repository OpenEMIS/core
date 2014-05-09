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

App::uses('UtilityComponent', 'Component');

class StaffTraining extends StaffAppModel {
	public $actsAs = array('ControllerAction', 'DatePicker' => array('completed_date'));
	public $belongsTo = array(
		'StaffTrainingCategory' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'staff_training_category_id'
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
	
	/*public function getData($id) {

        $utility = new UtilityComponent(new ComponentCollection);
		$options['joins'] = array(
            array('table' => 'staff_training_categories',
            	'alias' => 'StaffTrainingCategories',
                'type' => 'LEFT',
                'conditions' => array(
                    'StaffTrainingCategories.id = StaffTraining.staff_training_category_id'
                )
            )
        );

        $options['fields'] = array(
        	'StaffTraining.id',
            'StaffTraining.staff_id',
            'StaffTraining.staff_training_category_id',
        	'StaffTrainingCategories.name',
        	'StaffTraining.completed_date'
        );

        $options['conditions'] = array('StaffTraining.staff_id' => $id);

        $options['order'] = array('StaffTraining.completed_date DESC');

		$list = $this->find('all', $options);
		$list = $utility->formatResult($list);

		return $list;
	}*/
	
	public function getDisplayFields($controller) {
        $fields = array(
            'model' => $this->alias,
            'fields' => array(
				array('field' => 'completed_date'),
                array('field' => 'name', 'model' => 'StaffTrainingCategory', 'labelKey' => 'general.category'),
                array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
                array('field' => 'modified', 'edit' => false),
                array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
                array('field' => 'created', 'edit' => false)
            )
        );
        return $fields;
    }
	
	
	public function beforeAction($controller, $action) {
        $controller->set('model', $this->alias);
	}
	
	public function training($controller, $params) {
        $controller->Navigation->addCrumb('Training');
		$header = __('Training');
		$this->unbindModel(array('belongsTo' => array('ModifiedUser','CreatedUser')));
		$data = $this->findAllByStaffId($controller->staffId);
		$controller->set(compact('header','data'));
    }

    public function trainingAdd($controller, $params) {
		$controller->Navigation->addCrumb('Add Training');
		$controller->set('header', __('Add Training'));
		$this->setup_add_edit_form($controller, $params);
    }

	public function trainingEdit($controller, $params) {
		$controller->Navigation->addCrumb('Edit Training');
		$controller->set('header', __('Edit Training'));
		$this->setup_add_edit_form($controller, $params);
		$this->render = 'add';
	}
	
	function setup_add_edit_form($controller, $params){
		$id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
		
		if ($controller->request->is('post') || $controller->request->is('put')) {
            $controller->request->data[$this->name]['staff_id'] = $controller->staffId;
            if ($this->save($controller->request->data)) {
                $controller->Message->alert('general.add.success');
                return $controller->redirect(array('action' => 'training'));
            }
        }
        else{
            $this->recursive = -1;
            $data = $this->findById($id);
            if (!empty($data)) {
                $controller->request->data = $data;
            }
        }
		
		$categoryOptions = $this->StaffTrainingCategory->findList(true);
		$controller->set(compact('categoryOptions'));
	}
	
	public function trainingView($controller, $params) {
		$controller->Navigation->addCrumb('Training Details');
		$id = isset($params['pass'][0]) ? $params['pass'][0] : 0;
		$header = __('Training Details');
		
		$this->recursive = 1;
		$data = $this->findById($id);
		if (empty($data)) {
		   $controller->Message->alert('general.noData');
		   return $controller->redirect(array('action' => 'leaves'));
	   }
	   
	   $controller->Session->write('StaffTrainingId', $id);
        $fields = $this->getDisplayFields($controller);
        $controller->set(compact('data', 'header', 'fields', 'id'));
	}

    public function trainingDelete($controller, $params) {
		if($controller->Session->check('StaffId') && $controller->Session->check('StaffTrainingId')) {
            $id = $controller->Session->read('StaffTrainingId');
            if ($this->delete($id)) {
                $controller->Message->alert('general.delete.success');
            } else {
                $controller->Message->alert('general.delete.failed');
            }
			$controller->Session->delete('StaffTrainingId');
            $controller->redirect(array('action' => 'training'));
        }
		
    }
}
