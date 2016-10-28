<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;

class InstitutionQualityVisitsTable extends ControllerActionTable
{
	private $SubjectStaff = null;

	public function initialize(array $config)
	{
		parent::initialize($config);

		$this->belongsTo('QualityVisitTypes', ['className' => 'FieldOption.QualityVisitTypes', 'foreignKey' => 'quality_visit_type_id']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Subjects', ['className' => 'Institution.InstitutionSubjects', 'foreignKey' => 'institution_subject_id']);
		$this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);

		$this->addBehavior('AcademicPeriod.Period');
		$this->addBehavior('AcademicPeriod.AcademicPeriod');
		$this->addBehavior('ControllerAction.FileUpload', [
			// 'name' => 'file_name',
			// 'content' => 'file_content',
			'size' => '10MB',
			'contentEditable' => true,
			'allowable_file_types' => 'all',
			'useDefaultName' => true
		]);
		$this->addBehavior('Institution.Visit');

		$this->SubjectStaff = TableRegistry::get('Institution.InstitutionSubjectStaff');
	}

	public function validationDefault(Validator $validator)
	{
		$validator = parent::validationDefault($validator);
		
		return $validator
			->allowEmpty('file_content');
	}

	public function beforeAction(Event $event, ArrayObject $extra)
    {
        $extra['config']['selectedLink'] = ['controller' => 'Institutions', 'action' => 'VisitRequests'];
    }

	public function indexBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->field('comment', ['visible' => false]);
		$this->field('file_name', ['visible' => false]);
		$this->field('file_content', ['visible' => false]);
		$this->field('academic_period_id', ['visible' => false]);

		$this->setFieldOrder([
			'date', 'institution_subject_id', 'staff_id', 'quality_visit_type_id'
		]);
	}

	public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
	{
		$this->setupValues($entity);
		$this->setupFields($entity);
	}

	public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{
		$query->contain(['CreatedUser']);
	}

	public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
	{
		$this->setupValues($entity);
	}

	public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
	{
		$this->setupFields($entity);
	}

	public function onGetStaffId(Event $event, Entity $entity)
	{
		if ($this->action == 'view') {
			return $event->subject()->Html->link($entity->staff->name_with_id , [
				'plugin' => 'Institution',
				'controller' => 'Institutions',
				'action' => 'StaffUser',
				'view',
				$entity->staff->id
			]);
		} else {
			return $entity->staff->name_with_id;
		}
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
	{
		if ($action == 'view') {
		} else if ($action == 'add' || $action == 'edit') {
			$institutionId = $this->Session->read('Institution.Institutions.id');
			$Subjects = $this->Subjects;

			$periodOptions = $this->AcademicPeriods->getList(['withSelect' => true, 'isEditable'=>true]);
			if (is_null($request->query('period'))) {
				$this->request->query['period'] = '';
			}
			$selectedPeriod = $this->queryString('period', $periodOptions);
			$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
				'message' => '{{label}} - ' . $this->getMessage('general.noSubjects'),
				'callable' => function($id) use ($Subjects, $institutionId) {
					return $Subjects
						->find()
						->where([
							$Subjects->aliasField('institution_id') => $institutionId,
							$Subjects->aliasField('academic_period_id') => $id
						])
						->count();
				}
			]);

			$attr['options'] = $periodOptions;
			$attr['onChangeReload'] = 'changePeriod';
		}

		return $attr;
	}

	public function onUpdateFieldInstitutionSubjectId(Event $event, array $attr, $action, Request $request)
	{
		if ($action == 'view') {
		} else if ($action == 'add' || $action == 'edit') {
			$institutionId = $this->Session->read('Institution.Institutions.id');
			$selectedPeriod = $request->query('period');
			$SubjectStaff = $this->SubjectStaff;

			$classOptions = [];
			if (!is_null($selectedPeriod)) {
				$classOptions = $this->Subjects
					->find('list')
					->where([
						$this->Subjects->aliasField('institution_id') => $institutionId,
						$this->Subjects->aliasField('academic_period_id') => $selectedPeriod
					])
					->toArray();
				$classOptions = ['' => __('-- Select Subject --')] + $classOptions;

				if (is_null($request->query('subject'))) {
					$this->request->query['subject'] = '';
				}
				$selectedClass = $this->queryString('subject', $classOptions);
				$this->advancedSelectOptions($classOptions, $selectedClass, [
					'message' => '{{label}} - ' . $this->getMessage('general.noStaff'),
					'callable' => function($id) use ($SubjectStaff) {
						return $SubjectStaff
							->find()
							->where([
								$SubjectStaff->aliasField('institution_subject_id') => $id
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

	public function onUpdateFieldStaffId(Event $event, array $attr, $action, Request $request)
	{
		if ($action == 'view') {
		} else if ($action == 'add' || $action == 'edit') {
			$selectedClass = $request->query('subject');

			$staffOptions = [];
			if (!is_null($selectedClass)) {
				$staff = $this->SubjectStaff
					->find()
					->contain('Users')
					->where([
						$this->SubjectStaff->aliasField('institution_subject_id') => $selectedClass
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

	public function onUpdateFieldEvaluator(Event $event, array $attr, $action, Request $request)
	{
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

	public function addEditOnChangePeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
	{
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

	public function addEditOnChangeSubject(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
	{
		$request = $this->request;
		unset($request->query['subject']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('institution_subject_id', $request->data[$this->alias()])) {
					$request->query['subject'] = $request->data[$this->alias()]['institution_subject_id'];
				}
			}
		}
	}

	public function setupFields(Entity $entity)
	{
		$this->field('academic_period_id', ['type' => 'select']);
		$this->field('institution_subject_id', ['type' => 'select']);
		$this->field('staff_id', ['type' => 'select']);
		$this->field('evaluator');
		$this->field('file_name', [
			'type' => 'hidden',
			'visible' => ['view' => false, 'edit' => true]
		]);
		$this->field('quality_visit_type_id', ['type' => 'select']);

		$this->setFieldOrder([
			'date', 'academic_period_id', 'institution_subject_id', 'staff_id',
			'evaluator', 'quality_visit_type_id', 'comment', 'file_name', 'file_content'
		]);
	}

	public function setupValues(Entity $entity)
	{
		$entity->evaluator = $entity->created_user->name;
	}
}
