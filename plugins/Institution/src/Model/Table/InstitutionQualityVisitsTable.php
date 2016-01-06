<?php
namespace Institution\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Event\Event;

class InstitutionQualityVisitsTable extends AppTable {
	private $SubjectStaff = null;

	public function initialize(array $config) {
		$this->table('institution_quality_visits');
		parent::initialize($config);

		$this->belongsTo('QualityVisitTypes', ['className' => 'FieldOption.QualityVisitTypes', 'foreignKey' => 'quality_visit_type_id']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Classes', ['className' => 'Institution.InstitutionClasses', 'foreignKey' => 'institution_class_id']);
		$this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);

		$this->addBehavior('AcademicPeriod.Period');
		$this->addBehavior('AcademicPeriod.AcademicPeriod');
		$this->addBehavior('ControllerAction.FileUpload', [
			// 'name' => 'file_name',
			// 'content' => 'file_content',
			'size' => '10MB',
			'contentEditable' => true,
			'allowable_file_types' => 'all'
		]);

		$this->SubjectStaff = TableRegistry::get('Institution.InstitutionClassStaff');
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		
		return $validator
			->allowEmpty('file_content');
	}

	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->field('comment', ['visible' => false]);
		$this->ControllerAction->field('file_name', ['visible' => false]);
		$this->ControllerAction->field('file_content', ['visible' => false]);
		$this->ControllerAction->field('academic_period_id', ['visible' => false]);

		$this->ControllerAction->setFieldOrder([
			'date', 'institution_class_id', 'staff_id', 'quality_visit_type_id'
		]);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupValues($entity);
		$this->setupFields($entity);
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain(['CreatedUser']);
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->setupValues($entity);
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function onGetStaffId(Event $event, Entity $entity) {
		return $entity->staff->name_with_id;
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view') {
		} else if ($action == 'add' || $action == 'edit') {
			$institutionId = $this->Session->read('Institution.Institutions.id');
			$Classes = $this->Classes;

			$periodOptions = $this->AcademicPeriods->getList();
			$selectedPeriod = $this->queryString('period', $periodOptions);
			$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
				'message' => '{{label}} - ' . $this->getMessage('general.noClasses'),
				'callable' => function($id) use ($Classes, $institutionId) {
					return $Classes
						->find()
						->where([
							$Classes->aliasField('institution_id') => $institutionId,
							$Classes->aliasField('academic_period_id') => $id
						])
						->count();
				}
			]);

			$attr['options'] = $periodOptions;
			$attr['onChangeReload'] = 'changePeriod';
		}

		return $attr;
	}

	public function onUpdateFieldInstitutionClassId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view') {
		} else if ($action == 'add' || $action == 'edit') {
			$institutionId = $this->Session->read('Institution.Institutions.id');
			$selectedPeriod = $request->query('period');
			$SubjectStaff = $this->SubjectStaff;

			$classOptions = [];
			if (!is_null($selectedPeriod)) {
				$classOptions = $this->Classes
					->find('list')
					->where([
						$this->Classes->aliasField('institution_id') => $institutionId,
						$this->Classes->aliasField('academic_period_id') => $selectedPeriod
					])
					->toArray();

				$selectedClass = $this->queryString('subject', $classOptions);
				$this->advancedSelectOptions($classOptions, $selectedClass, [
					'message' => '{{label}} - ' . $this->getMessage('general.noStaff'),
					'callable' => function($id) use ($SubjectStaff) {
						return $SubjectStaff
							->find()
							->where([
								$SubjectStaff->aliasField('institution_class_id') => $id
							])
							->count();
					}
				]);
			}

			$attr['options'] = $classOptions;
			$attr['onChangeReload'] = 'changeSubject';
		}

		return $attr;
	}

	public function onUpdateFieldStaffId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view') {
		} else if ($action == 'add' || $action == 'edit') {
			$selectedClass = $request->query('subject');

			$staffOptions = [];
			if (!is_null($selectedClass)) {
				$staff = $this->SubjectStaff
					->find()
					->contain('Users')
					->where([
						$this->SubjectStaff->aliasField('institution_class_id') => $selectedClass
					])
					->all();

				foreach ($staff as $key => $obj) {
					$staffOptions[$obj->staff_id] = $obj->user->name_with_id;
				}
			}

			$attr['options'] = $staffOptions;
		}

		return $attr;
	}

	public function onUpdateFieldEvaluator(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view') {
		} else if ($action == 'add') {
			// when add, is login user
			$firstName = $this->Auth->user('first_name');
			$lastName = $this->Auth->user('last_name');
			$evaluator = $firstName . " " . $lastName;

			$attr['type'] = 'readonly';
			$attr['value'] = $evaluator;
			$attr['attr']['value'] = $evaluator;
		}
		// else if ($action == 'edit') {
		// 	// when edit, is created user
		// 	$attr['type'] = 'readonly';
		// }

		return $attr;
	}

	public function addEditOnChangePeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['period']);
		unset($request->query['subject']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
					$request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
				}
			}
		}
	}

	public function addEditOnChangeSubject(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['subject']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('institution_class_id', $request->data[$this->alias()])) {
					$request->query['subject'] = $request->data[$this->alias()]['institution_class_id'];
				}
			}
		}
	}

	public function setupFields(Entity $entity) {
		$this->ControllerAction->field('academic_period_id', ['type' => 'select']);
		$this->ControllerAction->field('institution_class_id', ['type' => 'select']);
		$this->ControllerAction->field('staff_id', ['type' => 'select']);
		$this->ControllerAction->field('evaluator');
		$this->ControllerAction->field('file_name', [
			'type' => 'hidden',
			'visible' => ['view' => false, 'edit' => true]
		]);
		$this->ControllerAction->field('quality_visit_type_id', ['type' => 'select']);

		$this->ControllerAction->setFieldOrder([
			'date', 'academic_period_id', 'institution_class_id', 'staff_id',
			'evaluator', 'quality_visit_type_id', 'comment', 'file_name', 'file_content'
		]);
	}

	public function setupValues(Entity $entity) {
		$entity->evaluator = $entity->created_user->name;
	}
}
