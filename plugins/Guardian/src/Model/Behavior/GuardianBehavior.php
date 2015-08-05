<?php 
namespace Guardian\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class GuardianBehavior extends Behavior {
	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$query->contain([], true);
		$query->innerJoin(
			['GuardianStudents' => 'student_guardians'],
			['GuardianStudents.guardian_user_id = ' . $this->_table->aliasField('id')]
		)
		->group($this->_table->aliasField('id'));

		$search = $this->_table->ControllerAction->getSearchKey();

		if (!empty($search)) {
			$query = $this->_table->addSearchConditions($query, ['searchTerm' => $search]);
		}
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'ControllerAction.Model.add.beforeAction' => 'addBeforeAction',
			'ControllerAction.Model.index.beforeAction' => 'indexBeforeAction',
			'ControllerAction.Model.index.beforePaginate' => 'indexBeforePaginate',
			'ControllerAction.Model.add.beforePatch' => 'addBeforePatch',
			'ControllerAction.Model.addEdit.beforePatch' => 'addEditBeforePatch',
		];
		$events = array_merge($events,$newEvent);
		return $events;
	}

	public function addBeforeAction(Event $event) {
		$name = $this->_table->alias();
		$this->_table->ControllerAction->addField('guardian_students.0.student_user_id', [
			'type' => 'hidden', 
			'value' => 0
		]);
		$this->_table->fields['openemis_no']['attr']['value'] = $this->_table->getUniqueOpenemisId(['model'=>Inflector::singularize('Guardian')]);
	}

	public function indexBeforeAction(Event $event) {
		$this->_table->ControllerAction->field('name', []);
		$this->_table->ControllerAction->field('default_identity_type', ['visible' => false]);
		// $this->_table->ControllerAction->field('guardian_relation', []);
		// $this->_table->ControllerAction->field('mobile_phone', []);

		$this->_table->ControllerAction->setFieldOrder(['photo_content', 'openemis_no', 
			'name'
			// , 'guardian_relation', 'mobile_phone'
			]);

		$indexDashboard = 'Staff.Staff/dashboard';
		$this->_table->controller->set('indexDashboard', $indexDashboard);
	}

	public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		if (array_key_exists('new', $this->_table->request->query)) {
			if ($this->_table->Session->check($this->_table->alias().'.add.'.$this->_table->request->query['new'])) {
				$studentGuardianData = $this->_table->Session->read($this->_table->alias().'.add.'.$this->_table->request->query['new']);

				if (array_key_exists($this->_table->alias(), $data)) {
					if (!array_key_exists('guardian_students', $data[$this->_table->alias()])) {
						$data[$this->_table->alias()]['guardian_students'] = [];
						$data[$this->_table->alias()]['guardian_students'][0] = [];
					}

					$data[$this->_table->alias()]['guardian_students'][0]['guardian_relation_id'] = $studentGuardianData[$this->_table->alias()]['guardian_students'][0]['guardian_relation_id'];
					$data[$this->_table->alias()]['guardian_students'][0]['guardian_education_level_id'] = $studentGuardianData[$this->_table->alias()]['guardian_students'][0]['guardian_education_level_id'];
					$data[$this->_table->alias()]['guardian_students'][0]['student_user_id'] = $studentGuardianData[$this->_table->alias()]['guardian_students'][0]['student_user_id'];
				}
			}
		}
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$newOptions = [];
		$options['associated'] = ['GuardianStudents'];

		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);
	}

}

?>