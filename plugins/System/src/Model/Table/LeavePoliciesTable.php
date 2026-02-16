<?php

namespace System\Model\Table;

use ArrayObject;
use Cake\Utility\Inflector;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use App\Model\Table\ControllerActionTable;
use Cake\Log\Log;
use Cake\I18n\Time;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\ORM\Exception\PersistenceFailedException;

class LeavePoliciesTable extends ControllerActionTable
{
    private $fieldsOrder = ['name'];
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

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $header = __(Inflector::humanize(Inflector::underscore($this->getAlias())));
        $this->controller->set('contentHeader', $header);
        $this->controller->Navigation->substituteCrumb(__('StaffPolicies'), $header);
        $this->controller->Navigation->substituteCrumb(__('Systems'), __('Staff'));
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {

    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $queryParams = $this->request->getQuery();
        if (!isset($queryParams['sort'])) {
            $query->order(
                [$this->aliasField('created') => 'DESC',
                    $this->aliasField('modified') => 'DESC']);
        }

    }

    public function onGetFormButtons(EventInterface $event, ArrayObject $buttons)
    {

    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
    }

    public function afterAction(EventInterface $event, ArrayObject $extra)
    {
//        $this->setupFields($entity);
        $this->setfieldOrder($this->fieldsOrder);
    }
    private function setupFields(Entity $entity)
    {
        $this->field('id', [
            'type' => 'hidden',
        ]);
        $this->field('name');
        $this->field('code');
        $this->field('description');
        $this->field('staff_leave_types', [
            'type' => 'element',
            'element' => 'System.staff_leave_types',
            'attr' => [
                'label' => __('Leave Types')
            ]
        ]);
    }
    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
        $action = 'edit';
        $entity->staff_leave_types = $this->getStaffLeaveTypesElement($entity, $action);
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {

        $this->setupFields($entity);
        $this->field('created_user_id', ['visible' => true]);
        $this->field('created', ['visible' => true, 'sort' => true]);
        $this->field('modified_user_id', ['visible' => true, 'enable' => false]);
        $this->field('modified', ['visible' => true, 'sort' => true]);

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
                    'staff_policy_leave_type_id' => $staffLeavePolicyTypesTable->aliasField('id'),
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
                    'staff_policy_leave_type_id' => $staffLeaveType->staff_policy_leave_type_id,
                    'enable' => $staffLeaveType->enable ? 1 : 0,
                    'staff_leave_type_id' => $staffLeaveType->staff_leave_type_id,
                    'code' => $staffLeaveType->code,
                    'name' => $staffLeaveType->name,
                    'days' => $staffLeaveType->days,
                    'rollover' => $staffLeaveType->rollover == 1 ? 1 : 0
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

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
//        Log::debug(print_r($entity, true));
//        Log::debug(print_r($options, true));
//        Log::debug(print_r($event, true));
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
//        Log::debug(print_r($entity, true));
//        Log::debug(print_r($options, true));
//        Log::debug(print_r($event, true));
        $this->saveStaffLeavePolicy($entity);
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(EventInterface $event, Entity $entity)
    {
        $staffPositionTitlesTable = self::getDynamicTableInstance('staff_position_titles');

        $linkedRecordsCount = $staffPositionTitlesTable->find()
            ->where(['staff_leave_policy_id' => $entity->id])
            ->count();

        if ($linkedRecordsCount > 0) {
            $this->Alert->error('general.delete.restrictDeleteBecauseAssociation');
            $event->stopPropagation();  // Stop the delete event
//            throw new PersistenceFailedException($entity, "Cannot delete this leave policy because it is linked to $linkedRecordsCount position titles.");
            return false;
        }
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function afterDelete(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $staffLeavePolicyTypesTable = self::getDynamicTableInstance('staff_leave_policy_types');

        // Remove linked records in `staff_leave_policy_types` by `staff_leave_policy_id`
        $affectedRows = $staffLeavePolicyTypesTable->deleteAll([
            'staff_leave_policy_id' => $entity->id
        ]);

        Log::debug("Deleted $affectedRows linked staff_leave_policy_types records for policy ID: {$entity->id}");
    }


    /**
     * Saves the staff leave policy and its associated leave types.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity containing staff leave policy data.
     * @return bool True on success, false otherwise.
     */
    public function saveStaffLeavePolicy($entity)
    {
        // Load the `staff_leave_policy_types` table
        $staffLeavePolicyTypesTable = self::getDynamicTableInstance('staff_leave_policy_types');

        $policyId = $entity->id;  // Ensure we have the policy ID
        if (!$policyId) {
            return false;  // Return false if no policy ID is present
        }

        // Arrays to track changes and new records
        $changedLeaveTypes = [];  // Leave types that need updating
        $changedLeaveTypeEntities = [];  // Entities that were updated
        $newLeaveTypeEntities = [];  // Entities for newly created leave types
        $newLeaveTypes = [];  // Leave types to be created
        $linkedIdsForDeletion = [];  // IDs of leave types to be deleted

        // Extract submitted leave types from the entity
        $staffLeaveTypes = $entity->staff_leave_types ?? [];

        // Loop through the submitted `staff_leave_types` array
        foreach ($staffLeaveTypes as $staffLeaveType) {
            $isEnabled = (int)$staffLeaveType['enable'] === 1;
            $hasExistingId = !empty($staffLeaveType['staff_policy_leave_type_id']);

            if (!$isEnabled && $hasExistingId) {
                // Mark for deletion if the leave type is disabled and has an existing ID
                $linkedIdsForDeletion[] = $staffLeaveType['staff_policy_leave_type_id'];
                continue;
            }

            if ($isEnabled && $hasExistingId) {
                // Track changes if the leave type is enabled and has an existing ID
                $changedLeaveTypes[] = [
                    'id' => $staffLeaveType['staff_policy_leave_type_id'],
                    'staff_leave_policy_id' => $policyId,
                    'staff_leave_type_id' => $staffLeaveType['staff_leave_type_id'],
                    'days' => $staffLeaveType['days'],
                    'rollover' => $staffLeaveType['rollover']
                ];
            }

            if ($isEnabled && !$hasExistingId) {
                // Track new leave types if the leave type is enabled and has no existing ID
                $newLeaveTypes[] = [
                    'id' => Text::uuid(),  // Generate UUID for new record
                    'staff_leave_policy_id' => $policyId,
                    'staff_leave_type_id' => $staffLeaveType['staff_leave_type_id'],
                    'days' => $staffLeaveType['days'],
                    'rollover' => $staffLeaveType['rollover']
                ];
            }
        }

        // Delete disabled leave types linked to this policy
        if (!empty($linkedIdsForDeletion)) {
            $staffLeavePolicyTypesTable->deleteAll([
                'staff_leave_policy_id' => $policyId,
                'id IN' => $linkedIdsForDeletion
            ]);
            Log::debug('Deleted records for disabled leave types for policy ID: ' . $policyId);
        }

        // Save updated leave types
        foreach ($changedLeaveTypes as $changedLeaveType) {
            try {
                $changedLeaveTypeEntity = $staffLeavePolicyTypesTable->get($changedLeaveType['id']);
                $staffLeavePolicyTypesTable->patchEntity($changedLeaveTypeEntity, $changedLeaveType);
                if ($staffLeavePolicyTypesTable->save($changedLeaveTypeEntity)) {
                    $changedLeaveTypeEntities[] = $changedLeaveTypeEntity;
                } else {
                    Log::error("Failed to save updated leave type ID: {$changedLeaveType['id']}");
                }
            } catch (\Exception $e) {
                Log::error("Error fetching leave type ID: {$changedLeaveType['id']} - " . $e->getMessage());
            }
        }

        // Save new leave types
        foreach ($newLeaveTypes as $newLeaveType) {
            $newLeaveTypeEntity = $staffLeavePolicyTypesTable->newEntity($newLeaveType);
            if ($staffLeavePolicyTypesTable->save($newLeaveTypeEntity)) {
                $newLeaveTypeEntities[] = $newLeaveTypeEntity;
            } else {
                Log::error("Failed to save new leave type for policy ID: $policyId");
            }
        }

        return true;
    }



}
