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

class AreaLevel extends AppModel {
	public $actsAs = array('ControllerAction');
	public $hasMany = array('Area');
	
	public function beforeAction($controller, $action) {
        parent::beforeAction($controller, $action);
		$controller->Navigation->addCrumb('Area Levels');
		$controller->set('header', __('Area Levels'));
    }
	
	public function getDisplayFields($controller) {
        $fields = array(
            'model' => $this->alias,
            'fields' => array(
                array('field' => 'id', 'type' => 'hidden'),
                array('field' => 'name', 'labelKey' => ''),
                array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
                array('field' => 'modified', 'edit' => false),
                array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
                array('field' => 'created', 'edit' => false)
            )
        );
        return $fields;
    }
	
	public function levels($controller, $params) {
		$data = $this->find('all', array('order' => array('level')));
		$controller->set(compact('data'));
	}
	
	public function levelsAdd($controller, $params) {
		if($controller->request->is('post') || $controller->request->is('put')) {
			$controller->request->data[$this->alias]['level'] = $this->field('level', array(), 'level DESC') + 1;
			if ($this->save($controller->request->data)) {
				$controller->Message->alert('general.add.success');
				return $controller->redirect(array('action' => 'levels'));
			}
		}
	}
	
	public function levelsView($controller, $params) {
		$id = isset($params->pass[0]) ? $params->pass[0] : 0;
		$data = $this->findById($id);
		$fields = $this->getDisplayFields($controller);
		$controller->set(compact('data', 'fields'));
	}
	
	public function levelsEdit($controller, $params) {
		$id = isset($params->pass[0]) ? $params->pass[0] : 0;
		$data = $this->findById($id);
		
		if(!empty($data)) {
			if($controller->request->is('post') || $controller->request->is('put')) {
				if ($this->save($controller->request->data)) {
					$controller->Message->alert('general.edit.success');
					return $controller->redirect(array('action' => 'levelsView', $id));
				}
			} else {
				$controller->request->data = $data;
			}
		} else {
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => 'levels'));
		}
	}
}
