<?php
namespace Training\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Network\Request;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;

class TrainingCoursesTable extends ControllerActionTable
{
	// Workflow Steps - category
	const TO_DO = 1;
	const IN_PROGRESS = 2;
	const DONE = 3;

	public function initialize(array $config)
	{
		parent::initialize($config);
		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
		$this->belongsTo('TrainingFieldStudies', ['className' => 'Training.TrainingFieldStudies', 'foreignKey' => 'training_field_of_study_id']);
		$this->belongsTo('TrainingCourseTypes', ['className' => 'Training.TrainingCourseTypes', 'foreignKey' => 'training_course_type_id']);
		$this->belongsTo('TrainingModeDeliveries', ['className' => 'Training.TrainingModeDeliveries', 'foreignKey' => 'training_mode_of_delivery_id']);
		$this->belongsTo('TrainingRequirements', ['className' => 'Training.TrainingRequirements', 'foreignKey' => 'training_requirement_id']);
		$this->belongsTo('TrainingLevels', ['className' => 'Training.TrainingLevels', 'foreignKey' => 'training_level_id']);
		$this->belongsTo('Assignees', ['className' => 'User.Users']);
		$this->hasMany('TrainingSessions', ['className' => 'Training.TrainingSessions', 'foreignKey' => 'training_course_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('TrainingNeeds', ['className' => 'Staff.TrainingNeeds', 'foreignKey' => 'course_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsToMany('TargetPopulations', [
			'className' => 'Institution.StaffPositionTitles',
			'joinTable' => 'training_courses_target_populations',
			'foreignKey' => 'training_course_id',
			'targetForeignKey' => 'target_population_id',
			'through' => 'Training.TrainingCoursesTargetPopulations',
			'dependent' => true
		]);
		$this->belongsToMany('TrainingProviders', [
			'className' => 'Training.TrainingProviders',
			'joinTable' => 'training_courses_providers',
			'foreignKey' => 'training_course_id',
			'targetForeignKey' => 'training_provider_id',
			'through' => 'Training.TrainingCoursesProviders',
			'dependent' => true
		]);
		$this->belongsToMany('CoursePrerequisites', [
			'className' => 'Training.PrerequisiteTrainingCourses',
			'joinTable' => 'training_courses_prerequisites',
			'foreignKey' => 'training_course_id',
			'targetForeignKey' => 'prerequisite_training_course_id',
			'through' => 'Training.TrainingCoursesPrerequisites',
			'dependent' => true
		]);
		$this->belongsToMany('Specialisations', [
			'className' => 'Training.TrainingSpecialisations',
			'joinTable' => 'training_courses_specialisations',
			'foreignKey' => 'training_course_id',
			'targetForeignKey' => 'training_specialisation_id',
			'through' => 'Training.TrainingCoursesSpecialisations',
			'dependent' => true
		]);
		$this->belongsToMany('ResultTypes', [
			'className' => 'Training.TrainingResultTypes',
			'joinTable' => 'training_courses_result_types',
			'foreignKey' => 'training_course_id',
			'targetForeignKey' => 'training_result_type_id',
			'through' => 'Training.TrainingCoursesResultTypes',
			'dependent' => true
		]);

		$this->setDeleteStrategy('restrict');
		$this->addBehavior('Workflow.Workflow');
		$this->addBehavior('ControllerAction.FileUpload', [
			// 'name' => 'file_name',
			// 'content' => 'file_content',
			'size' => '10MB',
			'contentEditable' => true,
			'allowable_file_types' => 'all',
			'useDefaultName' => true
		]);
		$this->addBehavior('Restful.RestfulAccessControl', [
        	'Dashboard' => ['index']
        ]);
	}

	public function validationDefault(Validator $validator)
	{
		$validator = parent::validationDefault($validator);

		return $validator
			->add('code', [
				'ruleUnique' => [
					'rule' => ['validateUnique'],
					'provider' => 'table'
				]
			])
			->allowEmpty('file_content');
	}

	public function beforeAction(Event $event, ArrayObject $extra)
	{
		// Type / Visible
		$visible = ['index' => false, 'view' => true, 'edit' => true, 'add' => true];
		$this->field('description', ['visible' => $visible]);
		$this->field('objective', ['visible' => $visible]);
		$this->field('duration', ['visible' => $visible]);
		$this->field('number_of_months', ['visible' => $visible]);
		$this->field('training_field_of_study_id', [
			'type' => 'select',
			'visible' => $visible
		]);
		$this->field('training_course_type_id', [
			'type' => 'select',
			'visible' => $visible
		]);
		$this->field('training_mode_of_delivery_id', [
			'type' => 'select',
			'visible' => $visible
		]);
		$this->field('training_requirement_id', [
			'type' => 'select',
			'visible' => $visible
		]);
		$this->field('training_level_id', [
			'type' => 'select',
			'visible' => $visible
		]);
		$this->field('file_name', [
			'type' => 'hidden',
			'visible' => $visible
		]);
		$this->field('file_content', ['visible' => $visible]);
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->setFieldOrder([
			'code', 'name', 'credit_hours'
		]);
	}

	public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{
		$query->contain(['TargetPopulations', 'TrainingProviders', 'CoursePrerequisites', 'Specialisations', 'ResultTypes']);
	}

	public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
	{
		$this->setupFields($entity);
	}

	public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
	{
		$this->setupFields($entity);
	}

	public function addOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
	{
		unset($this->request->query['course']);
	}

	public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
	{
		$this->request->query['course'] = $entity->id;
	}

	public function onUpdateFieldCreditHours(Event $event, array $attr, $action, Request $request)
	{
		$creditHours = TableRegistry::get('Configuration.ConfigItems')->value('training_credit_hour');

		for ($i=1; $i <= $creditHours; $i++) {
  			$attr['options'][$i] = $i;
  		}

		return $attr;
	}

	public function onUpdateFieldTargetPopulations(Event $event, array $attr, $action, Request $request)
	{
		if ($action == 'add' || $action == 'edit') {
			$attr['options'] = TableRegistry::get('Institution.StaffPositionTitles')->getList()->toArray();
		}

		return $attr;
	}

	public function onUpdateFieldTrainingProviders(Event $event, array $attr, $action, Request $request)
	{
		if ($action == 'add' || $action == 'edit') {
			$attr['options'] = TableRegistry::get('Training.TrainingProviders')->getList()->toArray();
		}

		return $attr;
	}

	public function onUpdateFieldCoursePrerequisites(Event $event, array $attr, $action, Request $request)
	{
		if ($action == 'add' || $action == 'edit') {
			$Courses = TableRegistry::get('Training.TrainingCourses');

			$id = $request->query('course');
			$excludes = [];
			if (!is_null($id)) {
				$excludes[$id] = $id;
			}

			$courseOptions = $this->Training->getCourseList(['excludes' => $excludes]);
			$attr['options'] = $courseOptions;
		}

		return $attr;
	}

	public function onUpdateFieldSpecialisations(Event $event, array $attr, $action, Request $request){
		if ($action == 'add' || $action == 'edit') {
			$attr['options'] = TableRegistry::get('Training.TrainingSpecialisations')->getList()->toArray();
		}

		return $attr;
	}

	public function onUpdateFieldResultTypes(Event $event, array $attr, $action, Request $request)
	{
		if ($action == 'add' || $action == 'edit') {
			$attr['options'] = TableRegistry::get('Training.TrainingResultTypes')->getList()->toArray();
		}

		return $attr;
	}

	public function setupFields(Entity $entity)
	{
		$this->field('credit_hours', ['type' => 'select']);
		$this->field('target_populations', [
			'type' => 'chosenSelect',
			'placeholder' => __('Select Target Populations'),
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
		$this->field('training_providers', [
			'type' => 'chosenSelect',
			'placeholder' => __('Select Providers'),
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
		$this->field('course_prerequisites', [
			'type' => 'chosenSelect',
			'placeholder' => __('Select Courses'),
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
		$this->field('specialisations', [
			'type' => 'chosenSelect',
			'placeholder' => __('Select Specialisations'),
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
		$this->field('result_types', [
			'type' => 'chosenSelect',
			'placeholder' => __('Select Result Types'),
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);

		// Field order
		$this->setFieldOrder([
			'code', 'name', 'description', 'objective', 'credit_hours', 'duration', 'number_of_months',
			'training_field_of_study_id', 'training_course_type_id', 'training_mode_of_delivery_id', 'training_requirement_id', 'training_level_id',
			'target_populations', 'training_providers', 'course_prerequisites', 'specialisations', 'result_types',
			'file_name', 'file_content'
		]);
	}

	public function findWorkbench(Query $query, array $options)
	{
		$controller = $options['_controller'];
		$controller->loadComponent('ControllerAction.ControllerAction');
		$session = $controller->request->session();

		$userId = $session->read('Auth.User.id');
		$Statuses = $this->Statuses;
		$doneStatus = self::DONE;

		$query
			->select([
				$this->aliasField('id'),
				$this->aliasField('status_id'),
				$this->aliasField('code'),
				$this->aliasField('name'),
				$this->aliasField('modified'),
				$this->aliasField('created'),
				$this->Statuses->aliasField('name'),
				$this->CreatedUser->aliasField('openemis_no'),
				$this->CreatedUser->aliasField('first_name'),
				$this->CreatedUser->aliasField('middle_name'),
				$this->CreatedUser->aliasField('third_name'),
				$this->CreatedUser->aliasField('last_name'),
				$this->CreatedUser->aliasField('preferred_name')
			])
			->contain([$this->CreatedUser->alias()])
			->matching($this->Statuses->alias(), function ($q) use ($Statuses, $doneStatus) {
				return $q->where([$Statuses->aliasField('category <> ') => $doneStatus]);
			})
			->where([$this->aliasField('assignee_id') => $userId])
			->order([$this->aliasField('created') => 'DESC'])
			->formatResults(function (ResultSetInterface $results) {

				return $results->map(function ($row) {
					$url = [
						'plugin' => 'Training',
						'controller' => 'Trainings',
						'action' => 'Courses',
						'view',
						$controller->ControllerAction->paramsEncode(['id' => $row->id])
					];

					if (is_null($row->modified)) {
						$receivedDate = $this->formatDate($row->created);
					} else {
						$receivedDate = $this->formatDate($row->modified);
					}

					$row['url'] = $url;
	    			$row['status'] = $row->_matchingData['Statuses']->name;
	    			$row['request_title'] = $row->code_name;
	    			$row['received_date'] = $receivedDate;
	    			$row['requester'] = $row->created_user->name_with_id;

					return $row;
				});
			});

		return $query;
	}
}
