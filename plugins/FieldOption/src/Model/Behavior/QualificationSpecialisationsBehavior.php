<?php
namespace FieldOption\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;
// use ControllerAction\Model\Traits\UtilityTrait;
use Cake\ORM\Query;

class QualificationSpecialisationsBehavior extends DisplayBehavior {
	private $fieldOptionName;

	public function implementedEvents() {
		$events = parent::implementedEvents();
		// $events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
		$newEvent = [
			'ControllerAction.Model.index.beforeAction' => 'indexBeforeAction',
			'ControllerAction.Model.view.beforeAction' => 'viewBeforeAction',
			'ControllerAction.Model.index.beforePaginate' => 'indexBeforePaginate',
			'ControllerAction.Model.viewEdit.beforeQuery' => 'viewEditBeforeQuery',
			'ControllerAction.Model.afterAction' => 'afterAction',
			
		];
		$events = array_merge($events,$newEvent);
		return $events;
	}
	public function afterAction(Event $event) {
		$this->_table->ControllerAction->field('education_subjects');
		$this->_table->ControllerAction->setFieldOrder(['field_option_id', 'id','name','visible','editable','default','international_code','national_code', 'education_subjects','modified_user_id','modified','created_user_id','created']);
	}
	
	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain(['EducationSubjects']);	
	}

	public function addEditBeforeAction(Event $event) {
		return parent::addEditBeforeAction($event);
	}

	public function viewBeforeAction(Event $event) {
		return parent::viewBeforeAction($event);
	}

	public function onUpdateFieldEducationSubjects(Event $event, array $attr, $action, Request $request) {
		switch ($action) {
			 case 'edit': case 'add':
				$EducationSubjects = TableRegistry::get('Education.EducationSubjects');
				$subjectOptions = $EducationSubjects
					->find('list')
					->find('visible')
					->find('order')
					->toArray();
				
				$attr['type'] = 'chosenSelect';
				$attr['options'] = $subjectOptions;
				$attr['model'] = 'QualificationSpecialisations';
				break;
			
			default:
				# code...
				break;
		}
		return $attr;
	}

	// this is used in the view but not index - the same logic can be found in QualificationSpecialisationsTable.php
	// needs to fix logic in future for field options so the logic can be in 1 place
	public function onGetEducationSubjects(Event $event, Entity $entity) {
		if (!$entity->has('education_subjects')) {
			$query = $this->find()
			->where([$this->aliasField($this->primaryKey()) => $entity->id])
			->contain(['EducationSubjects'])
			;
			$data = $query->first();
		}
		else {
			$data = $entity;
		}

		$educationSubjects = [];
		if ($data->has('education_subjects')) {
			foreach ($data->education_subjects as $key => $value) {
				$educationSubjects[] = $value->name;
			}
		}

		return (!empty($educationSubjects))? implode(', ', $educationSubjects): ' ';
	}
}
