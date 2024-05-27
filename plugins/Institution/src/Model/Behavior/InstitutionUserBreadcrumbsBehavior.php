<?php
namespace Institution\Model\Behavior;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Http\ServerRequest;
use Cake\Utility\Inflector;
use Cake\Controller\Component;

class InstitutionUserBreadcrumbsBehavior extends Behavior {
	public function initialize(array $config): void {

	}


/******************************************************************************************************************
**
** Link/Map ControllerActionComponent events
**
******************************************************************************************************************/
	public function implementedEvents(): array {
		$events = parent::implementedEvents();
    	$events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
		return $events;
	}


/******************************************************************************************************************
**
** Events
**
******************************************************************************************************************/
	public function onGetBreadcrumb(Event $event, ServerRequest $request, Component $Navigation, Entity $persona) {
		$crumbTitle = Inflector::humanize(Inflector::underscore($this->_table->getAlias()));
		$splitTitle = explode(' ', $crumbTitle);
		$newCrumbTitle = Inflector::pluralize($splitTitle[0]);
		//staffs
		if($this->_table->getAlias() == 'StaffUser' && $request->getParam('action') == 'StaffUser') {
			$newCrumbTitle = $splitTitle[0];//staff
		}
        $model = $this->_table;
		$queryString = $model->getQueryString();
		$institutionId = $request->getParam('institutionId') ?
            $model->paramsDecode($request->getParam('institutionId'))['id'] :
            $request->getSession()->read('Institution.Institutions.id');
		$institutionId = !empty($institutionId) ? $institutionId : $queryString['institution_id'];
		$Navigation->substituteCrumb($crumbTitle, __($newCrumbTitle), [
		    'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => $newCrumbTitle,
			'index',
            $model->paramsEncode(['id' => $institutionId,'institution_id' => $institutionId])]);
		if ($this->_table->getAlias() == $splitTitle[0].'User') {
			$Navigation->addCrumb($persona->name);
		} else {
			$url = ['plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => $splitTitle[0].'User',
                'view',
				$model->paramsEncode(['id' => $persona->id,'institution_id' => $institutionId,'institution_student_id' => $queryString['institution_student_id'],
				'student_id' => $persona->id])];
			$Navigation->addCrumb($persona->name, $url);
			$Navigation->addCrumb($crumbTitle);
		}
	}

}
