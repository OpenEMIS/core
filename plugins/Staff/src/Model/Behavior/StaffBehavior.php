<?php 
namespace Staff\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class StaffBehavior extends Behavior {
	public function initialize(array $config) {
	}

	public function beforeFind(Event $event, Query $query, $options) {
		$query
			->join([
				'table' => 'institution_site_staff',
				'alias' => 'InstitutionSiteStaff',
				'type' => 'INNER',
				'conditions' => [$this->_table->aliasField('id').' = '. 'InstitutionSiteStaff.security_user_id']
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
			'ControllerAction.Model.afterAction' => 'afterAction',
		];
		$events = array_merge($events,$newEvent);
		return $events;
	}

	public function afterAction(Event $event) {
		$alias = $this->_table->alias;
		// $tableName = $this->_table->registryAlias();
		$table = TableRegistry::get('Institution.InstitutionSiteStaff');
		$institutionSiteArray = [];
		switch($alias){
			case "Staff":
				$session = $this->_table->Session;
				$institutionId = $session->read('Institutions.id');
				// Total Students: number

				$query = $table->find()
					->where([$table->aliasField('institution_site_id') => $institutionId])
					->count();
				$institutionSiteArray['Gender'] = $table->getDonutChart('institution_site_staff_gender', 
					['institution_site_id' => $institutionId]);

				break;
			case "Users":
				$query = $table->find()
					->count();
				$institutionSiteArray['Gender'] = $table->getDonutChart('institution_site_staff_gender');
				break;
		}
		if ($this->_table->action == 'index') {
			// $indexDashboard = 'Student.Students/dashboard';
			// $this->_table->controller->viewVars['indexElements']['mini_dashboard'] = [
	  //           'name' => $indexDashboard,
	  //           'data' => [],
	  //           'options' => [],
	  //           'order' => 1
	  //       ];
			$indexDashboard = 'Institution.Institutions/dashboard';
			$this->_table->controller->viewVars['indexElements']['mini_dashboard'] = [
	            'name' => $indexDashboard,
	            'data' => [
	            	'institutionCount' => $query,
	            	'institutionSiteArray' => $institutionSiteArray,
	            ],
	            'options' => [],
	            'order' => 1
	        ];
	    }
	}

	public function addBeforeAction(Event $event) {
		$name = $this->_table->alias();
		$this->_table->ControllerAction->addField('institution_site_staff.0.institution_site_id', [
			'type' => 'hidden', 
			'value' => 0
		]);
		$this->_table->fields['openemis_no']['attr']['value'] = $this->_table->getUniqueOpenemisId(['model'=>Inflector::singularize('Staff')]);
	}

	public function indexBeforeAction(Event $event) {
		$this->_table->fields['staff_institution_name']['visible'] = true;

		$this->_table->ControllerAction->field('name', []);
		$this->_table->ControllerAction->field('default_identity_type', []);
		$this->_table->ControllerAction->field('staff_institution_name', []);
		$this->_table->ControllerAction->field('staff_status', []);

		$this->_table->ControllerAction->setFieldOrder(['photo_content', 'openemis_no', 
			'name', 'default_identity_type', 'staff_institution_name', 'staff_status']);

		$indexDashboard = 'Staff.Staff/dashboard';
		$this->_table->controller->set('indexDashboard', $indexDashboard);
	}

	public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		if (array_key_exists('new', $this->_table->request->query)) {
			if ($this->_table->Session->check($this->_table->alias().'.add.'.$this->_table->request->query['new'])) {
				$institutionStaffData = $this->_table->Session->read($this->_table->alias().'.add.'.$this->_table->request->query['new']);

				if (array_key_exists($this->_table->alias(), $data)) {
						if (!array_key_exists('institution_site_staff', $data[$this->_table->alias()])) {
						$data[$this->_table->alias()]['institution_site_staff'] = [];
						$data[$this->_table->alias()]['institution_site_staff'][0] = [];
					}
					$data[$this->_table->alias()]['institution_site_staff'][0]['institution_site_id'] = $institutionStaffData[$this->_table->alias()]['institution_site_staff'][0]['institution_site_id'];

					$data[$this->_table->alias()]['institution_site_staff'][0]['staff_type_id'] = $institutionStaffData[$this->_table->alias()]['institution_site_staff'][0]['staff_type_id'];
					$data[$this->_table->alias()]['institution_site_staff'][0]['institution_site_position_id'] = $institutionStaffData[$this->_table->alias()]['institution_site_staff'][0]['institution_site_position_id'];

					$data[$this->_table->alias()]['institution_site_staff'][0]['FTE'] = $institutionStaffData[$this->_table->alias()]['institution_site_staff'][0]['FTE'];

					// start (date and year) handling
					$data[$this->_table->alias()]['institution_site_staff'][0]['start_date'] = $institutionStaffData[$this->_table->alias()]['institution_site_staff'][0]['start_date'];
					
					$data[$this->_table->alias()]['institution_site_staff'][0]['security_role_id'] = $institutionStaffData[$this->_table->alias()]['institution_site_staff'][0]['security_role_id'];
					
				}
			}
		}

		if (array_key_exists('start_date', $data[$this->_table->alias()]['institution_site_staff'][0])) {
			$startData = getdate(strtotime($data[$this->_table->alias()]['institution_site_staff'][0]['start_date']));
			$data[$this->_table->alias()]['institution_site_staff'][0]['start_year'] = (array_key_exists('year', $startData))? $startData['year']: null;
		}
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$newOptions = [];
		$options['associated'] = ['InstitutionSiteStaff'];

		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);
	}
}
