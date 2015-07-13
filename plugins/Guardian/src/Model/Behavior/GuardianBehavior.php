<?php 
namespace Guardian\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class GuardianBehavior extends Behavior {
	public function initialize(array $config) {
	}

	public function beforeFind(Event $event, Query $query, $options) {
		$query
			->join([
				'table' => 'student_guardians',
				'alias' => 'GuardianStudents',
				'type' => 'INNER',
				'conditions' => [$this->_table->aliasField('id').' = '. 'GuardianStudents.guardian_user_id']
			])
			->group($this->_table->aliasField('id'));
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'ControllerAction.Model.add.beforeAction' => 'addBeforeAction',
			'ControllerAction.Model.index.beforeAction' => 'indexBeforeAction',
			'ControllerAction.Model.add.beforePatch' => 'addBeforePatch',
			'ControllerAction.Model.addEdit.beforePatch' => 'addEditBeforePatch',
		];
		$events = array_merge($events,$newEvent);
		return $events;
	}

	public function addBeforeAction(Event $event) {
		$name = $this->_table->alias();
		$this->_table->ControllerAction->addField('student_guardians.0.student_user_id', [
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
		// if (array_key_exists('new', $this->_table->request->query)) {
		// 	if ($this->_table->Session->check($this->_table->alias().'.add.'.$this->_table->request->query['new'])) {
		// 		$institutionStaffData = $this->_table->Session->read($this->_table->alias().'.add.'.$this->_table->request->query['new']);

		// 		if (array_key_exists($this->_table->alias(), $data)) {
		// 				if (!array_key_exists('institution_site_staff', $data[$this->_table->alias()])) {
		// 				$data[$this->_table->alias()]['institution_site_staff'] = [];
		// 				$data[$this->_table->alias()]['institution_site_staff'][0] = [];
		// 			}
		// 			$data[$this->_table->alias()]['institution_site_staff'][0]['institution_site_id'] = $institutionStaffData[$this->_table->alias()]['institution_site_staff'][0]['institution_site_id'];

		// 			$data[$this->_table->alias()]['institution_site_staff'][0]['FTE'] = $institutionStaffData[$this->_table->alias()]['institution_site_staff'][0]['FTE']/100;


		// 			$data[$this->_table->alias()]['institution_site_staff'][0]['staff_type_id'] = $institutionStaffData[$this->_table->alias()]['institution_site_staff'][0]['staff_type_id'];
		// 			$data[$this->_table->alias()]['institution_site_staff'][0]['institution_site_position_id'] = $institutionStaffData[$this->_table->alias()]['institution_site_staff'][0]['institution_site_position_id'];

		// 			// start (date and year) handling
		// 			$data[$this->_table->alias()]['institution_site_staff'][0]['start_date'] = $institutionStaffData[$this->_table->alias()]['institution_site_staff'][0]['start_date'];
		// 			$startData = getdate(strtotime($data[$this->_table->alias()]['institution_site_staff'][0]['start_date']));
		// 			$data[$this->_table->alias()]['institution_site_staff'][0]['start_year'] = (array_key_exists('year', $startData))? $startData['year']: null;
		// 		}
		// 	}
		// }
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$newOptions = [];
		$options['associated'] = ['StudentGuardians'];

		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);
	}

}

?>