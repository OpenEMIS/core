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
 
class NavigationComponent extends Component {
	private $controller;
	public $topNavigations;
	public $leftNavigations;
	public $navigations;
	public $breadcrumbs;
	public $params;
	public $ignoredLinks = array();
	public $skip = false;
	
	public $components = array('Auth', 'AccessControl', 'Session');
	
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
		$this->navigations = $this->getLinks();
		$this->topNavigations = array();
        $this->SecurityGroupUser = ClassRegistry::init('SecurityGroupUser');
	}
	
	//called after Controller::beforeFilter()
	public function startup(Controller $controller) {
	}
	
	//called after Controller::beforeRender()
	public function beforeRender(Controller $controller) {
		if(!$this->skip) {
			$this->apply($controller->params['controller'], $this->controller->action);
		}

		$this->checkWizardModeLink();

		$this->controller->set('_topNavigations', $this->topNavigations);
		$this->controller->set('_leftNavigations', $this->leftNavigations);
		$this->controller->set('_params', $this->params);
		$this->controller->set('_breadcrumbs', $this->breadcrumbs);
	}
	
	//called after Controller::render()
	public function shutdown(Controller $controller) {}
	
	//called before Controller::redirect()
	public function beforeRedirect(Controller $controller, $url, $status = null, $exit = true) {}
	
	public function addCrumb($title, $options=array()) {		
		$item = array(
			'title' => __($title),
			'link' => array('url' => $options),
			'selected' => sizeof($options)==0
		);
		$this->breadcrumbs[] = $item;
	}
	
	public function createLink($title, $controller, $action, $pattern='', $params=array()) {
		$attr = array();
		$attr['title'] = $title;
		$attr['display'] = false;
		$attr['selected'] = false;
		$attr['controller'] = $controller;
		$attr['action'] = $action;
		$attr['pattern'] = strlen($pattern)==0 ? $action : $pattern;
		return array_merge($attr, $params);
	}
	
	public function apply($controller, $action) {
		$navigations = array();
		$found = false;
                //pr($this->navigations);
		foreach($this->navigations as $module => $obj) {
			foreach($obj['links'] as $links) {
				foreach($links as $title => &$linkList) {
					if(!is_array($linkList)) continue;
					foreach($linkList as $link => &$attr) {
						if(!is_array($attr)) continue;
						$_controller = $attr['controller'];
						$pattern = $attr['pattern'];
						
						// Checking access control
						$check = $this->AccessControl->newCheck($_controller, $attr['action']);
						//pr($attr);
						
						if($check || $_controller === 'Home') {
							$linkList['display'] = true;
							$attr['display'] = true;
							
							if($check === true || (isset($check['parent_id']) && $check['parent_id'] == -1) || in_array($module, array('Administration', 'Reports'))) { // to initialise top navigation menu
								if(!array_key_exists($module, $this->topNavigations)) {
									$objController = $module !== 'Administration' ?  : $_controller;
									$this->topNavigations[$module] = array(
										'controller' => $obj['controller'], 
										'action' => isset($obj['action']) ? $obj['action'] : '',
										'selected' => false
									);
								} else {
									if($module !== 'Administration') {
										$this->topNavigations[$module]['controller'] = $obj['controller'];
										$this->topNavigations[$module]['action'] = isset($obj['action']) ? $obj['action'] : '';
									}
								}
							}
						}
						// End access control
						
						// To check which link is selected
						if(!$found && strcasecmp($_controller, $controller)==0 && preg_match(sprintf('/^%s/i', $pattern), $action)) {//pr($attr);
							$found = true;
							$attr['selected'] = true;
							$this->topNavigations[$module]['selected'] = true;
						}
                                                
					}
				}
				if($found) {
					if(empty($this->leftNavigations)) {
						$this->leftNavigations = $links;
					}
				}
                                //pr($this->leftNavigations);
			}
		}//pr($this->navigations);
	}
	
	public function getLinks() {
		$nav = array();
		$nav['Home'] = array('controller' => 'Home', 'links' => $this->getHomeLinks());
		$nav['Institutions'] = array('controller' => 'Institutions', 'links' => $this->getInstitutionsLinks());
		
		// Initialise navigations from plugins
		$modules = $this->settings['modules'];
		foreach($modules as $module) {
			$componentObj = $module.'NavigationComponent';
			App::uses($componentObj, $module.'.Controller/Component');
			$component = new $componentObj(new ComponentCollection);
			$componentLinks = $component->getLinks($this);
			$nav = array_merge($nav, $componentLinks);
		}
		// End initialise
		
		$nav['Administration'] = array('controller' => 'Areas', 'links' => $this->getSettingsLinks());
		return $nav;
	}
	
	public function getHomeLinks() {
		$navigation = ClassRegistry::init('Navigation');
		$links = $navigation->getByModule('Home', true);

		return $links;
	}
	
	public function getInstitutionsLinks() {
		$navigation = ClassRegistry::init('Navigation');
		$links = $navigation->getByModule('Institution', true);
		
		return $links;
	}
	
	public function getSettingsLinks() {
		$navigation = ClassRegistry::init('Navigation');
		$links = $navigation->getByModule('Administration', true);
		$this->ignoreLinks($links, 'Administration');
		return $links;
	}
	
	public function ignoreLinks($links, $module) {
		if(!isset($this->ignoredLinks[$module])) {
			$this->ignoredLinks[$module] = array();
		}
		foreach($links as $i => $category) {
			foreach($category as $j => $items) {
				foreach($items as $k => $obj) {
					if(isset($obj['controller'])) {
						$controller = $obj['controller'];
						$action = $obj['action'];
						$this->ignoredLinks[$module][] = array('controller' => $controller, 'action' => $action);
						$this->AccessControl->ignore($controller, $action);
					}
				}
			}
		}
	}

	public function checkWizardModeLink(){
		$wizardMode = false;
		if(!empty($this->leftNavigations)){
			foreach($this->leftNavigations as $module => $obj) {
				foreach($obj as $value){
					if($value['selected']=='1'){
						foreach($obj as $chkValue){
							if($chkValue['wizard'] == '1'){
								$wizardMode = true;
								break;
							}
						}
						break;
					}
				}
			}
			if(!$wizardMode){
				$this->Session->delete('WizardMode');
		        $this->Session->delete('WizardLink');
			}
		}
	}

	public function checkWizardLink($controller, $module){
		$navigation = ClassRegistry::init('Navigation');
		$links = $navigation->getWizardByModule($module, false);
		$i = 0;
		foreach($links as $link){
			$chkAction = $link['Navigation']['action'] . 'Add';
			
			if($link['Navigation']['action'] == "attachments" || $link['Navigation']['action'] == "additional"){
				$chkAction = $link['Navigation']['action'] . 'Edit';
			}else if($link['Navigation']['action']=='view'){
				$chkAction = 'edit';
			}
			$validate = $this->AccessControl->check($controller, $chkAction);
			if(!$validate){
				unset($links[$i]);
			}
			$i++;
		}

		return $links;

	}
	//$this->AccessControl->apply($this->params['controller'], $this->params['action']);

	public function getWizardLinks($module){
		$wizardLinks = array();
		$links = $this->checkWizardLink($this->controller->name,$module);
		foreach($links as $link){
			$link['Navigation']['completed'] = '0';
			if($link['Navigation']['action'] == 'view'){
				$link['Navigation']['new_action'] = 'edit';
				$link['Navigation']['completed'] = '-1';
				$link['Navigation']['multiple'] = false;
			}else{
				if($link['Navigation']['action'] == "attachments" || $link['Navigation']['action'] == "additional"){
					$link['Navigation']['new_action'] = $link['Navigation']['action'] . 'Edit';
					$link['Navigation']['multiple'] = false;
				}else{
					$link['Navigation']['new_action'] = $link['Navigation']['action'] . 'Add';
					$link['Navigation']['multiple'] = true;
				}
			}
			$wizardLinks[] = $link['Navigation'];
		}
		return $wizardLinks;
	}


    private function getLastWizardStep($step=false){
         $wizardLink = $this->Session->read('WizardLink');
         $i = 0;
         foreach($wizardLink as $link){
            if($link['completed']=='-1'){
                if($step){
                    return $i;
                }else{
                    return $link;
                }
                break;
            }
            $i++;
        }
    }

    public function getWizard($action){
		if(!$this->Session->check('WizardMode') || $this->Session->read('WizardMode')!=true){
			return;
		}

        $newAction = '';
        $configItemName = str_replace('Add', '', $this->controller->action);
        $configItemName = str_replace('Edit', '', $configItemName);

        $actionConcat = str_replace("Add", "", $action);
        $actionConcat = str_replace("Edit", "", $actionConcat);

        $ConfigItem = ClassRegistry::init('ConfigItem');
       
        $mandatory = $ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => strtolower($this->controller->className).'_'.$configItemName, 'ConfigItem.type' => 'Wizard - Add New '.$this->controller->className));
        $this->controller->set('mandatory', $mandatory);
        $linkCurrent = $this->getLastWizardStep(false);
        $wizardLink = $this->Session->read('WizardLink');
        $nextLink = '';
        $wizardEnd = '0';
       
        if($action == 'view'){
            $newAction = 'edit';
            $this->controller->redirect(array('action'=>$newAction));
        }else if($this->action!='edit'){
            $i = 0;
            foreach($wizardLink as $link){
                if($link['action']==$actionConcat){
                    if($i+1 < count($wizardLink)){
                        $nextLink = $wizardLink[$i+1]['new_action'];
                        if(isset($wizardLink[$i+1]['new_id'])){
                             $nextLink .= '/' . $wizardLink[$i+1]['new_id'];
                        }
                    }
                }
                if($link['action']==$action){
                    if($link['completed']=='0'){
                        if(isset($linkCurrent['new_id'])){
                            $this->controller->redirect(array('action'=>$linkCurrent['new_action'], $linkCurrent['new_id']));
                        }else{
                            $this->controller->redirect(array('action'=>$linkCurrent['new_action']));
                        }
                    }else if($link['completed']=='1'){
                        if(isset($link['new_id'])){
                           $this->controller->redirect(array('action'=>$link['new_action'], $link['new_id']));
                        }else{
                            $this->controller->redirect(array('action'=>$link['new_action']));
                        }
                    }else{
                        $newAction = $link['action'] . 'Add';
                        if($link['multiple']==false){
	                    	if(substr($link['new_action'], -3) != 'Add'){
	                         	$newAction = $link['action'] . 'Edit';
	                    	}
                    	}
                        $this->controller->redirect(array('action'=>$newAction));
                    }
                    break;
                }
                $i++;
            }
        }else{
        	break;
        }

        if($action==$wizardLink[count($wizardLink)-1]['new_action']){
        	$wizardEnd = '1';
        }

        $this->controller->set('wizardEnd', $wizardEnd);
        $this->controller->set('nextLink', $nextLink);
        
    }

    private function getWizardLink($action, $full=false){
    	if(!$this->Session->check('WizardMode') || $this->Session->read('WizardMode')!=true){
			return;
		}
        $action = str_replace("Add", "", $action);
        $action = str_replace("Edit", "", $action);

        $wizardLink = $this->Session->read('WizardLink');
        $i=0;
        foreach($wizardLink as $link){
            if($link['action']==$action){
                if($full){
                    return $link;
                }else{
                    return $i;
                }
                break;
            }
            $i++;
        }
    }

    public function skipWizardLink($action){
    	if(!$this->Session->check('WizardMode') || $this->Session->read('WizardMode')!=true){
			return;
		}
        $linkIndex = $this->getWizardLink($action);
        $wizardLink = $this->Session->read('WizardLink');
        $wizardLink[$linkIndex]['completed'] = '1';
        $currentLinkIndex = $this->getLastWizardStep(true);
        if($linkIndex+1 < count($wizardLink)){
            if(($linkIndex+1)>=$currentLinkIndex){
                $wizardLink[$linkIndex+1]['completed'] = '-1';
            }
        }

        $this->Session->write('WizardLink', $wizardLink);
        $this->controller->redirect(array('action'=>$wizardLink[$linkIndex+1]['new_action']));
    }

    public function previousWizardLink($action){
    	if(!$this->Session->check('WizardMode') || $this->Session->read('WizardMode')!=true){
			return;
		}
        $linkIndex = $this->getWizardLink($action);
        $wizardLink = $this->Session->read('WizardLink');

        $ConfigItem = ClassRegistry::init('ConfigItem');

        for($i=($linkIndex-1);$i>=0;$i--){
        	$mandatory = $ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => strtolower($this->controller->className).'_'.$wizardLink[$i]['action'], 'ConfigItem.type' => 'Wizard - Add New '.$this->controller->className));

            if($mandatory!='2'){
               if(isset($wizardLink[$i]['new_id'])){
	                $this->controller->redirect(array('action'=>$wizardLink[$i]['new_action'], $wizardLink[$i]['new_id']));
	            }else{
	                $this->controller->redirect(array('action'=>$wizardLink[$i]['new_action']));
	            }
	            break;
            }
        }

        $this->controller->redirect(array('action'=>$wizardLink[0]['new_action']));
    }

    public function exitWizard($cancel = true){
	 	$this->Session->delete('WizardMode');
        $this->Session->delete('WizardLink');
        if($cancel){
	        $this->Session->delete($this->controller->className.'Id');
	        $this->controller->redirect(array('action'=>'index'));
	    }else{
  			$this->controller->redirect(array('action'=>'view'));
	    }
    }

    public function updateWizard($action, $id=null, $addMore=false){
    	if(!$this->Session->check('WizardMode') || $this->Session->read('WizardMode')!=true){
			return;
		}
        $i = 0;
        $wizardLink = $this->Session->read('WizardLink');
        $action = str_replace("Add", "", $action);
        $action = str_replace("Edit", "", $action);
        foreach($wizardLink as $link){
            if($link['action']==$action && $link['multiple']==false){
                $wizardLink[$i]['completed'] = '1';
                if($link['new_action']!='edit'){
                    $wizardLink[$i]['new_action'] = $link['action'] . "Edit";
                    $wizardLink[$i]['new_id'] = $id; 
                }else{
                    $this->Session->write($this->controller->className.'Id',$id);
                }
                break;
            }else if($link['action']==$action && $link['multiple']==true && !$addMore){
				$wizardLink[$i]['completed'] = '1';
            	break;
            }
            $i++;
        }

		if($addMore){
    		 $this->controller->redirect(array('action'=>$action));
    		 return;
    	}

    	if($i+1 >= count($wizardLink)){
            $this->exitWizard(false);
        }else{
            $currentLinkIndex = $this->getLastWizardStep(true);
            
            if(($i+1)>=$currentLinkIndex){
                $wizardLink[$i+1]['completed'] = '-1';
            }

         	$ConfigItem = ClassRegistry::init('ConfigItem');
       	
       		for($c=$i+1;$c<=count($wizardLink);$c++){
       			$mandatory = $ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => strtolower($this->controller->className).'_'.$wizardLink[$c]['action'], 'ConfigItem.type' => 'Wizard - Add New '.$this->controller->className));

       			$wizardLink[$c]['completed'] = '-1';
       			if($mandatory!='2'){
					if(isset($wizardLink[$c]['new_id'])){
					  	$this->Session->write('WizardLink', $wizardLink);
		                $this->controller->redirect(array('action'=>$wizardLink[$c]['new_action'], $wizardLink[$c]['new_id']));
		            }else{
	            	  	$this->Session->write('WizardLink', $wizardLink);
		                $this->controller->redirect(array('action'=>$wizardLink[$c]['new_action']));
		            }
		            break;
       			}
       		}
        }

   		$this->Session->write('WizardLink', $wizardLink);
        $this->controller->redirect(array('action'=>$wizardLink[count($wizardLink)-1]['new_action']));
       
    }


    public function validateModel($action, $modelName){
    	if(!$this->Session->check('WizardMode') || $this->Session->read('WizardMode')!=true){
			return;
		}
		$id = $this->Session->read($this->controller->className.'Id');
    	$count = $this->controller->{$modelName}->find('count', array('conditions'=>array(strtolower($this->controller->className.'_id')=>$id)));
    	if($count>0){
    		$this->updateWizard($action, null, false);
    	}
    }

}
?>