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
	public $uses = array('Area', 'AreaLevel', 'AreaAdministrative', 'AreaAdministrativeLevel');
	
	public $modules = array(
		'Area',
		'AreaLevel',
		'AreaAdministrative',
		'AreaAdministrativeLevel'
	);
	
	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
		$this->Navigation->addCrumb('Administrative Boundaries', array('controller' => 'Areas', 'action' => 'index'));
		
		$areaOptions = array(
			'Area' => __('Areas (Education)'),
			'AreaLevel' => __('Area Levels (Education)'),
			'AreaAdministrative' => __('Areas (Administrative)'),
			'AreaAdministrativeLevel' => __('Area Levels (Administrative)')
		);
		
		if(array_key_exists($this->action, $areaOptions)) {
			$this->set('selectedAction', $this->action);
		}
		$this->set('areaOptions', $areaOptions);
	}
	
	public function recover($i) {
		$this->autoRender = false;
		$params = array('Area', 'run', $i);
		$cmd = sprintf("%sConsole/cake.php -app %s %s", APP, APP, implode(' ', $params));
		$nohup = 'nohup %s > %stmp/logs/processes.log & echo $!';
		$shellCmd = sprintf($nohup, $cmd, APP);
		$this->log($shellCmd, 'debug');
		pr($shellCmd);
		$pid = exec($shellCmd);
		pr($pid);
	}
	
	public function index() {
		return $this->redirect(array('action' => 'Area'));
	}
	
	public function ajaxGetAreaOptions($model='Area', $parentId=0) {
		$this->layout = 'ajax';
		$levelModels = array('Area' => 'AreaLevel', 'AreaAdministrative' => 'AreaAdministrativeLevel');
		$levelModel = $levelModels[$model];
		if($parentId > 0) {
			$data = $this->{$model}->find('all', array(
				'conditions' => array('parent_id' => $parentId, 'visible' => 1),
				'order' => array('order')
			));
			$this->set(compact('data', 'model', 'levelModel', 'parentId'));
		}
	}

	public function ajaxReloadAreaDiv($model='Area', $controller='', $field='area_id', $parentId=0) {
		$this->set(compact('model', 'controller', 'field', 'parentId'));
	}
}
