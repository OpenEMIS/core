<?php

namespace Alert\Model\Behavior;

use ArrayObject;
use Cake\Log\Log;
use Cake\ORM\Entity;
use Cake\Event\EventInterface; //POCOR-9509: CakePHP 5 - replaced Cake\Event\Event
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

/* POCOR-7462 for cases alert rule */

class AlertRuleStudentStatusBehavior extends AlertRuleBehavior
{
    protected $_defaultConfig = [
        'feature' => 'StudentStatus',
        'name' => 'Student Status',
        'method' => ['Email','SMS'],
        'threshold' => [
            'statuses' => [
                'type' => 'chosenSelect',
                'select' => false,
                'after' => 'security_roles',
                'lookupModel' => 'Student.StudentStatuses',
                'attr' => ['required' => true], //POCOR-9509: mark statuses as required
            ],
        ],
        'placeholder' => [
            '${student_status}' => 'Student Status',
            '${academic_period.name}' => 'Academic Period Name',
            '${start_date}' => 'Student Study Start Date',
            '${end_date}' => 'Student Study End Date',
            '${student.openemis_no}' => 'Student OpenEMIS ID',
            '${student.name}' => 'Student Name',
            '${student.first_name}' => 'Student First Name',
            '${student.middle_name}' => 'Student Middle Name',
            '${student.third_name}' => 'Student Third Name',
            '${student.last_name}' => 'Student Last Name',
            '${student.preferred_name}' => 'Student Preferred Name',
            '${student.email}' => 'Student Email',
            '${student.address}' => 'Student Address',
            '${student.postal_code}' => 'Student Postal Code',
            '${student.date_of_birth}' => 'Student Date of Birth',
            '${institution.name}' => 'Institution (School) Name',
            '${institution.code}' => 'Institution (School) Code',
            '${institution.address}' => 'Institution Address',
            '${institution.postal_code}' => 'Institution Postal Code',
            '${institution.contact_person}' => 'Institution Contact Person',
            '${institution.telephone}' => 'Institution Telephone Number',
            '${institution.email}' => 'Institution Email',
            '${institution.website}' => 'Institution Website',
            '${grade.name}' => 'Education Grade Name',
            '${guardian.name}' => 'Guardian Name',
            '${guardian.relation}' => 'Guardian Relation',
            '${guardian.contact}' => 'Guardian Contact',

        ]

    ];

    public function initialize(array $config): void
    {
        parent::initialize($config);

    }

//    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
//    {
//        $model = $this->_table;
//        if (isset($data['feature']) && !empty($data['feature']) && $data['feature'] == $this->alertRule) {
//            if (isset($data['threshold']['statuses']) && !empty($data['threshold']['statuses'])) {
//                $statuses = $data['threshold']['statuses'];
//                $data['threshold'] = json_encode(['value' => $statuses]);
//            } else {
//                $data['threshold'] = json_encode(['value' => []]);
//            }
//        }
//    }

    //POCOR-9509: start - validate statuses is required non-empty array
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        $model = $this->_table;
        if (isset($data['feature']) && !empty($data['feature']) && $data['feature'] == $this->alertRule) {
            if (isset($data['submit']) && $data['submit'] == 'save') {
                $statusIds = $data['statuses']['_ids'] ?? [];
                if (empty($statusIds)) {
                    $data['statuses'] = null;
                }
                $validator = $model->getValidator();
                $validator->notEmptyString('statuses', __('Statuses cannot be empty'));
                $model->setValidator('forSave', $validator);
            }
        }
    }
    //POCOR-9509: end

    public function onStudentStatusSetupFields(EventInterface $event, Entity $entity) //POCOR-9509: CakePHP 5 - Event → EventInterface
    {
        $this->onAlertRuleSetupFields($event, $entity);
    }

    public function onGetStudentStatusThreshold(EventInterface $event, Entity $entity) //POCOR-9509: CakePHP 5 - Event → EventInterface
    {
        $thresholdData = json_decode($entity->threshold, true);
        return $thresholdData['value'];
    }

    public function onGetStatusThreshold(EventInterface $event, Entity $entity) //POCOR-9509: CakePHP 5 - Event → EventInterface
    {
        $thresholdData = json_decode($entity->threshold, true);
        return $thresholdData['value'];
    }

    /*
     * POCOR-8683
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
