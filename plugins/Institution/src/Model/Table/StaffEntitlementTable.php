<?php

namespace Institution\Model\Table;

use ArrayObject;
use Cake\Database\Schema\Table;
use DatePeriod;
use DateInterval;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\Validation\Validator;
use Cake\Datasource\ResultSetInterface;
use Cake\Collection\Collection;
use Cake\Log\Log;
use Cake\Http\ServerRequest;
use App\Model\Table\ControllerActionTable;
use Archive\Model\Table\DataManagementConnectionsTable as ArchiveConnections;
use Cake\Utility\Inflector;
use Cake\Datasource\Exception\RecordNotFoundException; // POCOR-9287

class StaffEntitlementTable extends ControllerActionTable
{
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    private $institutionId = null;
    private $staffId = null;

    public function initialize(array $config): void
    {
        $this->setTable('institution_staff_leave');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffLeaveTypes', ['className' => 'Staff.StaffLeaveTypes']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
//        $this->addBehavior('Institution.StaffProfile');
        $this->addBehavior('Institution.InstitutionTab');
        $this->addBehavior('Staff.StaffTab');
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('view', false);
        $this->toggle('remove', false);
    }

    public function indexAfterAction(EventInterface $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->setupTabElements();
    }
    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {

        $this->field('year', ['visible' => true]);
        $this->field('staff_leave_type'); // POCOR-9287
        $this->field('staff_leave_type_id', ['visible' => false]); // POCOR-9287
        $this->field('position_name', ['visible' => true]);
        $this->field('days_total', ['visible' => true]);
        $this->field('days_total_adjusted', ['visible' => true]);
        $this->field('days_taken', ['visible' => true]);
        $this->field('days_balance', ['visible' => true]);
        $this->field('date_from', ['visible' => false]);
        $this->field('date_to', ['visible' => false]);
        $this->field('institution_id', ['visible' => false]);
        $this->field('staff_id', ['visible' => false]);
        $this->field('start_time', ['visible' => false]);
        $this->field('end_time', ['visible' => false]);
        $this->field('full_day', ['visible' => false]);
        $this->field('comments', ['visible' => false]);
        $this->field('assignee', ['visible' => false]);
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('status', ['visible' => false]);
        $this->field('number_of_days', ['visible' => false]);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('assignee_id', ['visible' => false]);
        $this->field('status_id', ['visible' => false]);
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $yearsQuery = clone $query;
        $yearsQuery
            ->select(['year' => $query->func()->year([$this->aliasField('date_from') => 'identifier'])])
            ->distinct(['year']) // Get unique years
            ->order(['year' => 'DESC'])                                ->limit(2)
            ->enableHydration(false) // Return as array
           ; // Remove the existing group by clause for the year-only query

        $years = $yearsQuery->toArray();
        $yearsOptions = array_combine(
            array_column($years, 'year'),
            array_column($years, 'year')
        );
        $year = $this->request->getQuery('year');
        $selectedYear = !is_null($year) ? $year : null;
        $this->controller->set(compact('yearsOptions', 'selectedYear'));

        $extra['elements']['controls'] = ['name' => 'Institution.Entitlements/controls',
            'data' => ['yearsOptions' => $yearsOptions,
                'selectedYear' => $selectedYear], 'options' => [], 'order' => 1];

        // Execute the query and format the results
        $years = $yearsQuery->toArray();
        $yearsList = array_combine(
            array_column($years, 'year'),
            array_column($years, 'year')
        );
        $institutionId = $this->getInstitutionID();
        $staffId       = $this->getStaffID();

// 2. Prepare the base conditions array
        $instStaffConditions = [];

// Only add the = check if the ID is not null, otherwise check IS NULL
        if ($institutionId !== null) {
            $instStaffConditions['InstitutionStaff.institution_id ='] = $institutionId;
        } else {
            $instStaffConditions['InstitutionStaff.institution_id ='] = -1;
        }

        if ($staffId !== null) {
            $instStaffConditions['InstitutionStaff.staff_id ='] = $staffId;
        } else {
            $instStaffConditions['InstitutionStaff.staff_id = '] = -1;
        }

        // Add calculated fields, join related tables, and group the query
        $query
            ->select([
                'institution_id' => $this->aliasField('institution_id'),
                'staff_id' => $this->aliasField('staff_id'),
                'staff_leave_type_id' => 'StaffLeaveEntitlements.staff_leave_type_id', // POCOR-9287
                'year' => $query->func()->year([$this->aliasField('date_from') => 'identifier']), // Extract year from date_from
                'days_taken' => $query->newExpr()->add([
                    'SUM(DATEDIFF(' . $this->aliasField('date_to') . ', ' . $this->aliasField('date_from') . ') + 1)'
                ]),

//                'days_total_adjusted' => $query->newExpr()->add([
//                    'COALESCE(StaffLeavePolicyTypes.days, 0) + COALESCE(SUM(DISTINCT StaffLeaveEntitlements.adjustment), 0)'
//                ]),

                'days_balance' => $query->newExpr()->add([
                    'COALESCE(StaffLeavePolicyTypes.days, 0) + COALESCE(StaffLeaveEntitlements.total_adjustment, 0) - ' .
                    'SUM(DATEDIFF(' . $this->aliasField('date_to') . ', ' . $this->aliasField('date_from') . ') + 1)'
                ]),
                'position_name' => 'StaffPositionTitles.name', // Position name
                'staff_leave_policy_id' => 'StaffPositionTitles.staff_leave_policy_id', // Leave policy ID
                'days_total' => $query->func()->coalesce(['StaffLeavePolicyTypes.days' => 'literal', 0]), // Default to 0 if NULL
                'entitlements_adjustment' => $query->func()->coalesce(['StaffLeaveEntitlements.total_adjustment' => 'literal', 0]),
                'days_total_adjusted' => $query->newExpr()->add([
                    'COALESCE(StaffLeavePolicyTypes.days, 0) + COALESCE(StaffLeaveEntitlements.total_adjustment, 0)'
                ]),
            ])
            ->join([
                // POCOR-9287 start
                // Join StaffLeaveEntitlements
                'StaffLeaveEntitlements' => [
                    'table' => '(SELECT staff_id, staff_leave_type_id,
                    SUM(adjustment) AS total_adjustment FROM staff_leave_entitlements
                    GROUP BY staff_id, staff_leave_type_id)',
                    'type' => 'RIGHT',
                    'conditions' => [
                        'StaffLeaveEntitlements.staff_id = ' . $this->aliasField('staff_id'),
                        'StaffLeaveEntitlements.staff_leave_type_id = ' . $this->aliasField('staff_leave_type_id')
                    ]
                ],
                // POCOR-9287 end
                // Join InstitutionStaff
                'InstitutionStaff' => [
                    'table' => 'institution_staff',
                    'type' => 'INNER',
                    // POCOR-9287 start
                    'conditions' => $instStaffConditions
//                        'InstitutionStaff.start_date <= ' . $this->aliasField('date_from'),
//                        'OR' => [
//                            'InstitutionStaff.end_date IS NULL',
//                            'InstitutionStaff.end_date >= ' . $this->aliasField('date_to')
//                        ]
                        // POCOR-9287 end

                ],
                // Join InstitutionPositions
                'InstitutionPositions' => [
                    'table' => 'institution_positions',
                    'type' => 'INNER',
                    'conditions' => [
                        'InstitutionPositions.id = InstitutionStaff.institution_position_id'
                    ]
                ],
                // Join StaffPositionTitles
                'StaffPositionTitles' => [
                    'table' => 'staff_position_titles',
                    'type' => 'INNER',
                    'conditions' => [
                        'StaffPositionTitles.id = InstitutionPositions.staff_position_title_id'
                    ]
                ],
                // Join StaffLeavePolicyTypes
                'StaffLeavePolicyTypes' => [
                    'table' => 'staff_leave_policy_types',
                    'type' => 'LEFT', // Change to LEFT JOIN to handle missing records gracefully
                    'conditions' => [
                        'StaffLeavePolicyTypes.staff_leave_policy_id = StaffPositionTitles.staff_leave_policy_id',
                        'StaffLeavePolicyTypes.staff_leave_type_id = StaffLeaveEntitlements.staff_leave_type_id' // POCOR-9287 start
                    ]
                ],
                'StaffLeaveTypes' => [
                    'table' => 'staff_leave_types',
                    'type' => 'LEFT', // Change to LEFT JOIN to handle missing records gracefully
                    'conditions' => [
                        'StaffLeavePolicyTypes.staff_leave_type_id = StaffLeaveTypes.id'
                    ]
                ],
                // POCOR-9287 end
            ])
            ->group([
                'StaffEntitlement.staff_id',
                'StaffEntitlement.institution_id',
                'YEAR(StaffEntitlement.date_from)',
//                'StaffLeaveEntitlements.staff_leave_type_id',
                'InstitutionStaff.id',
//                'StaffPositionTitles.staff_leave_policy_id',
//                'StaffLeavePolicyTypes.days',
//                'StaffLeaveEntitlements.total_adjustment'
//                'days_total' // Group by days allocated for consistency
            ]);

        $search = $this->getSearchKey();


        $query->where(['StaffLeaveEntitlements.staff_id' => $this->getStaffID()], [],true); // POCOR-9287 start
        if (!empty($search)) {
            // function from AdvancedNameSearchBehavior
            $query->where(['OR' => ['StaffLeaveTypes.name LIKE ' => '%' . $search . '%',
                'StaffPositionTitles.name LIKE ' => '%' . $search . '%']]);
        }
        $institutionId = $this->getInstitutionID();
        $staffId = $this->getStaffID();
//        dd([[$selectedYear, $institutionId, $staffId],
//            $query->sql($query->getValueBinder())]);
        unset($extra['config']['search']);
        return $query;

    }

    // POCOR-9287 start
    public function onGetDaysTotal(EventInterface $event, Entity $entity)
    {
            return $entity->days_total . " ";

    }
    public function onGetYear(EventInterface $event, Entity $entity)
    {

        return $entity->year ?? __('Not Specified');

    }
    public function onGetDaysTaken(EventInterface $event, Entity $entity)
    {

        return $entity->days_taken ?? __('Not Specified');

    }
    public function onGetDaysBalance(EventInterface $event, Entity $entity)
    {

        return $entity->days_balance ?? __('Not Specified');

    }
    public function onGetStaffLeaveType(EventInterface $event, Entity $entity)
    {
        return $this->getRelatedName('Staff.StaffLeaveTypes', $entity->staff_leave_type_id);

    }

    /**
     * common proc to show related field in the index table
     * @param $tableName
     * @param $relatedField
     * @return string
     *
     */
    public function getRelatedName($tableName, $relatedField)
    {
        if (!$relatedField) {
            return "a";
        }
        $Table = self::getDynamicTableInstance($tableName);
        try {
            $related = $Table->get($relatedField);
            $name = strval($related->name);
            return $name;
        } catch (RecordNotFoundException $e) {
            return $relatedField;
        }
    }
    // POCOR-9287 end

    private function setupTabElements()
    {
        $options['type'] = 'staff';
        $staffId = $this->getStaffId();
        if (!is_null($staffId)) {
            $options['user_id'] = $staffId;
        }

        //$tabElements = $this->controller->getCareerTabElements($options);
        $tabElements = $this->getCareerTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->getAlias());
    }


    /**
     * Get a dynamic table instance with all associations.
     *
     * POCOR-8091
     *
     * @param string $tableName The name of the table.
     * @return \Cake\ORM\Table The table instance.
     * @throws \RuntimeException If there is an issue retrieving the table instance.
     *
     */
    private static function getDynamicTableInstance(string $tableName): \Cake\ORM\Table
    {
        // Parse plugin and table names if dot notation is used
        $locator = TableRegistry::getTableLocator();
        try {
            return $locator->get($tableName);
        } catch (\Exception $exception) {
            throw new \RuntimeException('Failed to get table instance', 0, $exception);
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
