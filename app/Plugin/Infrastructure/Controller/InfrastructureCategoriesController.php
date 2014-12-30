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

class InfrastructureCategoriesController extends InfrastructureAppController {
	public $uses = array(
		'InfrastructureCategory'
	);

	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
		$this->Navigation->addCrumb('Infrastructure', array('plugin' => 'Infrastructure', 'controller' => 'InfrastructureCategories', 'action' => 'index'));
		
		$model = 'InfrastructureCategory';
		$this->set(compact('model'));
	}

	public function index(){
		$this->Navigation->addCrumb('Categories');
		
		$parentId = isset($this->params->named['parent_id']) ? $this->params->named['parent_id'] : 0;
		
		$conditions = array();
		if(!empty($parentId)){
			$conditions = array('InfrastructureCategory.parent_id' => $parentId);
		}
		
		$data = $this->InfrastructureCategory->find('all', array(
			'conditions' => $conditions, 
			'order' => array('InfrastructureCategory.order')
		));
		
		$this->set(compact('data'));
	}

	public function view() {
		$this->Navigation->addCrumb('Category Details');

		$id = isset($this->params->pass[0]) ? $this->params->pass[0] : 0;
		if ($this->InfrastructureCategory->exists($id)) {
			$data = $this->InfrastructureCategory->findById($id);
			$this->Session->write($this->InfrastructureCategory->alias.'.id', $id);
			$this->set(compact('data'));
		} else {
			$this->Message->alert('general.view.notExists');
			return $this->redirect(array('action' => 'index'));
		}
	}

	public function edit($id=0) {
		$this->Navigation->addCrumb('Edit Category');
		
		$id = isset($this->params->pass[0]) ? $this->params->pass[0] : 0;
		
		$data = $this->InfrastructureCategory->findById($id);
		$visibleOptions = $this->Option->get('yesno');

		if ($this->request->is(array('post', 'put'))) {
			$postData = $this->request->data;

			if ($this->InfrastructureCategory->save($postData)) {
				$this->Message->alert('general.edit.success');
				return $this->redirect(array('action' => 'view', $id));
			} else {
				$this->request->data = $postData;
				$this->Message->alert('general.edit.failed');
			}
		} else {
			$this->request->data = $data;
		}
		
		$this->set(compact('id', 'visibleOptions'));
	}
	
	

}

?>
