<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Utility\Inflector;

use App\Model\Table\ControllerActionTable;

class InstitutionWorkflowsTable extends ControllerActionTable
{
	private $features = [
		'StaffBehaviours' => [
			'className' => 'Institution.StaffBehaviours'
		],
		'StudentBehaviours' => [
			'className' => 'Institution.StudentBehaviours'
		]
	];

	public function initialize(array $config)
	{
		parent::initialize($config);

		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps']);
		$this->belongsTo('Workflows', ['className' => 'Workflow.Workflows']);
		$this->belongsTo('Assignees', ['className' => 'User.Users']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);

		$this->belongsToMany('LinkedRecords', [
			'className' => 'Institution.StaffBehaviours',
			'joinTable' => 'institution_workflows_records',
			'foreignKey' => 'institution_workflow_id',
			'targetForeignKey' => 'record_id',
			'through' => 'Institution.InstitutionWorkflowsRecords',
			'dependent' => true
		]);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
	}

    public function onGetStatusId(Event $event, Entity $entity)
    {
        return '<span class="status highlight">' . $entity->status->name . '</span>';
    }

    public function onGetAssigneeId(Event $event, Entity $entity)
    {
        return empty($entity->assignee_id) ? '<span>&lt;'.$this->getMessage('general.unassigned').'&gt;</span>' : '';
    }

    public function onGetFeature(Event $event, Entity $entity)
    {
        $featureOptions = $this->getFeatureOptions();
        return $featureOptions[$entity->feature];
    }

	public function onGetLinkedRecords(Event $event, Entity $entity)
	{
		$linkedRecords = [];
		if ($entity->has('linked_records')) {
			foreach ($entity->linked_records as $key => $obj) {
				$linkedRecords[] = $obj['description'];
			}
		}

		return !empty($linkedRecords) ? implode(", ", $linkedRecords) : '';
	}

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $featureOptions = $this->getFeatureOptions();
        $selectedFeature = !is_null($this->request->query('feature')) ? $this->request->query('feature') : key($featureOptions);
        $filterOptions = [];
        $selectedFilter = '';
        $statusOptions = [];
        $selectedStatus = '';

        $extra['selectedFeature'] = $selectedFeature;
        $extra['selectedFilter'] = $selectedFilter;
        $extra['selectedStatus'] = $selectedStatus;

        $extra['elements']['control'] = [
            'name' => 'Institution.Workflows/controls',
            'data' => [
                'featureOptions'=> $featureOptions,
                'selectedFeature'=> $selectedFeature,
                'filterOptions'=> $filterOptions,
                'selectedFilter'=> $selectedFilter,
                'featureOptions'=> $featureOptions,
                'selectedFeature'=> $selectedFeature,
                'statusOptions'=> $statusOptions,
                'selectedStatus' => $selectedStatus
            ],
            'order' => 3
        ];

        $this->field('workflow_id', ['visible' => 'false']);
        $this->setFieldOrder(['feature', 'status_id', 'assignee_id', 'title']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $where = [];
        if (isset($extra['selectedFeature']) && !empty($extra['selectedFeature'])) {
            $where[$this->aliasField('feature')] = $extra['selectedFeature'];
        }
        if (isset($extra['selectedStatus']) && !empty($extra['selectedStatus'])) {
            $where[$this->aliasField('status_id')] = $extra['selectedStatus'];
        }

        $query->where($where);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
    	$query->contain(['LinkedRecords']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
    	$this->setupFields($entity, $extra);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
		$this->setupFields($entity, $extra);
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
    	$entity = $attr['entity'];

    	if ($action == 'add') {
    		$featureOptions = $this->getFeatureOptions();

    		$attr['type'] = 'select';
    		$attr['options'] = $featureOptions;
    		$attr['onChangeReload'] = 'changeFeature';
    	} else if ($action == 'edit') {
    		$featureOptions = $this->getFeatureOptions();

    		$attr['type'] = 'readonly';
    		$attr['value'] = $entity->feature;
    		$attr['attr']['value'] = $featureOptions[$entity->feature];
    	}

    	return $attr;
    }

    public function onUpdateFieldStatusId(Event $event, array $attr, $action, Request $request)
    {
    	if ($action == 'add') {
    		$attr['type'] = 'hidden';
    		$attr['value'] = 0;
    	} else if ($action == 'edit') {
    		$attr['type'] = 'hidden';
    	}

    	return $attr;
    }

    public function onUpdateFieldWorkflowId(Event $event, array $attr, $action, Request $request)
    {
    	if ($action == 'add') {
    		$attr['type'] = 'hidden';
    		$attr['value'] = 0;
    	} else if ($action == 'edit') {
    		$attr['type'] = 'hidden';
    	}

    	return $attr;
    }

    public function onUpdateFieldAssigneeId(Event $event, array $attr, $action, Request $request)
    {
    	if ($action == 'add') {
    		$attr['type'] = 'hidden';
    		$attr['value'] = 0;
    	} else if ($action == 'edit') {
    		$attr['type'] = 'hidden';
    	}

    	return $attr;
    }

    public function onUpdateFieldLinkedRecords(Event $event, array $attr, $action, Request $request)
    {
    	$session = $this->request->session();
    	$institutionId = null;
    	if ($session->check('Institution.Institutions.id')) {
            $institutionId = $session->read('Institution.Institutions.id');
        }

    	$entity = $attr['entity'];

    	if ($action == 'add' || $action == 'edit') {
    		if ($entity->has('feature') && !empty($entity->feature)) {
    			$registryAlias = $this->features[$entity->feature]['className'];
    			$linkedRecordTable = TableRegistry::get($registryAlias);
    			$linkedRecordOptions = $linkedRecordTable
    				->find('list', ['keyField' => 'id', 'valueField' => 'description'])
    				->where([$linkedRecordTable->aliasField('institution_id') => $institutionId])
    				->toArray();

				$attr['options'] = $linkedRecordOptions;
    		} else {
				$attr['visible'] = false;
    		}
    	}

    	return $attr;
    }

    public function getFeatureOptions()
    {
    	$featureOptions = [];
		foreach ($this->features as $key => $obj) {
			$featureOptions[$key] = __(Inflector::humanize(Inflector::underscore($key)));
		}

		return $featureOptions;
    }

    private function setupFields(Entity $entity, ArrayObject $extra)
    {
		$this->field('feature', ['entity' => $entity]);
		$this->field('status_id');
		$this->field('workflow_id', ['type' => 'hidden']);
		$this->field('assignee_id');
		$this->field('linked_records', ['type' => 'chosenSelect', 'entity' => $entity]);

		$this->setFieldOrder(['feature', 'status_id', 'assignee_id', 'title', 'linked_records']);
    }
}
