<?php 
namespace Student\Model\Behavior;

use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;

class StudentBehavior extends Behavior {
	public function initialize(array $config) {
	}

	public function beforeFind(Event $event, Query $query, $options) {
		$query
			->join([
				'table' => 'institution_site_students',
				'alias' => 'InstitionSiteStudents',
				'type' => 'INNER',
				'conditions' => 'Users.id = InstitionSiteStudents.security_user_id',
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
		$this->_table->fields['super_admin']['visible'] = false;
		$this->_table->fields['status']['visible'] = false;
		$this->_table->fields['date_of_death']['visible'] = false;
		$this->_table->fields['last_login']['visible'] = false;
		$this->_table->fields['photo_name']['visible'] = false;
	}

	public function indexBeforeAction(Event $event) {
		$this->_table->ControllerAction->addField('photo_content', [
			'type' => 'image',
			//'element' => 'Student.Students/picture'
		]);

		$this->_table->fields['first_name']['visible'] = false;
		$this->_table->fields['middle_name']['visible'] = false;
		$this->_table->fields['third_name']['visible'] = false;
		$this->_table->fields['last_name']['visible'] = false;
		$this->_table->fields['preferred_name']['visible'] = false;
		$this->_table->fields['address']['visible'] = false;
		$this->_table->fields['postal_code']['visible'] = false;
		$this->_table->fields['address_area_id']['visible'] = false;
		$this->_table->fields['gender_id']['visible'] = false;
		$this->_table->fields['date_of_birth']['visible'] = false;
		$this->_table->fields['username']['visible'] = false;
		$this->_table->fields['birthplace_area_id']['visible'] = false;
		$this->_table->fields['status']['visible'] = false;

		$this->_table->ControllerAction->field('name', []);
		$this->_table->ControllerAction->field('existence_type', []);
		$this->_table->ControllerAction->field('institution_name', []);
		$this->_table->ControllerAction->field('student_status', []);

		$this->_table->ControllerAction->setFieldOrder(['photo_content', 'openemis_no', 
			'name', 'existence_type', 'institution_name', 'status']);

		$indexDashboard = 'Student.Students/dashboard';
		$this->_table->controller->set('indexDashboard', $indexDashboard);
	}

	public function addBeforePatch($event, $entity, $data, $options) {
		// this is an entry that is added to institutions
		if (array_key_exists('new', $this->_table->request->query)) {
			if ($this->_table->Session->check('InstitutionSiteStudents.add.'.$this->_table->request->query['new'])) {
				$institutionStudentData = $this->_table->Session->read('InstitutionSiteStudents.add.'.$this->_table->request->query['new']);

				if (array_key_exists('Users', $data)) {
					if (!array_key_exists('institution_site_students', $data['Users'])) {
						$data['Users']['institution_site_students'] = [];
						$data['Users']['institution_site_students'][0] = [];
					}
					$data['Users']['institution_site_students'][0]['institution_site_id'] = $institutionStudentData['InstitutionSiteStudents']['institution_site_id'];
					$data['Users']['institution_site_students'][0]['student_status_id'] = $institutionStudentData['InstitutionSiteStudents']['student_status_id'];
					$data['Users']['institution_site_students'][0]['education_programme_id'] = $institutionStudentData['InstitutionSiteStudents']['education_programme_id'];

					// start and end (date and year) handling
					$data['Users']['institution_site_students'][0]['start_date'] = $institutionStudentData['InstitutionSiteStudents']['start_date'];
					$data['Users']['institution_site_students'][0]['end_date'] = $institutionStudentData['InstitutionSiteStudents']['end_date'];
					$startData = getdate(strtotime($data['Users']['institution_site_students'][0]['start_date']));
					$data['Users']['institution_site_students'][0]['start_year'] = (array_key_exists('year', $startData))? $startData['year']: null;
					$endData = getdate(strtotime($data['Users']['institution_site_students'][0]['end_date']));
					$data['Users']['institution_site_students'][0]['end_year'] = (array_key_exists('year', $endData))? $endData['year']: null;
				}
			}
		}
		return compact('entity', 'data', 'options');
	}

	public function afterSave(Event $event, Entity $entity, $options) {
		if ($entity->isNew()) {
			// for attaching student to section
			if ($this->_table->Session->check('InstitutionSiteStudents.add.'.$this->_table->request->query['new'])) {
				$institutionStudentData = $this->_table->Session->read('InstitutionSiteStudents.add.'.$this->_table->request->query['new']);
				$sectionData = [];
				$sectionData['security_user_id'] = $entity->id;
				$sectionData['education_grade_id'] = $institutionStudentData['InstitutionSiteStudents']['education_grade'];
				$sectionData['institution_site_section_id'] = $institutionStudentData['InstitutionSiteStudents']['section'];
				$sectionData['student_category_id'] = $institutionStudentData['InstitutionSiteStudents']['student_status_id'];

				$InstitutionSiteSectionStudents = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
				$InstitutionSiteSectionStudents->autoInsertSectionStudent($sectionData);	
			}
		}
	}

	public function addAfterSaveRedirect($action) {
		$action = [];
		if ($this->_table->Session->check('InstitutionSiteStudents.add.'.$this->_table->request->query['new'])) {
			$action = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Students'];
		}

		return $action;
	}
}
