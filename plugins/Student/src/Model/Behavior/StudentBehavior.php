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
		$this->_table->belongsToMany('Guardians', [
			'className' => 'Student.Guardians',
			'foreignKey' => 'student_user_id',
			'targetForeignKey' => 'security_user_id',
			'through' => 'Student.StudentGuardians',
			'dependent' => true
		]);
	}

	public function beforeFind(Event $event, Query $query, $options) {
		$query
			->join([
				'table' => 'institution_site_students',
				'alias' => 'InstitutionSiteStudents',
				'type' => 'INNER',
				'conditions' => [$this->_table->aliasField('id').' = '. 'InstitutionSiteStudents.security_user_id']
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

	public function addBeforeAction(Event $event) {
		$name = $this->_table->alias();
		$this->_table->ControllerAction->addField('institution_site_students.0.institution_site_id', [
			'type' => 'hidden', 
			'value' => 0
		]);
		$this->_table->fields['openemis_no']['attr']['value'] = $this->_table->getUniqueOpenemisId(['model'=>Inflector::singularize('Student')]);
	}

	public function indexBeforeAction(Event $event) {
		$this->_table->fields['student_institution_name']['visible'] = true;

		$this->_table->ControllerAction->field('name', []);
		$this->_table->ControllerAction->field('default_identity_type', []);
		$this->_table->ControllerAction->field('student_institution_name', []);
		$this->_table->ControllerAction->field('student_status', []);

		$this->_table->ControllerAction->setFieldOrder(['photo_content', 'openemis_no', 
			'name', 'default_identity_type', 'student_institution_name', 'student_status']);

		// $indexDashboard = 'Student.Students/dashboard';
		// $this->_table->controller->set('indexDashboard', $indexDashboard);
	}

	public function afterAction(Event $event) {
		$alias = $this->_table->alias;
		// $tableName = $this->_table->registryAlias();
		$table = TableRegistry::get('Institution.InstitutionSiteStudents');
		$institutionSiteArray = [];
		switch($alias){
			case "Students":
				$session = $this->_table->Session;
				$institutionId = $session->read('Institutions.id');
				// Total Students: number

				$query = $table->find()
					->where([$table->aliasField('institution_site_id') => $institutionId])
					->count();
				$institutionSiteArray['Gender'] = $table->getDonutChart('institution_site_student_gender', 
					['institution_site_id' => $institutionId]);
				$institutionSiteArray['Age'] = $table->getDonutChart('institution_site_student_age', 
					['institution_site_id' => $institutionId]);

				break;
			case "Users":
				$query = $table->find()
					->count();
				$institutionSiteArray['Gender'] = $table->getDonutChart('institution_site_student_gender');
				break;
		}

		if ($this->_table->action == 'index') {
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

	public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		// this method should rightfully be in institution userbehavior - need to move this in an issue after guardian module is in prod
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
				}
			}
		}

		if (array_key_exists('start_date', $data[$this->_table->alias()]['institution_site_students'][0])) {
			$startData = getdate(strtotime($data[$this->_table->alias()]['institution_site_students'][0]['start_date']));
			$data[$this->_table->alias()]['institution_site_students'][0]['start_year'] = (array_key_exists('year', $startData))? $startData['year']: null;
		}
		if (array_key_exists('end_date', $data[$this->_table->alias()]['institution_site_students'][0])) {
			$endData = getdate(strtotime($data[$this->_table->alias()]['institution_site_students'][0]['end_date']));
			$data[$this->_table->alias()]['institution_site_students'][0]['end_year'] = (array_key_exists('year', $endData))? $endData['year']: null;
		}
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$newOptions = [];
		$options['associated'] = ['InstitutionSiteStudents'];

		// Jeff: workaround, needs to redo this logic
		if (isset($data[$this->_table->alias()]['institution_site_students'])) {
			$students = $data[$this->_table->alias()]['institution_site_students'];
			if (!empty($students) && isset($students[0]) && isset($students[0]['institution_site_id'])) {
				if ($students[0]['institution_site_id'] == 0) {
					$data[$this->_table->alias()]['institution_site_students'][0]['start_date'] = date('Y-m-d');
					$data[$this->_table->alias()]['institution_site_students'][0]['end_date'] = date('Y-m-d', time()+86400);
					$data[$this->_table->alias()]['institution_site_students'][0]['education_programme_id'] = 0;
					$data[$this->_table->alias()]['institution_site_students'][0]['student_status_id'] = 0;
				}
			}
		}

		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);
	}

	public function afterSave(Event $event, Entity $entity, $options) {
		// this method should rightfully be in institution userbehavior - need to move this in an issue after guardian module is in prod
		if ($entity->isNew()) {
			// for attaching student to section
			if (array_key_exists('new', $this->_table->request->query)) {
				$sessionVar = $this->_table->alias().'.add.'.$this->_table->request->query['new'];
				if ($this->_table->Session->check($sessionVar)) {
					$institutionStudentData = $this->_table->Session->read($sessionVar);
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
