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

class EducationSystem extends AppModel {
	public $actsAs = array('ControllerAction', 'Reorder');
	public $hasMany = array('EducationLevel');
	
	public $validate = array(
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a name'
			),
			'isUnique' => array(
				'rule' => 'isUnique',
				'message' => 'This name is already exists in the system'
			)
		)
	);
	
	public $_action = 'systems';
	public $_header = 'Education Systems';
	
	public function beforeAction($controller, $action) {
		parent::beforeAction($controller, $action);
		$controller->Navigation->addCrumb($this->_header);
		$controller->set('header', __($this->_header));
		$controller->set('_action', $this->_action);
		$controller->set('selectedAction', $this->_action);
	}
	
	public function getDisplayFields($controller) {
		$yesnoOptions = $controller->Option->get('yesno');
        $fields = array(
            'model' => $this->alias,
            'fields' => array(
                array('field' => 'id', 'type' => 'hidden'),
                array('field' => 'name'),
				array('field' => 'visible', 'type' => 'select', 'options' => $yesnoOptions),
                array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
                array('field' => 'modified', 'edit' => false),
                array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
                array('field' => 'created', 'edit' => false)
            )
        );
        return $fields;
    }
	
	public function systems($controller, $params) {
		$data = $this->find('all', array('order' => array('order')));
		$controller->set(compact('data'));
	}
	
	public function systemsAdd($controller, $params) {
		if($controller->request->is('post') || $controller->request->is('put')) {
			$controller->request->data[$this->alias]['order'] = $this->field('order', array(), 'order DESC') + 1;
			if ($this->save($controller->request->data)) {
				$controller->Message->alert('general.add.success');
				return $controller->redirect(array('action' => $this->_action));
			}
		}
	}
	
	public function systemsView($controller, $params) {
		$id = isset($params->pass[0]) ? $params->pass[0] : 0;
		$data = $this->findById($id);
		$fields = $this->getDisplayFields($controller);
		$controller->set(compact('data', 'fields'));
	}
	
	public function systemsEdit($controller, $params) {
		$id = isset($params->pass[0]) ? $params->pass[0] : 0;
		$data = $this->findById($id);
		
		if(!empty($data)) {
			$fields = $this->getDisplayFields($controller);
			$controller->set(compact('fields'));
			if($controller->request->is('post') || $controller->request->is('put')) {
				if ($this->save($controller->request->data)) {
					$controller->Message->alert('general.edit.success');
					return $controller->redirect(array('action' => $this->_action.'View', $id));
				}
			} else {
				$controller->request->data = $data;
			}
		} else {
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => $this->_action));
		}
	}
}
