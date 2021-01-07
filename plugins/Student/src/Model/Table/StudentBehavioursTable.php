<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use App\Model\Table\AppTable;

class StudentBehavioursTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Students', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('StudentBehaviourCategories', ['className' => 'Student.StudentBehaviourCategories']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
	}

	public function indexBeforeAction(Event $event, ArrayObject $settings) {
		$this->ControllerAction->field('student_id', ['visible' => false]);
		$this->ControllerAction->field('student_behaviour_category_id', ['type' => 'select']);
		$this->ControllerAction->field('description', ['visible' => false]);
		$this->ControllerAction->field('action', ['visible' => false]);

		$this->ControllerAction->setFieldOrder(['institution_id', 'date_of_behaviour', 'time_of_behaviour', 'title', 'student_behaviour_category_id']);
	}
        
        public function beforeFind( Event $event, Query $query )
        {   
            if ($this->controller->name == 'Profiles' && $this->request->query['type'] == 'student') {
                $studentId = $this->Session->read('Auth.User.id');
                $conditions[$this->aliasField('student_id')] = $studentId;
                $query->where($conditions, [], true);
            }            
        }

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		parent::onUpdateActionButtons($event, $entity, $buttons);
                
		if (array_key_exists('view', $buttons)) {
			$url = [
				'plugin' => 'Institution',
				'controller' => 'Institutions',
				'action' => 'StudentBehaviours',
				'view',
				$this->paramsEncode(['id' => $entity->id]),
				'institution_id' => $entity->institution->id,
			];
			$buttons['view']['url'] = $url;

			// POCOR-1893 unset the view button on profiles controller
			if ($this->controller->name == 'Profiles') {
				unset($buttons['view']);
			}
			// end POCOR-1893
		}

		return $buttons;
	}

	private function setupTabElements() {
		$options['type'] = 'student';
		$tabElements = $this->controller->getAcademicTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$alias = 'Behaviours';
		$this->controller->set('selectedAction', $alias);
	}

	public function indexAfterAction(Event $event, $data) {
		$this->setupTabElements();
	}
}
