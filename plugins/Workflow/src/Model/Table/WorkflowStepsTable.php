<?php
namespace Workflow\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;

class WorkflowStepsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Workflows', ['className' => 'Workflow.Workflows']);
		$this->hasMany('WorkflowActions', ['className' => 'Workflow.WorkflowActions']);
		$this->belongsToMany('SecurityRoles', [
			'className' => 'Security.SecurityRoles',
			'joinTable' => 'workflow_step_roles',
			'foreignKey' => 'workflow_step_id',
			'targetForeignKey' => 'security_role_id'
		]);
	}

	public function beforeAction(Event $event) {
		$this->fields['stage']['visible'] = false;

		$this->ControllerAction->addField('security_roles', [
			'type' => 'chosen_select',
			'fieldNameKey' => 'security_roles',
			'fieldName' => $this->alias() . '.security_roles._ids',
			'placeholder' => __('Select Security Roles'),
			'order' => 3,
			'visible' => true
		]);
	}

	public function indexBeforeAction(Event $event) {
		//Add controls filter to index page
		$toolbarElements = [
            ['name' => 'Workflow.controls', 'data' => [], 'options' => []]
        ];

        $this->controller->set(compact('toolbarElements'));
	}

	public function viewBeforeQuery(Event $event, Query $query, array $contain) {
		//Retrieve associated data
		$contain[] = 'SecurityRoles';
		return compact('query', 'contain');
	}

	public function addEditBeforeAction(Event $event) {
		//Setup fields
		list($workflowOptions, , $securityRoleOptions) = array_values($this->getSelectOptions());

		$this->fields['workflow_id']['type'] = 'select';
		$this->fields['workflow_id']['options'] = $workflowOptions;

		$this->fields['security_roles']['options'] = $securityRoleOptions;

		$this->ControllerAction->setFieldOrder('workflow_id', 1);
	}

	public function addEditBeforePatch(Event $event, Entity $entity, array $data, array $options) {
		//Required by patchEntity for associated data
		$options['associated'] = ['SecurityRoles'];
		return compact('entity', 'data', 'options');
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		//Initialize field values
		list(, $selectedWorkflow) = array_values($this->getSelectOptions());

		$entity->workflow_id = $selectedWorkflow;

		return $entity;
	}

	public function editBeforeQuery(Event $event, Query $query, array $contain) {
		//Retrieve associated data
		$contain[] = 'SecurityRoles';
		return compact('query', 'contain');
	}

	public function getSelectOptions() {
		//Return all required options and their key
		$query = $this->request->query;

		$workflowOptions = $this->Workflows->find('list')->toArray();
		$selectedWorkflow = isset($query['workflow']) ? $query['workflow'] : key($workflowOptions);

		$SecurityRoles = TableRegistry::get('Security.SecurityRoles');
        $securityRoleOptions = $SecurityRoles->find('list')->toArray();
        $selectedSecurityRole = key($securityRoleOptions);

		return compact('workflowOptions', 'selectedWorkflow', 'securityRoleOptions', 'selectedSecurityRole');
	}
}
