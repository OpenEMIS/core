<?php
namespace Institution\Model\Behavior;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Controller\Component;

class InstitutionUserBreadcrumbsBehavior extends Behavior {
	public function initialize(array $config) {

	}
	

/******************************************************************************************************************
**
** Link/Map ControllerActionComponent events
**
******************************************************************************************************************/
	public function implementedEvents() {
		$events = parent::implementedEvents();
    	$events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
		return $events;
	}


/******************************************************************************************************************
**
** Events
**
******************************************************************************************************************/
	public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, Entity $persona) {
		$crumbTitle = $this->_table->getHeader($this->_table->alias());
		$newCrumbTitle = explode(' ', $crumbTitle);
		$newCrumbTitle = Inflector::pluralize($newCrumbTitle[0]);
		$Navigation->substituteCrumb($crumbTitle, $newCrumbTitle, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $newCrumbTitle]);
		if ($this->_table->alias()=='StudentUser' || $this->_table->alias()=='StaffUser') {
			$Navigation->addCrumb($persona->name);
		} else {
			$url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $newCrumbTitle];
			$Navigation->addCrumb($persona->name, $url);
			$Navigation->addCrumb($crumbTitle);
		}
	}

}
