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
		$crumbTitle = Inflector::humanize(Inflector::underscore($this->_table->alias()));
		$splitTitle = explode(' ', $crumbTitle);
		$newCrumbTitle = Inflector::pluralize($splitTitle[0]);
		$institutionId = $request->param('institutionId') ? $this->_table->paramsDecode($request->param('institutionId'))['id'] : $request->session()->read('Institution.Institutions.id');
		$Navigation->substituteCrumb($crumbTitle, __($newCrumbTitle), ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $newCrumbTitle, 'institutionId' => $this->_table->paramsEncode(['id' => $institutionId])]);
		$model = $this->_table;
		if ($this->_table->alias() == $splitTitle[0].'User') {
			$Navigation->addCrumb($persona->name);
		} else {
			$url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $splitTitle[0].'User', 'view', $model->paramsEncode(['id' => $persona->id])];
			$Navigation->addCrumb($persona->name, $url);
			$Navigation->addCrumb($crumbTitle);
		}
	}

}
