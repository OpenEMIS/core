<?php 
namespace Staff\Model\Behavior;

use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\Event\Event;

class StaffBehavior extends Behavior {
	public function initialize(array $config) {
	}

	public function beforeFind(Event $event, Query $query, $options) {
		$query
			->join([
				'table' => 'institution_site_staff',
				'alias' => 'InstitionSiteStaff',
				'type' => 'INNER',
				'conditions' => 'Users.id = InstitionSiteStaff.security_user_id',
			])
			->group('Users.id');
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'ControllerAction.Model.beforeAction' => 'beforeAction',
			'ControllerAction.Model.index.beforeAction' => 'indexBeforeAction',
			'ControllerAction.Model.add.beforePatch' => 'addBeforePatch',
			'ControllerAction.Model.add.afterSaveRedirect' => 'addAfterSaveRedirect'
		];
		$events = array_merge($events,$newEvent);
		return $events;
	}

	public function beforeAction(Event $event) {
		$this->_table->ControllerAction->field('photo_content', [
			'type' => 'element',
			'element' => 'Staff.Staff/picture'
		]);
		$this->_table->fields['super_admin']['visible'] = false;
		$this->_table->fields['status']['visible'] = false;
		$this->_table->fields['date_of_death']['visible'] = false;
		$this->_table->fields['last_login']['visible'] = false;
		$this->_table->fields['photo_name']['visible'] = false;
	}

	public function indexBeforeAction(Event $event) {
		$this->_table->ControllerAction->addField('photo_content', [
			'type' => 'image',
		]);
		$this->_table->fields['username']['visible']['index'] = false;
		$this->_table->fields['birthplace_area_id']['visible']['index'] = false;
		$this->_table->fields['photo_content']['visible']['index'] = true;
		$this->_table->ControllerAction->setFieldOrder(['photo_content']);

		$indexDashboard = 'Staff.Staff/dashboard';
		$this->_table->controller->set('indexDashboard', $indexDashboard);
	}

	public function addBeforePatch($event, $entity, $data, $options) {
		// this is an entry that is added to institutions
		if (array_key_exists('new', $this->_table->request->query)) {
			if ($this->_table->Session->check('InstitutionSiteStaff.add.'.$this->_table->request->query['new'])) {
				$institutionStudentData = $this->_table->Session->read('InstitutionSiteStaff.add.'.$this->_table->request->query['new']);
				if (array_key_exists('Users', $data)) {
					if (!array_key_exists('institution_site_staff', $data['Users'])) {
						$data['Users']['institution_site_staff'] = [];
						$data['Users']['institution_site_staff'][0] = [];
					}
					$data['Users']['institution_site_staff'][0]['institution_site_id'] = $institutionStudentData['InstitutionSiteStaff']['institution_site_id'];

					$data['Users']['institution_site_staff'][0]['FTE'] = $institutionStudentData['InstitutionSiteStaff']['FTE']/100;
					$data['Users']['institution_site_staff'][0]['staff_type_id'] = $institutionStudentData['InstitutionSiteStaff']['staff_type_id'];

					// start (date and year) handling
					$data['Users']['institution_site_staff'][0]['start_date'] = $institutionStudentData['InstitutionSiteStaff']['start_date'];
					$startData = getdate(strtotime($data['Users']['institution_site_staff'][0]['start_date']));
					$data['Users']['institution_site_staff'][0]['start_year'] = (array_key_exists('year', $startData))? $startData['year']: null;
				}
			}
		}
		return compact('entity', 'data', 'options');
	}

	public function addAfterSaveRedirect($action) {
		$action = [];
		if ($this->_table->Session->check('InstitutionSiteStaff.add.'.$this->_table->request->query['new'])) {
			$action = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Staff'];
		}

		return $action;
	}
}
