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
use Cake\Datasource\ResultSetInterface;

use App\Model\Table\ControllerActionTable;

class StaffLeaveTable extends ControllerActionTable
{
	// Workflow Steps - category
	const TO_DO = 1;
	const IN_PROGRESS = 2;
	const DONE = 3;

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
		$this->addBehavior('Restful.RestfulAccessControl', [
        	'Dashboard' => ['index']
        ]);
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

	public function institutionStaffAfterDelete(Event $event, Entity $institutionStaffEntity)
	{
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

	public function findWorkbench(Query $query, array $options)
	{
		$controller = $options['_controller'];
		$session = $controller->request->session();

		$userId = $session->read('Auth.User.id');
		$Statuses = $this->Statuses;
		$doneStatus = self::DONE;

		$query
			->select([
				$this->aliasField('id'),
				$this->aliasField('status_id'),
				$this->aliasField('staff_id'),
				$this->aliasField('institution_id'),
				$this->aliasField('modified'),
				$this->aliasField('created'),
				$this->Statuses->aliasField('name'),
				$this->Users->aliasField('openemis_no'),
				$this->Users->aliasField('first_name'),
				$this->Users->aliasField('middle_name'),
				$this->Users->aliasField('third_name'),
				$this->Users->aliasField('last_name'),
				$this->Users->aliasField('preferred_name'),
				$this->StaffLeaveTypes->aliasField('name'),
				$this->Institutions->aliasField('code'),
				$this->Institutions->aliasField('name'),
				$this->CreatedUser->aliasField('openemis_no'),
				$this->CreatedUser->aliasField('first_name'),
				$this->CreatedUser->aliasField('middle_name'),
				$this->CreatedUser->aliasField('third_name'),
				$this->CreatedUser->aliasField('last_name'),
				$this->CreatedUser->aliasField('preferred_name')
			])
			->contain([$this->Users->alias(), $this->StaffLeaveTypes->alias(), $this->Institutions->alias(), $this->CreatedUser->alias()])
			->matching($this->Statuses->alias(), function ($q) use ($Statuses, $doneStatus) {
				return $q->where([$Statuses->aliasField('category <> ') => $doneStatus]);
			})
			->where([$this->aliasField('assignee_id') => $userId])
			->order([$this->aliasField('created') => 'DESC'])
			->formatResults(function (ResultSetInterface $results) {
				return $results->map(function ($row) {
					$url = [
						'plugin' => 'Institution',
						'controller' => 'Institutions',
						'action' => 'StaffLeave',
						'view',
						$row->id,
						'user_id' => $row->staff_id,
						'institution_id' => $row->institution_id
					];

					if (is_null($row->modified)) {
						$receivedDate = $this->formatDate($row->created);
					} else {
						$receivedDate = $this->formatDate($row->modified);
					}

					$row['url'] = $url;
	    			$row['status'] = $row->_matchingData['Statuses']->name;
	    			$row['request_title'] = $row->staff_leave_type->name.' '.__('of').' '.$row->user->name_with_id;
	    			$row['institution'] = $row->institution->code_name;
	    			$row['received_date'] = $receivedDate;
	    			$row['requester'] = $row->created_user->name_with_id;

					return $row;
				});
			});

		return $query;
	}
}
