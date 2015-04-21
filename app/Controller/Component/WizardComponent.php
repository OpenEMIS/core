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

class WizardComponent extends Component {
	private $controller;
	public $components = array('Session');
	public $module;
	
	// Is called before the controller's beforeFilter method.
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
	}
	
	// Is called after the controller's beforeFilter method but before the controller executes the current action handler.
	public function startup(Controller $controller) {
		if ($this->isActive()) {
			if ($controller->request->is(array('post', 'put'))) {
				$data = $controller->request->data;
				if (isset($data['wizard'])) {
					$wizard = $data['wizard'];
					if (isset($wizard['cancel'])) {
						$this->stop();
					} else if (isset($wizard['previous'])) {
						$this->previous();
					} else if (isset($wizard['skip'])) {
						$this->skip();
					} else if (isset($wizard['finish'])) {
						$this->end();
					}
				}
			} else {
				$controllerName = $controller->name;
				$action = $controller->action;
				$link = $this->getLink($controllerName, $action);
				$linkIndex = key($link);
				$linkObj = current($link);
				$navigation = $linkObj['Navigation'];
				
				$lastIndex = $this->getLastCompleted();
				if ($lastIndex === false && $linkIndex != 0) {
					$this->redirect(0);
				}
				else if ($linkIndex - $lastIndex > 1) {
					$redirectIndex = $this->setMandatoryStatus(intval($lastIndex)+1, false);
					if($redirectIndex){
						$this->redirect($redirectIndex);
					}
				} else {
					$this->setCurrent($linkIndex);
					// if user is in wizard mode, clicking on view will automatically redirect to edit
					if ($action === $navigation['action'].'View') {
						if (isset($controller->params->pass[0])) {
							$id = $controller->params->pass[0];
							$url = $this->getURL($linkIndex);
							$url['action'] = $navigation['action'].'Edit';
							$url[] = $id;
							return $controller->redirect($url);
						}
					}
				}
			}
		}
	}
	
	public function beforeRedirect(Controller $controller, $url, $status=null, $exit=true) {
		// basically this is to intercept the action's redirect when user click next
		if ($this->isActive()) {
			if($controller->request->is(array('post', 'put'))) {
				$data = $controller->request->data;
				if(isset($data['wizard'])) {
					$wizard = $data['wizard'];
					if(isset($wizard['next'])) {
						$controller->Message->stopAlert(); // prevent alert from showing
						$this->next();
					}
				}
			}
		}
	}
	
	// Is called after the controller executes the requested action's logic, but before the controller's renders views and layout.
	public function beforeRender(Controller $controller) {
		$mode = $this->isActive();
		$controller->set('WizardMode', $mode);
		
		$nextBtn = array(
			'name' => __('Next'),
			'options' => array(
				'div' => false,
				'name' => 'wizard[next]',
				'class' => 'btn_save btn_right',
				'onclick' => 'return Config.checkValidate()'
			)
		);
		
		$prevBtn = array(
			'name' => __('Previous'),
			'options' => array('div' => false, 'name' => 'wizard[previous]', 'class' => 'btn_save btn_right')
		);
		
		$cancelBtn = array(
			'name' => __('Cancel'),
			'options' => array('div' => false, 'name' => 'wizard[cancel]', 'class' => 'btn_cancel btn_right')
		);
		
		$skipBtn = array(
			'name' => __('Skip'),
			'options' => array('div' => false, 'name' => 'wizard[skip]', 'class' => 'btn_cancel')
		);
		
		$finishBtn = array(
			'name' => __('Finish'),
			'options' => array('div' => false, 'name' => 'wizard[finish]', 'class' => 'btn_save')
		);
		
		$btn = array();
		// if this is the first link in the wizard, add the cancel button
		if($this->getCurrent() == 0 && !$this->isCompleted(0)) {
			$btn[] = $cancelBtn;
		}
		
		if($this->getCurrent() != 0) {
			$btn[] = $prevBtn;
		}
		
		if(!$this->isEnd()) {
			$btn[] = $nextBtn;
			if(!$this->isMandatory()) {
				$btn[] = $skipBtn;
			}
		} else {
			$btn[] = $finishBtn;
		}
		
		$controller->set('WizardButtons', $btn);
	}
	
	public function setModule($module) {
		$this->module = $module;
	}
	
	public function getLinks($module=null) {
		$data = array();
		if (is_null($module)) {
			$data = $this->Session->read($this->module . '.wizard.links');
		} else {
			$conditions = array('Navigation.module' => $module, 'Navigation.is_wizard' => true);
			$data = ClassRegistry::init('Navigation')->find('all', array(
				'fields' => array('*'),
				'joins' => array(
					array(
						'table' => 'config_items',
						'alias' => 'ConfigItem',
						'type' => 'LEFT',
						'conditions' => array("ConfigItem.name = " . sprintf("CONCAT('%s_', Navigation.action)", strtolower($module)))
					)
				),
				'conditions' => $conditions,
				'order' => array('Navigation.order')
			));
		}
		return $data;
	}
	
	public function getAllActions($module=null){
		$data = $this->getLinks($module);
		$actions = array();
		foreach($data AS $arr){
			if (!in_array($arr['Navigation']['action'], array('additional', 'attachments'))) {
				$actions[] = $arr['Navigation']['action'] . '/add';
			}else{
				if ($arr['Navigation']['action'] == 'additional')  {
					$actions[] = $arr['Navigation']['action'] . 'Edit';
				} else {
					$actions[] = $arr['Navigation']['action'] . 'Add';
				}
			}
		}
		return $actions;
	}
	
	protected function loopLinks($controllerName, $action){
		$result = array();
		$links = $this->Session->read($this->module . '.wizard.links');
		foreach($links as $index => $link) {
			$obj = $link['Navigation'];
			if($controllerName === $obj['controller'] && preg_match(sprintf('/^%s/i', $obj['pattern']), $action)) {
				$result[$index] = $link;
				break;
			}
		}
		return $result;
	}

	public function getLink() {
		$controllerName = $this->controller->name;
		$action = $this->controller->action;
		return $this->loopLinks($controllerName, $action);
	}

	public function checkLink(){
		$split = explode('/', str_replace(Router::url('/', true), '', $this->controller->referer()));
		$result = array();
		if(count($split)>1){
			$controllerName = $split[0];
			$action = $split[1];
			$result = $this->loopLinks($controllerName, $action);
		}
		return $result;
	}
	
	public function getNoOfLinks() {
		$links = $this->Session->read($this->module . '.wizard.links');
		return count($links);
	}
	
	public function start($options = array()) {
		$links = $this->getLinks($this->module);
		
		$personnelId = $this->Session->read($this->module.'.id');
		$this->Session->delete($this->module);
		$this->Session->write($this->module . '.wizard.links', $links);
		$this->Session->write($this->module . '.wizard.mode', true);
		$this->setCurrent(0);
		if(count($this->checkLink())>0){
			$this->Session->write($this->module . '.id', $personnelId);
		}
	}
	
	public function stop() {
		$this->Session->delete($this->module);
		return $this->controller->redirect(array('action' => 'index'));
	}
	
	public function end() {
		$url = $this->getURL(-1);
		$this->Session->delete($this->module . '.wizard');
		return $this->controller->redirect($url);
	}
	
	public function next() {		
		$index = $this->getCurrent();
		$toLinkIndex = $index + 1;
		
		// set current link to completed
		$this->setCompleted($index);
		
		$redirectIndex = $this->setMandatoryStatus($toLinkIndex, 'next');
		if($redirectIndex){
			$this->redirect($redirectIndex);
		}
	}
	
	public function previous() {
		$index = $this->getCurrent();
		$toLinkIndex = $index - 1;
		$redirectIndex = $this->setMandatoryStatus($toLinkIndex, 'previous');
		if($redirectIndex > -1){
			$this->redirect($redirectIndex);
		}
	}
	
	public function skip() {
		$index = $this->getCurrent();
		$toLinkIndex = $index + 1;
		$this->setCompleted($index);
		$redirectIndex = $this->setMandatoryStatus($toLinkIndex, 'skip');
		if($redirectIndex){
			$this->redirect($redirectIndex);
		}
	}
	
	// returns redirect index
	protected function setMandatoryStatus($toLinkIndex, $type){
		$mandatory = $this->module . '.wizard.mandatory';
		$links = $this->Session->read($this->module . '.wizard.links');
		if(array_key_exists($toLinkIndex, $links)) { // retrieve the next or previous link
			$toLink = $links[$toLinkIndex];
			$config = $toLink['ConfigItem'];
			if(isset($config['value']) && $config['value'] == 1) { // check whether if user allow to skip the next or the previous link
				$this->Session->write($mandatory, true);
			} else if($toLinkIndex == 0){
				$this->Session->write($mandatory, true);
			} else {
				$this->Session->write($mandatory, false);
			}
			if($type){
				unset($this->controller->request->data['wizard'][$type]);
			}
			return $toLinkIndex;
		}else{
			return false;
		}
	}
	
	public function redirect($index) {
		$links = $this->getLinks();
		$url = isset($links[$index]) ? $this->getURL($index) : $this->getURL($index-1);
		$this->setCurrent($index);
		return $this->controller->redirect($url);
	}
	
	public function getURL($index, $action='add') {
		$links = $this->getLinks();
		$link = $index != -1 ? $links[$index] : $links[0];
		
		$navigation = $link['Navigation'];
		$url = array('controller' => $navigation['controller']);
		if(!empty($navigation['plugin'])) {
			$url['plugin'] = $navigation['plugin'];
		}
		
		if($index == 0) {
			$url['action'] = $action;
		} else if ($index == -1) {
			$url['action'] = $navigation['action'];
		} else {
			if (!in_array($navigation['action'], array('additional', 'attachments'))) {
				$url['action'] = $navigation['action'] . '/' . $action;
			}else{
				if ($navigation['action'] == 'additional')  {
					$url['action'] = $navigation['action'] . 'Edit';
				} else {
					$url['action'] = $navigation['action'] . 'Add';
				}
			}
		}
		return $url;
	}
	
	public function isActive() {
		$key = $this->module . '.wizard.mode';
		return $this->Session->check($key);
	}
	
	public function isMandatory() {
		$key = $this->module . '.wizard.mandatory';
		return $this->getCurrent() == 0 || ($this->Session->check($key) && $this->Session->read($key));
	}
	
	public function setCurrent($index) {
		$key = $this->module . '.wizard.current';
		$this->Session->write($key, $index);
	}
	
	public function getCurrent() {
		$key = $this->module . '.wizard.current';
		return intval($this->Session->read($key));
	}
	
	public function isCompleted($index) {
		$key = $this->module . '.wizard.completed.' . $index;
		return $this->Session->check($key) && $this->Session->read($key);
	}
	
	public function setCompleted($index) {
		$key = $this->module . '.wizard.completed.' . $index;
		$this->Session->write($key, true);
	}
	
	public function getLastCompleted() {
		$key = $this->module . '.wizard.completed';
		$completed = $this->Session->read($key);
		$index = false;
		if(!empty($completed)) {
			end($completed);
			$index = intval(key($completed));
		}
		return $index;
	}
	
	public function isEnd() {
		return ($this->getNoOfLinks()-1 == $this->getCurrent());
	}
}
