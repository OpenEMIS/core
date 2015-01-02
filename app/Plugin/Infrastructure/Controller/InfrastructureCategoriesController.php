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
		'Infrastructure.InfrastructureCategory'
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
		$conditions = array('InfrastructureCategory.parent_id' => $parentId);
		
		$data = $this->InfrastructureCategory->find('all', array(
			'conditions' => $conditions, 
			'order' => array('InfrastructureCategory.order')
		));
		
		$breadcrumbs = array();
		$this->generatebreadcrumbs($parentId, $breadcrumbs);
		$breadcrumbs = array_reverse($breadcrumbs);
		
		$currentTab = 'Categories';
		
		$this->set(compact('data', 'parentId', 'breadcrumbs', 'currentTab'));
	}

	public function view() {
		$this->Navigation->addCrumb('Category Details');

		$id = isset($this->params->pass[0]) ? $this->params->pass[0] : 0;
		
		if ($this->InfrastructureCategory->exists($id)) {
			$data = $this->InfrastructureCategory->findById($id);
			$this->Session->write('InfrastructureCategory.id', $id);
			
			if(!empty($data)){
				$parentCategory = $this->InfrastructureCategory->findById($data['InfrastructureCategory']['parent_id']);
			}
			
			$this->set(compact('data', 'parentCategory'));
		} else {
			$this->Message->alert('general.view.notExists');
			return $this->redirect(array('action' => 'index'));
		}
	}
	
	public function add() {
		$this->Navigation->addCrumb('Add Category');
		
		$visibleOptions = $this->Option->get('yesno');
		$parentId = isset($this->params->named['parent_id']) ? $this->params->named['parent_id'] : 0;
		$parentCategory = $this->InfrastructureCategory->findById($parentId);

		$parentName = !empty($parentCategory) ? $parentCategory['InfrastructureCategory']['name'] : '';
		
		if ($this->request->is(array('post', 'put'))) {
			$postData = $this->request->data;
			$this->InfrastructureCategory->create();
			
			if ($this->InfrastructureCategory->save($postData)) {
				$this->Message->alert('general.add.success');
				return $this->redirect(array('action' => 'index', 'parent_id' => $parentId));
			} else {
				$this->request->data = $postData;
				$this->Message->alert('general.add.failed');
			}
		}
		
		$this->set(compact('visibleOptions','parentId' , 'parentName'));
	}

	public function edit() {
		$this->Navigation->addCrumb('Edit Category');
		
		$id = isset($this->params->pass[0]) ? $this->params->pass[0] : 0;
		
		$data = $this->InfrastructureCategory->findById($id);
		$visibleOptions = $this->Option->get('yesno');
		
		if(!empty($data)){
			$category = $this->InfrastructureCategory->findById($data['InfrastructureCategory']['parent_id']);
		}

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
		
		$this->set(compact('id', 'visibleOptions', 'category'));
	}
	
	public function delete() {
		if ($this->Session->check('InfrastructureCategory.id')) {
			$id = $this->Session->read('InfrastructureCategory.id');
			if(!empty($id)){
				$category = $this->InfrastructureCategory->findById($id);
				$parentId = $category['InfrastructureCategory']['parent_id'];
			}

			$this->InfrastructureCategory->deleteAll(array('InfrastructureCategory.id' => $id));
			$this->Message->alert('general.delete.success');
			$this->redirect(array('action' => 'index', 'parent_id' => !empty($parentId) ? $parentId : 0));
		} else {
			$this->redirect(array('action' => 'index', 'parent_id' => !empty($parentId) ? $parentId : 0));
		}
	}
	
	private function generatebreadcrumbs($categoryId, &$arr){
		$category = $this->InfrastructureCategory->findById($categoryId);

		if(!empty($category)){
			$arr[] = array(
				'id' => $category['InfrastructureCategory']['id'],
				'name' => $category['InfrastructureCategory']['name']
			);
			
			$parentId = $category['InfrastructureCategory']['parent_id'];
			if(!empty($parentId)){
				$this->generatebreadcrumbs($parentId, $arr);
			}
		}
	}
	
	public function reorder() {
		$this->Navigation->addCrumb('Categories');
		
		$parentId = isset($this->params->named['parent_id']) ? $this->params->named['parent_id'] : 0;
		$conditions = array('InfrastructureCategory.parent_id' => $parentId);
		
		$data = $this->InfrastructureCategory->find('all', array(
			'conditions' => $conditions, 
			'order' => array('InfrastructureCategory.order')
		));
		
		$breadcrumbs = array();
		$this->generatebreadcrumbs($parentId, $breadcrumbs);
		$breadcrumbs = array_reverse($breadcrumbs);
		
		$currentTab = 'Categories';
		
		$this->set(compact('data', 'parentId', 'breadcrumbs', 'currentTab'));
	}
	
	public function move() {
		$this->autoRender = false;
		if ($this->request->is(array('post', 'put'))) {
			$data = $this->request->data;
			$conditions = array('InfrastructureCategory.parent_id' => $this->params->named['parent_id']);
			$this->InfrastructureCategory->moveOrder($data, $conditions);
			$redirect = array('action' => 'reorder', 'parent_id' => $this->params->named['parent_id']);
			return $this->redirect($redirect);
		}
	}

}

?>
