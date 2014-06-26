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

App::uses('AppController', 'Controller');

class AreasController extends AppController {
	public $uses = array('Area', 'AreaLevel', 'AreaEducation', 'AreaEducationLevel');
	
	public $modules = array(
		'areasEducation' => 'AreaEducation',
        'areas' => 'Area',
		'levelsEducation' => 'AreaEducationLevel',
		'levels' => 'AreaLevel'
    );
	
    public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
		$this->Navigation->addCrumb('Administrative Boundaries', array('controller' => 'Areas', 'action' => 'index'));
		
		$areaOptions = array(
			'areas' => __('Areas'),
			'levels' => __('Area Levels'),
			'areasEducation' => __('Areas (Education)'),
			'levelsEducation' => __('Area Levels (Education)')
		);
		
		if(array_key_exists($this->action, $areaOptions)) {
			$this->set('selectedAction', $this->action);
		}
		$this->set('areaOptions', $areaOptions);
    }
	
	public function recover($i) {
		$this->autoRender = false;
		$model = 'Area';
		if($i == 2) {
			$model = 'AreaEducation';
		}
		$modelObj = ClassRegistry::init($model);
		$modelObj->recover('parent', -1);
		return $this->redirect(array('action' => 'index'));
	}
	
	public function index() {
		return $this->redirect(array('action' => 'areas'));
	}
	
	public function ajaxGetAreaOptions($model='Area', $parentId=0) {
		$this->layout = 'ajax';
		$levelModels = array('Area' => 'AreaLevel', 'AreaEducation' => 'AreaEducationLevel');
		$levelModel = $levelModels[$model];
		if($parentId > 0) {
			$data = $this->{$model}->find('all', array(
				'conditions' => array('parent_id' => $parentId, 'visible' => 1),
				'order' => array('order')
			));
			$this->set(compact('data', 'model', 'levelModel'));
		}
	}
}
