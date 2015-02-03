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

class InfrastructureLevelsController extends InfrastructureAppController {

	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
		$this->Navigation->addCrumb('Infrastructure', array('plugin' => 'Infrastructure', 'controller' => 'InfrastructureLevels', 'action' => 'index'));
		
		$model = 'InfrastructureLevel';
		$this->set(compact('model'));
	}

	public function index(){
		$this->Navigation->addCrumb('Levels');
		
		$parentId = isset($this->params->named['parent_id']) ? $this->params->named['parent_id'] : 0;
		$conditions = array('InfrastructureLevel.parent_id' => $parentId);
		
		$data = $this->InfrastructureLevel->find('all', array(
			'conditions' => $conditions, 
			'order' => array('InfrastructureLevel.order')
		));
		
		$breadcrumbs = array();
		$this->generatebreadcrumbs($parentId, $breadcrumbs);
		$breadcrumbs = array_reverse($breadcrumbs);
		
		$currentTab = 'Levels';
		
		$this->set(compact('data', 'parentId', 'breadcrumbs', 'currentTab'));
	}

	public function view() {
		$this->Navigation->addCrumb('Level Details');

		$id = isset($this->params->pass[0]) ? $this->params->pass[0] : 0;
		
		if ($this->InfrastructureLevel->exists($id)) {
			$data = $this->InfrastructureLevel->findById($id);
			$this->Session->write('InfrastructureLevel.id', $id);
			
			if(!empty($data)){
				$parentLevel = $this->InfrastructureLevel->findById($data['InfrastructureLevel']['parent_id']);
			}
			
			$this->set(compact('data', 'parentLevel'));
		} else {
			$this->Message->alert('general.view.notExists');
			return $this->redirect(array('action' => 'index'));
		}
	}
	
	public function add() {
		$this->Navigation->addCrumb('Add Level');
		
		$visibleOptions = $this->Option->get('yesno');
		$parentId = isset($this->params->named['parent_id']) ? $this->params->named['parent_id'] : 0;
		$parentLevel = $this->InfrastructureLevel->findById($parentId);

		$parentName = !empty($parentLevel) ? $parentLevel['InfrastructureLevel']['name'] : '';
		
		if ($this->request->is(array('post', 'put'))) {
			$postData = $this->request->data;
			$this->InfrastructureLevel->create();
			
			if ($this->InfrastructureLevel->save($postData)) {
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
		$this->Navigation->addCrumb('Edit Level');
		
		$id = isset($this->params->pass[0]) ? $this->params->pass[0] : 0;
		
		$data = $this->InfrastructureLevel->findById($id);
		$visibleOptions = $this->Option->get('yesno');
		
		if(!empty($data)){
			$level = $this->InfrastructureLevel->findById($data['InfrastructureLevel']['parent_id']);
		}

		if ($this->request->is(array('post', 'put'))) {
			$postData = $this->request->data;

			if ($this->InfrastructureLevel->save($postData)) {
				$this->Message->alert('general.edit.success');
				return $this->redirect(array('action' => 'view', $id));
			} else {
				$this->request->data = $postData;
				$this->Message->alert('general.edit.failed');
			}
		} else {
			$this->request->data = $data;
		}
		
		$this->set(compact('id', 'visibleOptions', 'level'));
	}
	
	public function remove() {
		if ($this->Session->check('InfrastructureLevel.id')) {
			$id = $this->Session->read('InfrastructureLevel.id');
			if(!empty($id)){
				$level = $this->InfrastructureLevel->findById($id);
				$parentId = $level['InfrastructureLevel']['parent_id'];
			}

			$this->InfrastructureLevel->deleteAll(array('InfrastructureLevel.id' => $id));
			$this->Message->alert('general.delete.success');
		}
		$this->redirect(array('action' => 'index', 'parent_id' => !empty($parentId) ? $parentId : 0));
	}
	
	private function generatebreadcrumbs($levelId, &$arr){
		$level = $this->InfrastructureLevel->findById($levelId);

		if(!empty($level)){
			$arr[] = array(
				'id' => $level['InfrastructureLevel']['id'],
				'name' => $level['InfrastructureLevel']['name']
			);
			
			$parentId = $level['InfrastructureLevel']['parent_id'];
			if(!empty($parentId)){
				$this->generatebreadcrumbs($parentId, $arr);
			}
		}
	}
	
	public function reorder() {
		$this->Navigation->addCrumb('Levels');
		
		$parentId = isset($this->params->named['parent_id']) ? $this->params->named['parent_id'] : 0;
		$conditions = array('InfrastructureLevel.parent_id' => $parentId);
		
		$data = $this->InfrastructureLevel->find('all', array(
			'conditions' => $conditions, 
			'order' => array('InfrastructureLevel.order')
		));
		
		$breadcrumbs = array();
		$this->generatebreadcrumbs($parentId, $breadcrumbs);
		$breadcrumbs = array_reverse($breadcrumbs);
		
		$currentTab = 'Levels';
		
		$this->set(compact('data', 'parentId', 'breadcrumbs', 'currentTab'));
	}
	
	public function move() {
		$this->autoRender = false;
		if ($this->request->is(array('post', 'put'))) {
			$data = $this->request->data;
			$conditions = array('InfrastructureLevel.parent_id' => $this->params->named['parent_id']);
			$this->InfrastructureLevel->moveOrder($data, $conditions);
			$redirect = array('action' => 'reorder', 'parent_id' => $this->params->named['parent_id']);
			return $this->redirect($redirect);
		}
	}
}
