<?php 
namespace Student\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class StudentBehavior extends Behavior {
	public function initialize(array $config) {
	}

	public function beforeFind(Event $event, Query $query, $options) {
		$query
			->join([
				'table' => 'institution_site_students',
				'alias' => 'InstututionSiteStudents',
				'type' => 'INNER',
				'conditions' => [$this->_table->aliasField('id').' = '. 'InstututionSiteStudents.security_user_id']
			])
			->group($this->_table->aliasField('id'));
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'ControllerAction.Model.beforeAction' => 'beforeAction',
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
		$this->_table->ControllerAction->addField('institution_site_students.0.institution_site_id', [
			'type' => 'hidden', 
			'value' => 0
		]);
		$this->_table->fields['openemis_no']['attr']['value'] = $this->_table->getUniqueOpenemisId(['model'=>Inflector::singularize('Student')]);
	}

	public function beforeAction(Event $event) {
		$this->_table->fields['super_admin']['visible'] = false;
		$this->_table->fields['status']['visible'] = false;
		$this->_table->fields['date_of_death']['visible'] = false;
		$this->_table->fields['last_login']['visible'] = false;
		$this->_table->fields['photo_name']['visible'] = false;
	}

	public function indexBeforeAction(Event $event) {
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
		$this->_table->fields['photo_content']['visible'] = true;

		$this->_table->ControllerAction->field('name', []);
		$this->_table->ControllerAction->field('default_identity_type', []);
		$this->_table->ControllerAction->field('student_institution_name', []);
		$this->_table->ControllerAction->field('student_status', []);

		$this->_table->ControllerAction->setFieldOrder(['photo_content', 'openemis_no', 
			'name', 'default_identity_type', 'student_institution_name', 'student_status']);

		$indexDashboard = 'Student.Students/dashboard';
		$this->_table->controller->set('indexDashboard', $indexDashboard);
	}

	public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		if (array_key_exists('new', $this->_table->request->query)) {
			if ($this->_table->Session->check($this->_table->alias().'.add.'.$this->_table->request->query['new'])) {
				$institutionStudentData = $this->_table->Session->read($this->_table->alias().'.add.'.$this->_table->request->query['new']);

				if (array_key_exists($this->_table->alias(), $data)) {
					if (!array_key_exists('institution_site_students', $data[$this->_table->alias()])) {
						$data[$this->_table->alias()]['institution_site_students'] = [];
						$data[$this->_table->alias()]['institution_site_students'][0] = [];
					}
					$data[$this->_table->alias()]['institution_site_students'][0]['institution_site_id'] = $institutionStudentData[$this->_table->alias()]['institution_site_students'][0]['institution_site_id'];

					$data[$this->_table->alias()]['institution_site_students'][0]['student_status_id'] = $institutionStudentData[$this->_table->alias()]['institution_site_students'][0]['student_status_id'];
					$data[$this->_table->alias()]['institution_site_students'][0]['education_programme_id'] = $institutionStudentData[$this->_table->alias()]['institution_site_students'][0]['education_programme_id'];

					// start and end (date and year) handling
					$data[$this->_table->alias()]['institution_site_students'][0]['start_date'] = $institutionStudentData[$this->_table->alias()]['institution_site_students'][0]['start_date'];
					$data[$this->_table->alias()]['institution_site_students'][0]['end_date'] = $institutionStudentData[$this->_table->alias()]['institution_site_students'][0]['end_date'];
					$startData = getdate(strtotime($data[$this->_table->alias()]['institution_site_students'][0]['start_date']));
					$data[$this->_table->alias()]['institution_site_students'][0]['start_year'] = (array_key_exists('year', $startData))? $startData['year']: null;
					$endData = getdate(strtotime($data[$this->_table->alias()]['institution_site_students'][0]['end_date']));
					$data[$this->_table->alias()]['institution_site_students'][0]['end_year'] = (array_key_exists('year', $endData))? $endData['year']: null;
				}
			}
		}
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$newOptions = [];
		$options['associated'] = ['InstitutionSiteStudents'];

		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);
	}

	public function afterSave(Event $event, Entity $entity, $options) {
		if ($entity->isNew()) {
			// for attaching student to section
			if (array_key_exists('new', $this->_table->request->query)) {
				if ($this->_table->Session->check('InstitutionSiteStudents.add.'.$this->_table->request->query['new'])) {
					$institutionStudentData = $this->_table->Session->read('InstitutionSiteStudents.add.'.$this->_table->request->query['new']);
					$sectionData = [];
					$sectionData['security_user_id'] = $entity->id;
					$sectionData['education_grade_id'] = $institutionStudentData[$this->_table->alias()]['institution_site_students'][0]['education_grade'];
					$sectionData['institution_site_section_id'] = $institutionStudentData[$this->_table->alias()]['institution_site_students'][0]['section'];
					$sectionData['student_category_id'] = $institutionStudentData[$this->_table->alias()]['institution_site_students'][0]['student_status_id'];

					$InstitutionSiteSectionStudents = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
					$InstitutionSiteSectionStudents->autoInsertSectionStudent($sectionData);	
				}
			}
		}
	}
}
