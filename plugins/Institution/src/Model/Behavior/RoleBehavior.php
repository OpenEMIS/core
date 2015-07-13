<?php 
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Network\Request;
use Cake\Controller\Controller;

class UserBehavior extends Behavior {
	private $associatedModel;
	public function initialize(array $config) {
		$this->associatedModel = (array_key_exists('associatedModel', $config))? $config['associatedModel']: null;
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		// $newEvents = [
		// 	'ControllerAction.Model.index.beforeAction' => 'indexBeforeAction',
		// 	'ControllerAction.Model.add.beforeAction' => 'addBeforeAction',
		// 	'ControllerAction.Model.add.beforePatch' => 'addBeforePatch',
		// 	'ControllerAction.Model.add.afterPatch' => 'addAfterPatch',
		// 	'ControllerAction.Model.add.afterSave' => 'addAfterSave',
		// 	'ControllerAction.Model.index.beforePaginate' => 'indexBeforePaginate',
		// 	'ControllerAction.Model.add.addOnReload' => 'onReload',
		// 	'Model.custom.onUpdateActionButtons' => 'onUpdateActionButtons',
		// ];

		// $roleEvents = [];

		// if ($this->_table->hasBehavior('Student')) {
		// 	$roleEvents = [
		// 		'ControllerAction.Model.onUpdateFieldAcademicPeriod' => 'onUpdateFieldAcademicPeriod',
		// 		'ControllerAction.Model.onUpdateFieldEducationProgrammeId' => 'onUpdateFieldEducationProgrammeId',
		// 		'ControllerAction.Model.onUpdateFieldEducationGrade' => 'onUpdateFieldEducationGrade',
		// 		'ControllerAction.Model.onUpdateFieldSection' => 'onUpdateFieldSection',
		// 		'ControllerAction.Model.onUpdateFieldStudentStatusId' => 'onUpdateFieldStudentStatusId',
		// 	];
		// }

		// if ($this->_table->hasBehavior('Staff')) {
		// 	$roleEvents = [
		// 		'ControllerAction.Model.onUpdateFieldInstitutionSitePositionId' => 'onUpdateFieldInstitutionSitePositionId',
		// 		'ControllerAction.Model.onUpdateFieldStartDate' => 'onUpdateFieldStartDate',
		// 		'ControllerAction.Model.onUpdateFieldFTE' => 'onUpdateFieldFTE',
		// 		'ControllerAction.Model.onUpdateFieldStaffTypeID' => 'onUpdateFieldStaffTypeID',
		// 	];
		// }

		// $newEvents = array_merge($newEvents, $roleEvents);
		// $events = array_merge($events,$newEvents);
		return $events;
	}
}
