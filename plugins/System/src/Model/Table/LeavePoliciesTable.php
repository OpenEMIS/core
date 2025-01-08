<?php

namespace System\Model\Table;

use ArrayObject;
use Cake\Utility\Inflector;
use Cake\Event\Event;
use Cake\ORM\Query;
use App\Model\Table\ControllerActionTable;
use Cake\Log\Log;
use Cake\I18n\Time;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class LeavePoliciesTable extends ControllerActionTable
{
    private $fieldsOrder = ['created', 'name'];
    public function initialize(array $config): void
    {
        $this->setTable('staff_leave_policies');
        parent::initialize($config);
//        $this->toggle('view', false);
//        $this->toggle('add', false);
//        $this->toggle('edit', false);
//        $this->toggle('remove', false);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $header = __(Inflector::humanize(Inflector::underscore($this->getAlias())));
        $this->controller->set('contentHeader', $header);
        $this->controller->Navigation->substituteCrumb(__('StaffPolicies'), $header);
        $this->controller->Navigation->substituteCrumb(__('Systems'), __('Staff'));
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {

    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $queryParams = $this->request->getQuery();
        if (!isset($queryParams['sort'])) {
            $query->order(
                [$this->aliasField('created') => 'DESC',
                    $this->aliasField('modified') => 'DESC']);
        }

    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {

    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
//        $this->setupFields($entity);
        $this->setfieldOrder($this->fieldsOrder);
    }
    private function setupFields(Entity $entity)
    {
        $this->field('staff_leave_types', [
            'type' => 'element',
            'element' => 'System.staff_leave_types',
            'attr' => [
                'label' => 'hello, darling'
            ]
        ]);
    }
    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
        $action = 'edit';
        $entity->staff_leave_types = $this->getStaffLeaveTypesElement($entity, $action);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
        $action = 'view';
        $entity->staff_leave_types = $this->getStaffLeaveTypesElement($entity, $action);
    }

    public function getStaffLeaveTypesElement($entity, $action)
    {
        $value = [];
        $staffLeaveTypesTable = self::getDynamicTableInstance('staff_leave_types');
        $staffLeavePolicyTypesTable = self::getDynamicTableInstance('staff_leave_policy_types');

        // Fetch all visible leave types
        $baseQuery = $staffLeaveTypesTable->find('all')
            ->where(['visible' => 1])
            ->orderAsc($staffLeaveTypesTable->aliasField('order'));

        // Action: New entity
        if ($entity->isNew()) {
            $staffLeaveTypes = $baseQuery->toArray();
            if (empty($staffLeaveTypes)) {
                return [];
            }

            foreach ($staffLeaveTypes as $staffLeaveType) {
                $value[] = [
                    'enable' => null,
                    'staff_leave_type_id' => $staffLeaveType->id,
                    'code' => $staffLeaveType->national_code,
                    'name' => $staffLeaveType->name,
                    'days' => null,
                    'rollover' => 0
                ];
            }
            return $value;
        }

        // Ensure we have a valid ID
        $id = $entity->id ?? null;
        if (!$id) {
            return [];
        }

        // Action: View
        if ($action === 'view') {
            $staffLeaveTypes = $baseQuery
                ->select([
                    'id' => $staffLeavePolicyTypesTable->aliasField('id'),
                    'enable' => $staffLeavePolicyTypesTable->aliasField('id'),
                    'staff_leave_type_id' => $staffLeaveTypesTable->aliasField('id'),
                    'code' => $staffLeaveTypesTable->aliasField('national_code'),
                    'name' => $staffLeaveTypesTable->aliasField('name'),
                    'days' => $staffLeavePolicyTypesTable->aliasField('days'),
                    'rollover' => $staffLeavePolicyTypesTable->aliasField('rollover')
                ])
                ->innerJoin(
                    [$staffLeavePolicyTypesTable->getAlias() => $staffLeavePolicyTypesTable->getTable()],
                    [
                        $staffLeavePolicyTypesTable->aliasField('staff_leave_type_id') . ' = ' . $staffLeaveTypesTable->aliasField('id'),
                        $staffLeavePolicyTypesTable->aliasField('staff_leave_policy_id') . ' = ' . $id
                    ]
                )
                ->toArray();

            if (empty($staffLeaveTypes)) {
                return [];
            }

            foreach ($staffLeaveTypes as $staffLeaveType) {
                $value[] = [
                    'staff_leave_type_id' => $staffLeaveType->staff_leave_type_id,
                    'code' => $staffLeaveType->code,
                    'name' => $staffLeaveType->name,
                    'days' => $staffLeaveType->days,
                    'rollover' => $staffLeaveType->rollover
                ];
            }
            return $value;
        }

        // Action: Edit
        if ($action === 'edit') {
            $staffLeaveTypes = $baseQuery
                ->select([
                    'id' => $staffLeavePolicyTypesTable->aliasField('id'),
                    'enable' => $staffLeavePolicyTypesTable->aliasField('id'),
                    'staff_leave_type_id' => $staffLeaveTypesTable->aliasField('id'),
                    'code' => $staffLeaveTypesTable->aliasField('national_code'),
                    'name' => $staffLeaveTypesTable->aliasField('name'),
                    'days' => $staffLeavePolicyTypesTable->aliasField('days'),
                    'rollover' => $staffLeavePolicyTypesTable->aliasField('rollover')
                ])
                ->leftJoin(
                    [$staffLeavePolicyTypesTable->getAlias() => $staffLeavePolicyTypesTable->getTable()],
                    [
                        $staffLeavePolicyTypesTable->aliasField('staff_leave_type_id') . ' = ' . $staffLeaveTypesTable->aliasField('id'),
                        $staffLeavePolicyTypesTable->aliasField('staff_leave_policy_id') . ' = ' . $id
                    ]
                )
                ->orderAsc($staffLeaveTypesTable->aliasField('order'))
                ->toArray();

            if (empty($staffLeaveTypes)) {
                return [];
            }
            $enabled = [];
            $disabled = [];
            foreach ($staffLeaveTypes as $staffLeaveType) {
                $record = [
                    'id' => $staffLeaveType->id,
                    'enable' => $staffLeaveType->enable ? 1 : 0,
                    'staff_leave_type_id' => $staffLeaveType->staff_leave_type_id,
                    'code' => $staffLeaveType->code,
                    'name' => $staffLeaveType->name,
                    'days' => $staffLeaveType->days,
                    'rollover' => $staffLeaveType->rollover
                ];
                if ($staffLeaveType->enable) {
                    $enabled[] = $record;
                } else {
                    $disabled[] = $record;
                }

            }
            $value = array_merge($enabled, $disabled);
            return $value;
        }

        return $value;
    }



    /**
     * POCOR-8391 added
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName
     * @return \Cake\ORM\Table
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        $locator = TableRegistry::getTableLocator();
        try {
            return $locator->get($tableName);
        } catch (\Exception $exception) {

        }
        $parts = explode('.', $tableName);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($tableName);
        $tableAlias = Inflector::camelize($table);

        // Create the fully qualified class name if a plugin is specified
        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $tableAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $tableAlias . 'Table';
        }
        // Check if the table instance already exists
        if (!$locator->exists($tableFullAlias)) {
            // Check if the specific table class exists
            if (!class_exists($className)) {
                $className = Table::class; // Fallback to generic Table class
            }

            // Configure a new table instance
            $locator->setConfig($tableAlias, [
                'className' => $className,
                'table' => $table,
                'alias' => $tableAlias,
            ]);
        }

        // Return the table instance
        return $locator->get($tableFullAlias);
    }

}
