<?php

namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use ArrayObject;
use Cake\Event\Event;
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

class StudentAbsencesPeriodDetailsTable extends AppTable
{
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

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $absencesList = $this->AbsenceTypes->getCodeList();
        $validator
            ->allowEmpty('student_absence_reason_id', function ($context) use ($absencesList) {
                if (isset($context['data']['absence_type_id']) && $context['data']['absence_type_id'] != 0) {
                    $absenceTypeId = $context['data']['absence_type_id'];
                    $code = $absencesList[$absenceTypeId];
                    return ($code != 'EXCUSED');
                }
                return true;
            });

        return $validator;
    }

    public function afterSaveCommit(Event $event, Entity $entity, ArrayObject $options)
    {
        //For Import StudentAbsenceExcel only. Insert into student_attendace_mark_records once import sucessfully as attendance is counted as marked
        if ($entity->has('record_source') && $entity->record_source == 'import_student_attendances') {
            $StudentAttendanceMarkedRecords = TableRegistry::get('Attendance.StudentAttendanceMarkedRecords');

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
    }

    /*
    * This Function is to update and delete data from child table bofore parent table
    * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
    * return data
    * @ticket POCOR-7165
    */
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->absence_type_id == 0) {
            $this->delete($entity);
        }

        // if ($entity->isNew() || $entity->dirty('absence_type_id')) {
        //     $this->updateStudentAbsencesRecord($entity);
        // }
    }


    public function afterSave(Event $event, Entity $entity, ArrayObject $requestData)
    {
        $this->sendStudentAbsenceAlert($entity); // POCOR-9392 commented out alerts for absence

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
//        Log::debug(print_r(['sendAlert' => $entity], true));

        $AbsenceTypesTable = self::getDynamicTableInstance('absence_types'); // POCOR-9162

        $unexcused = $AbsenceTypesTable->find()->where(['code' => 'UNEXCUSED'])->first();
        $excused = $AbsenceTypesTable->find()->where(['code' => 'EXCUSED'])->first();

        if (!$unexcused || !$excused) {
            Log::debug('Absence type IDs not found');
            return;
        }

        $validAbsenceTypeIds = [$unexcused->id, $excused->id];

        if (!in_array($entity->absence_type_id, $validAbsenceTypeIds, true)) {
            Log::debug('No alert sent because absence type is not valid for alert');
            return;
        }

        // Load necessary tables
        $alertsTable = self::getDynamicTableInstance('Alert.Alerts');
        $alertRulesTable = self::getDynamicTableInstance('Alert.AlertRules');
        $systemProcessesTable = self::getDynamicTableInstance('SystemProcesses');

        // Find the relevant alert
        $alert = $alertsTable->find()
            ->where([
                $alertsTable->aliasField('process_name') => 'AlertStudentAbsence',
                $alertsTable->aliasField('frequency') => 'once'
            ])
            ->first();

        if (!$alert) {
            Log::debug('No Alerts for AlertStudentAbsence');
            return;
        }

        $activeRules = $alertRulesTable->find()
            ->where([
                $alertRulesTable->aliasField('feature') => $alert->name,
                $alertRulesTable->aliasField('enabled') => 1
            ])
            ->toArray();

        if (empty($activeRules)) {
            Log::debug('No active alert rules for AlertStudentAbsence');
            return;
        }

        $userId = isset($entity->modified_user_id) && (int) $entity->modified_user_id !== 0
            ? (int) $entity->modified_user_id
            : (int) $entity->created_user_id;

        if ($userId === 0) {
            $userId = 1; // fallback default user ID
            Log::debug('Fallback user ID used. Entity dump:');
            Log::debug(print_r($entity, true));
        }

        $extraOptions = [
            'student_id' => (int) $entity->student_id,
            'institution_id' => (int) $entity->institution_id,
            'institution_class_id' => (int) $entity->institution_class_id,
            'academic_period_id' => (string) $entity->academic_period_id,
            'period' => (int) $entity->period,
            'date' => $entity->date->format('Y-m-d'),
            'subject_id' => (int) $entity->subject_id,
        ];

        foreach ($activeRules as $rule) {
//            Log::debug(print_r([
//                'Absence Alert Triggering' => $extraOptions,
//                'user_id' => $userId,
//                'alert' => $alert->toArray()
//            ], true));

            DashboardController::triggerSystemProcess(
                $systemProcessesTable,
                is_array($rule) ? $rule : $rule->toArray(),
                $alert->process_name,
                $userId,
                $extraOptions
            );
        }
    }


    /**
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName . POCOR-8231
     * @return \Cake\ORM\Table
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
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
            Log::debug('Error: ' . $e->getMessage());
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
