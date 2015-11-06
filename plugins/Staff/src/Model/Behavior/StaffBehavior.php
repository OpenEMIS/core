<?php 
namespace Staff\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class StaffBehavior extends Behavior {
	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'ControllerAction.Model.add.beforeAction' => 'addBeforeAction',
			'ControllerAction.Model.index.beforeAction' => 'indexBeforeAction',
			'ControllerAction.Model.index.beforePaginate' => 'indexBeforePaginate',
			'ControllerAction.Model.add.beforePatch' => 'addBeforePatch',
			'ControllerAction.Model.addEdit.beforePatch' => 'addEditBeforePatch',
			'ControllerAction.Model.afterAction' => 'afterAction',
		];
		$events = array_merge($events,$newEvent);
		return $events;
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$query->contain(['Users', 'Institutions', 'StaffStatuses']);

		$search = $this->_table->ControllerAction->getSearchKey();

		if (!empty($search)) {
			$query = $this->_table->addSearchConditions($query, ['searchTerm' => $search]);

			if ($request->params['controller'] == 'Institutions') {
				$session = $event->subject()->request->session();
				$institutionId = $session->read('Institution.Institutions.id');
				$query->andWhere(['Staff.institution_id' => $institutionId]);
			}
		}

		// this part filters the list by institutions/areas granted to the group
		if ($this->_table->Auth->user('super_admin') != 1) { // if user is not super admin, the list will be filtered
			$institutionIds = $this->_table->AccessControl->getInstitutionsByUser();
			$query->where(['Staff.institution_id IN ' => $institutionIds]);
		}
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$settings['model'] = 'Institution.Staff';

		$this->_table->ControllerAction->field('name');
		$this->_table->ControllerAction->field('default_identity_type');
		$this->_table->ControllerAction->field('institution');
		$this->_table->ControllerAction->field('staff_status');

		$this->_table->ControllerAction->setFieldOrder(['photo_content', 'openemis_no', 
			'name', 'default_identity_type', 'institution', 'staff_status']);
	}

	public function onGetInstitution(Event $event, Entity $entity) {
		// Check if the user is assigned to an institution name
		if(!empty ($entity->institution->name)) {
			return $entity->institution->name;
		}
	}

	public function onGetStaffStatus(Event $event, Entity $entity) {
		$name = '';
		if ($entity instanceof User) {
			$session = $event->subject()->request->session();
			$institutionId = $session->read('Institution.Institutions.id');

			$InstitutionStaff = TableRegistry::get('Institution.Staff');
			$obj = $InstitutionStaff->find()
				->contain('StaffStatuses')
				->where([
					$InstitutionStaff->aliasField('institution_id') => $institutionId,
					$InstitutionStaff->aliasField('security_user_id') => $entity->id
				])
				->first();
			$name = $obj->staff_status->name;
		} else { // from Institutions -> Staff
			if (!empty($entity->staff_status)) {
				$name = $entity->staff_status->name;
			}
		}
		return $name;
	}

	// Logic for the mini dashboard
	public function afterAction(Event $event) {
		$alias = $this->_table->alias;
		$table = TableRegistry::get('Institution.Staff');
		$institutionArray = [];
		switch($alias){

			// For Institution Staff
			case "Staff":
				$session = $this->_table->Session;
				$institutionId = $session->read('Institution.Institutions.id');
				// Total Students: number

				// Get Number of staff in an institution
				$staffCount = $table->find()
					->where([$table->aliasField('institution_id') => $institutionId])
					->distinct(['security_user_id'])
					->count(['security_user_id']);

				// Get Gender
				$institutionArray['Gender'] = $table->getDonutChart('institution_staff_gender', 
					['institution_id' => $institutionId, 'key' => 'Gender']);

				// To be implemented when the qualification table is fixed
				// $institutionArray['Qualification'] = $table->getDonutChart('institution_staff_qualification', 
				// 	['institution_id' => $institutionId, 'key' => 'Qualification']);

				// Get Staff Licenses
				$table = TableRegistry::get('Staff.Licenses');
				$institutionArray['Licenses'] = $table->getDonutChart('institution_staff_licenses', 
					['institution_id' => $institutionId, 'key' => 'Licenses']);

				break;

			// For Staffs
			case "Users":
				// Get Number of staffs
				$staffCount = $table->find()
					->distinct(['security_user_id'])
					->count(['security_user_id']);
				// Get Staff genders
				$institutionArray['Gender'] = $table->getDonutChart('institution_staff_gender', ['key' => 'Gender']);
				break;
		}
		if ($this->_table->action == 'index') {
			$indexDashboard = 'dashboard';
			$this->_table->controller->viewVars['indexElements']['mini_dashboard'] = [
	            'name' => $indexDashboard,
	            'data' => [
	            	'model' => 'staff',
	            	'modelCount' => $staffCount,
	            	'modelArray' => $institutionArray,
	            ],
	            'options' => [],
	            'order' => 1
	        ];
	    }
	}

	public function addBeforeAction(Event $event) {
		$name = $this->_table->alias();
		$this->_table->ControllerAction->addField('institution_staff.0.institution_id', [
			'type' => 'hidden', 
			'value' => 0
		]);
		$this->_table->fields['openemis_no']['attr']['value'] = $this->_table->getUniqueOpenemisId(['model'=>Inflector::singularize('Staff')]);
	}

	public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		// this method should rightfully be in institution userbehavior - need to move this in an issue after guardian module is in prod
		if (array_key_exists('new', $this->_table->request->query)) {
			if ($this->_table->Session->check($this->_table->alias().'.add.'.$this->_table->request->query['new'])) {
				$institutionStaffData = $this->_table->Session->read($this->_table->alias().'.add.'.$this->_table->request->query['new']);

				if (array_key_exists($this->_table->alias(), $data)) {
						if (!array_key_exists('institution_staff', $data[$this->_table->alias()])) {
						$data[$this->_table->alias()]['institution_staff'] = [];
						$data[$this->_table->alias()]['institution_staff'][0] = [];
					}
					$data[$this->_table->alias()]['institution_staff'][0]['institution_id'] = $institutionStaffData[$this->_table->alias()]['institution_staff'][0]['institution_id'];

					$data[$this->_table->alias()]['institution_staff'][0]['staff_type_id'] = $institutionStaffData[$this->_table->alias()]['institution_staff'][0]['staff_type_id'];
					$data[$this->_table->alias()]['institution_staff'][0]['staff_status_id'] = $institutionStaffData[$this->_table->alias()]['institution_staff'][0]['staff_status_id'];

					$data[$this->_table->alias()]['institution_staff'][0]['institution_position_id'] = $institutionStaffData[$this->_table->alias()]['institution_staff'][0]['institution_position_id'];

					$data[$this->_table->alias()]['institution_staff'][0]['FTE'] = $institutionStaffData[$this->_table->alias()]['institution_staff'][0]['FTE'];

					// start (date and year) handling
					//$data[$this->_table->alias()]['institution_staff'][0]['start_date'] = $institutionStaffData[$this->_table->alias()]['institution_staff'][0]['start_date'];
					
					$data[$this->_table->alias()]['institution_staff'][0]['security_role_id'] = $institutionStaffData[$this->_table->alias()]['institution_staff'][0]['security_role_id'];
					
				}
			}
		}

		if (array_key_exists('start_date', $data[$this->_table->alias()]['institution_staff'][0])) {
			$startData = getdate(strtotime($data[$this->_table->alias()]['institution_staff'][0]['start_date']));
			$data[$this->_table->alias()]['institution_staff'][0]['start_year'] = (array_key_exists('year', $startData))? $startData['year']: null;
		}
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$newOptions = [];
		$options['associated'] = ['Staff'];

		// Jeff: workaround, needs to redo this logic
		if (isset($data[$this->_table->alias()]['institution_staff'])) {
			$obj = $data[$this->_table->alias()]['institution_staff'];
			if (!empty($obj) && isset($obj[0]) && isset($obj[0]['institution_id'])) {
				if ($obj[0]['institution_id'] == 0) {
					$data[$this->_table->alias()]['institution_staff'][0]['start_date'] = date('Y-m-d');
					$data[$this->_table->alias()]['institution_staff'][0]['end_date'] = date('Y-m-d', time()+86400);
					$data[$this->_table->alias()]['institution_staff'][0]['staff_type_id'] = 0;
					$data[$this->_table->alias()]['institution_staff'][0]['institution_position_id'] = 0;
					$data[$this->_table->alias()]['institution_staff'][0]['staff_status_id'] = 0;
				}
			}
		}

		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);
	}
}
