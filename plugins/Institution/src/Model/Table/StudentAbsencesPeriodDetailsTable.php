<?php

namespace Institution\Model\Table;

use Alert\Model\Table\AlertLogsTable; //POCOR-9509: use AlertLogsTable trigger helper instead of DashboardController trigger
use App\Model\Table\AppTable;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Filesystem\Folder;
use Cake\Mailer\Email;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\ORM\Locator\TableLocator;
use Cake\Log\Log;
use App\Controller\DashboardController;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Query;
use InvalidArgumentException;

class StudentAbsencesPeriodDetailsTable extends AppTable
{
    //POCOR-9509: per-request cache — resolved once, reused for all students saved in the same class batch
    private ?array $absenceAlertValidTypeIds = null;
    private mixed  $absenceAlert             = null; // false = checked but not found in DB
    private ?array $absenceAlertActiveRules  = null;

    public function initialize(array $config): void
    {
        $this->setTable('institution_student_absence_details');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('AbsenceTypes', ['className' => 'Institution.AbsenceTypes']);
        $this->belongsTo('StudentAbsenceReasons', ['className' => 'Institution.StudentAbsenceReasons']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);

        // $this->addBehavior('Institution.Calendar');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentAttendances' => ['index', 'view', 'add']
        ]);
    }

//    public function validationDefault(Validator $validator): Validator
//    {
//        $validator = parent::validationDefault($validator);
//        $absencesList = $this->AbsenceTypes->getCodeList();
//        $validator
//            ->allowEmpty('student_absence_reason_id', function ($context) use ($absencesList) {
//                if (isset($context['data']['absence_type_id']) && $context['data']['absence_type_id'] != 0) {
//                    $absenceTypeId = $context['data']['absence_type_id'];
//                    $code = $absencesList[$absenceTypeId];
//                    return ($code != 'EXCUSED');
//                }
//                return true;
//            });
//
//        return $validator;
//    }

//    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
//    {
//        $absenceTypeId = $data['absence_type_id'] ?? null;
//
//        if ($absenceTypeId && empty($data['student_absence_reason_id'])) {
//            $absenceTypes = $this->AbsenceTypes->getCodeList();
//            $code = $absenceTypes[$absenceTypeId] ?? null;
////
//            if ($code === 'EXCUSED') {
//                Log::debug(print_r(['EXUSED' => $data], true));
//                $data['comment'] = $data['comment'] ?? __('Reason not shown');
//                $data['student_absence_reason_id'] = 2; // or some fallback
//            }
//        }
//    }

    public function afterSaveCommit(EventInterface $event, Entity $entity, ArrayObject $options): Entity
    {
        // POCOR-9572: Debug logging (commented out for production)
        // Log::debug('========================================');
        // Log::debug('[SAVE PHP] afterSaveCommit() - Transaction committed');
        // Log::debug('Student ID: ' . $entity->student_id . ', Absence Type: ' . $entity->absence_type_id);
        // Log::debug('========================================');

        //For Import StudentAbsenceExcel only. Insert into student_attendace_mark_records once import sucessfully as attendance is counted as marked
        if ($entity->has('record_source') && $entity->record_source == 'import_student_attendances') {
            $StudentAttendanceMarkedRecords = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceMarkedRecords');

            $date = $entity->date->i18nFormat('YYY-MM-dd');

            $markRecordsData = [
                'institution_id' => $entity->institution_id,
                'academic_period_id' => $entity->academic_period_id,
                'institution_class_id' => $entity->institution_class_id,
                'education_grade_id' => $entity->education_grade_id,
                'subject_id' => $entity['subject_id'],
                'date' => $date,
                'period' => $entity->period
            ];

            $markRecord = $StudentAttendanceMarkedRecords->newEntity($markRecordsData);
            if (!$markRecord->getErrors()) {
                $StudentAttendanceMarkedRecords->save($markRecord);
            }
        }
        //POCOR-7165[START] Reason for commenting this is becouse its deleteting the data from parent table before the child table
        //which is creting foreign key constrain issue so its moved to before save.

        // if ($entity->absence_type_id == 0) {
        //     $this->delete($entity);
        //     $this->deleteStudentAbsence($entity);
        // }

        // if ($entity->isNew() || $entity->dirty('absence_type_id')) {
        //     $this->updateStudentAbsencesRecord($entity);
        // }
        //POCOR-7165[END]
        return $entity;
    }

    /*
    * This Function is to update and delete data from child table bofore parent table
    * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
    * return data
    * @ticket POCOR-7165
    */
    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        // POCOR-9572: Debug logging (commented out for production)
        // Log::debug('========================================');
        // Log::debug('[SAVE PHP] beforeSave() called');
        // Log::debug('Is new record: ' . ($entity->isNew() ? 'YES' : 'NO'));
        // Log::debug('Entity data: ' . json_encode([
        //     'student_id' => $entity->student_id,
        //     'absence_type_id' => $entity->absence_type_id,
        //     'student_absence_reason_id' => $entity->student_absence_reason_id ?? null,
        //     'comment' => $entity->comment ?? null,
        //     'date' => $entity->date ?? null,
        //     'period' => $entity->period ?? null
        // ]));

        if ($entity->absence_type_id == 0) {
            // Log::debug('[SAVE PHP] absence_type_id == 0 → DELETING record (PRESENT)');
            $this->delete($entity);
            $this->clearNoScheduledClass($entity); //POCOR-9652: reset flag when student marked present
            $event->stopPropagation();
            // Log::debug('[SAVE PHP] Record deleted, event stopped');
            // Log::debug('========================================');
            return $entity;
        }

        // Log::debug('[SAVE PHP] absence_type_id != 0 → Continuing with save');
        // Log::debug('========================================');

        // if ($entity->isNew() || $entity->dirty('absence_type_id')) {
        //     $this->updateStudentAbsencesRecord($entity);
        // }
    }

    public function findFirst(Query $query, array $options)
    {
        // POCOR-9572: Debug logging (commented out for production)
        // Log::debug('========================================');
        // Log::debug('[FIND PHP] findFirst() called with options: ' . json_encode($options));

        $compositeKeyFields = [
            'student_id',
            'institution_id',
            'academic_period_id',
            'institution_class_id',
            'date',
            'period',
            'subject_id'
        ];

        $conditions = [];

        foreach ($compositeKeyFields as $field) {
            if (isset($options[$field])) {
                $conditions[$field] = $options[$field];
            } else {
                Log::error('[FIND PHP] Missing composite key field: ' . $field);
                throw new InvalidArgumentException("Missing composite key field: {$field}");
            }
        }

        // Log::debug('[FIND PHP] Built WHERE conditions: ' . json_encode($conditions));
        // Log::debug('========================================');

        return $query->where($conditions)->limit(1);
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $requestData): Entity
    {
        // POCOR-9572: Debug logging (commented out for production)
        // Log::debug('========================================');
        // Log::debug('[SAVE PHP] afterSave() - Save completed successfully');
        // Log::debug('Saved entity data: ' . json_encode([
        //     'student_id' => $entity->student_id,
        //     'absence_type_id' => $entity->absence_type_id,
        //     'student_absence_reason_id' => $entity->student_absence_reason_id ?? null,
        //     'comment' => $entity->comment ?? null,
        //     'date' => $entity->date->format('Y-m-d') ?? null,
        //     'period' => $entity->period ?? null
        // ]));
        // Log::debug('========================================');

        $this->sendStudentAbsenceAlert($entity); // POCOR-9392 commented out alerts for absence
        $this->clearNoScheduledClass($entity); //POCOR-9652: reset flag when absence is saved
        return $entity;
    }


    /**
     * @param mixed $absenceTypeId
     * @param mixed $entity
     * @param $total_days
     * @return void
     */
    /**
     * Sends alert for a student absence if applicable.
     *
     * @param \Cake\ORM\Entity $entity The absence entity
     * @return void
     */
    private function sendStudentAbsenceAlert(Entity $entity): void
    {
        // Log::debug('@StudentAbsencesPeriodDetailsTable::sendStudentAbsenceAlert() ENTRY - entity_id=' . $entity->id); //[TEMP-LOG]
        // Log::debug('@StudentAbsencesPeriodDetailsTable::sendStudentAbsenceAlert() entity_data: student_id=' . $entity->student_id . ', absence_type_id=' . $entity->absence_type_id); //[TEMP-LOG]

        //POCOR-9509: load absence type IDs once per request, reuse for all students in the batch
        if ($this->absenceAlertValidTypeIds === null) {
            $AbsenceTypesTable = self::getDynamicTableInstance('absence_types');
            $unexcused = $AbsenceTypesTable->find()->where(['code' => 'UNEXCUSED'])->first();
            $excused   = $AbsenceTypesTable->find()->where(['code' => 'EXCUSED'])->first();
            $this->absenceAlertValidTypeIds = ($unexcused && $excused)
                ? [$unexcused->id, $excused->id]
                : [];
        }

        if (empty($this->absenceAlertValidTypeIds)
            || !in_array($entity->absence_type_id, $this->absenceAlertValidTypeIds, true)) {
            return;
        }

        //POCOR-9509: load alert config once per request
        if ($this->absenceAlert === null) {
            $alertsTable = self::getDynamicTableInstance('Alert.Alerts');
            $found = $alertsTable->find()
                ->where([
                    $alertsTable->aliasField('process_name') => 'AlertStudentAbsence',
                    $alertsTable->aliasField('frequency') => 'once'
                ])
                ->first();
            $this->absenceAlert = $found ?? false; // false = not found, skip next time too
        }

        if (!$this->absenceAlert) {
            return;
        }

        //POCOR-9509: load active rules once per request
        if ($this->absenceAlertActiveRules === null) {
            $alertRulesTable = self::getDynamicTableInstance('Alert.AlertRules');
            $this->absenceAlertActiveRules = $alertRulesTable->find()
                ->where([
                    $alertRulesTable->aliasField('feature') => $this->absenceAlert->name,
                    $alertRulesTable->aliasField('enabled') => 1
                ])
                ->toArray();
        }

        if (empty($this->absenceAlertActiveRules)) {
            return;
        }

        $activeRules = $this->absenceAlertActiveRules; //POCOR-9509: use cached rules
        $alert       = $this->absenceAlert;            //POCOR-9509: use cached alert

        // Load necessary tables
        $systemProcessesTable = self::getDynamicTableInstance('SystemProcesses');

        // Log::debug('@StudentAbsencesPeriodDetailsTable::sendStudentAbsenceAlert() Found ' . count($activeRules) . ' active rules'); //[TEMP-LOG]

        $userId = isset($entity->modified_user_id) && (int) $entity->modified_user_id !== 0
            ? (int) $entity->modified_user_id
            : (int) $entity->created_user_id;

        if ($userId === 0) {
            $userId = 1; // fallback default user ID
            // Log::debug('@StudentAbsencesPeriodDetailsTable::sendStudentAbsenceAlert() Using fallback userId=1'); //[TEMP-LOG]
        }

        // Log::debug('@StudentAbsencesPeriodDetailsTable::sendStudentAbsenceAlert() Resolved userId=' . $userId); //[TEMP-LOG]

        $extraOptions = [
            'student_id' => (int) $entity->student_id,
            'institution_id' => (int) $entity->institution_id,
            'institution_class_id' => (int) $entity->institution_class_id,
            'academic_period_id' => (string) $entity->academic_period_id,
            'period' => (int) $entity->period,
            'date' => $entity->date->format('Y-m-d'),
            'subject_id' => (int) $entity->subject_id,
        ];

        // Log::debug('@StudentAbsencesPeriodDetailsTable::sendStudentAbsenceAlert() extraOptions: ' . json_encode($extraOptions)); //[TEMP-LOG]

        //POCOR-9509: start - move absence alert triggering onto AlertLogsTable helper path
        foreach ($activeRules as $rule) {
            // Log::debug('@StudentAbsencesPeriodDetailsTable::sendStudentAbsenceAlert() Calling triggerAlertSystemProcess for rule_id=' . ($rule['id'] ?? $rule->id)); //[TEMP-LOG]

            AlertLogsTable::triggerAlertSystemProcess(
                $systemProcessesTable,
                is_array($rule) ? $rule : $rule->toArray(),
                $alert->process_name,
                $userId,
                $extraOptions
            );

            // Log::debug('@StudentAbsencesPeriodDetailsTable::sendStudentAbsenceAlert() triggerAlertSystemProcess returned'); //[TEMP-LOG]
        }
        //POCOR-9509: end - move absence alert triggering onto AlertLogsTable helper path

        // Log::debug('@StudentAbsencesPeriodDetailsTable::sendStudentAbsenceAlert() EXIT'); //[TEMP-LOG]
    }


    //POCOR-9652: start - clear no_scheduled_class flag when attendance is marked on a previously-blocked day
    private function clearNoScheduledClass(Entity $entity): void
    {
        $date = is_object($entity->date) ? $entity->date->format('Y-m-d') : (string)$entity->date;
        // Log::debug('[TEMP-LOG] clearNoScheduledClass: START student=' . $entity->student_id . ' class=' . $entity->institution_class_id . ' date=' . $date);
        $MarkedRecords = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceMarkedRecords');
        $updated = $MarkedRecords->updateAll(
            ['no_scheduled_class' => 0],
            [
                'institution_id'       => (int)$entity->institution_id,
                'academic_period_id'   => (int)$entity->academic_period_id,
                'institution_class_id' => (int)$entity->institution_class_id,
                'date'                 => $date,
                'no_scheduled_class'   => 1,
            ]
        );
        // Log::debug('[TEMP-LOG] clearNoScheduledClass: updated=' . $updated . ' rows');
    }
    //POCOR-9652: end

    /**
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName . POCOR-8231
     * @return \Cake\ORM\Table
     *
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        // Create a TableLocator instance
        $locator = TableRegistry::getTableLocator();

        try {
            // Try to get the table instance directly
            return $locator->get($tableName);
        } catch (\Exception $e) {
//            Log::debug('Error: ' . $e->getMessage());
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
