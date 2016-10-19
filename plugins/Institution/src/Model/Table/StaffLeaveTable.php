<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\Network\Request;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;

class StaffLeaveTable extends ControllerActionTable
{
	public function initialize(array $config)
	{
		$this->table('institution_staff_leave');
		parent::initialize($config);

		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
		$this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('StaffLeaveTypes', ['className' => 'Staff.StaffLeaveTypes']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('Assignees', ['className' => 'User.Users']);

		$this->addBehavior('ControllerAction.FileUpload', [
			// 'name' => 'file_name',
			// 'content' => 'file_content',
			'size' => '10MB',
			'contentEditable' => true,
			'allowable_file_types' => 'all',
			'useDefaultName' => true
		]);
		$this->addBehavior('Institution.InstitutionWorkflowAccessControl');
	}

	public function validationDefault(Validator $validator)
	{
		$validator = parent::validationDefault($validator);

		return $validator
			->add('date_to', 'ruleCompareDateReverse', [
				'rule' => ['compareDateReverse', 'date_from', true]
			])
			->allowEmpty('file_content')
		;
	}

	public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.InstitutionStaff.afterDelete'] = 'institutionStaffAfterDelete';
        return $events;
    }

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
	{
		$dateFrom = date_create($entity->date_from);
		$dateTo = date_create($entity->date_to);
		$diff = date_diff($dateFrom, $dateTo, true);
		$numberOfDays = $diff->format("%a");
		$entity->number_of_days = ++$numberOfDays;
	}

	public function beforeAction(Event $event, ArrayObject $extra)
	{
		$this->field('staff_leave_type_id', ['type' => 'select']);
		$this->field('number_of_days', [
			'visible' => ['index' => true, 'view' => true, 'edit' => false, 'add' => false]
		]);
		$this->field('file_name', [
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
		$this->field('file_content', [
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
		$this->field('staff_id', ['type' => 'hidden']);

		$this->setFieldOrder(['staff_leave_type_id', 'date_from', 'date_to', 'number_of_days', 'comments', 'file_name', 'file_content']);
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{
		$userId = $this->getUserId();
		$query->where([
			$this->aliasField('staff_id') => $userId
		]);
	}

	public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
	{
		$this->setupTabElements();
	}

	public function onUpdateFieldFileName(Event $event, array $attr, $action, Request $request)
	{
		if ($action == 'view') {
			$attr['type'] = 'hidden';
		} else if ($action == 'add' || $action == 'edit') {
			$attr['type'] = 'hidden';
		}

		return $attr;
	}

	public function onUpdateFieldStaffId(Event $event, array $attr, $action, Request $request)
	{
		if ($action == 'add') {
			$userId = $this->getUserId();

			$attr['value'] = $userId;
		}

		return $attr;
	}

	private function setupTabElements()
	{
		$options['type'] = 'staff';
		$userId = $this->getUserId();
		if (!is_null($userId)) {
			$options['user_id'] = $userId;
		}

		$tabElements = $this->controller->getCareerTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function getUserId()
	{
		$session = $this->request->session();
		if ($session->check('Staff.Staff.id')) {
			$userId = $session->read('Staff.Staff.id');
			return $userId;
		}

		return null;
	}

	public function institutionStaffAfterDelete(Event $event, Entity $institutionStaffEntity) {
		$staffLeaveData = $this->find()
            ->where([
    			$this->aliasField('staff_id') => $institutionStaffEntity->staff_id,
    			$this->aliasField('institution_id') => $institutionStaffEntity->institution_id,
    		])
            ->toArray();

        foreach ($staffLeaveData as $key => $staffLeaveEntity) {
            $this->delete($staffLeaveEntity);
        }
	}
}
