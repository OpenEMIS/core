<?php

namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Database\Expression\IdentifierExpression;
use Cake\ORM\Table; //POCOR-9005

class GuardiansTable extends AppTable
{

    public function initialize(array $config): void
    {

        $this->setTable('student_guardians');
        parent::initialize($config);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Excel', [
            'pages' => false
        ]);
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {

        $sheets[] = [
            'name' => $this->getAlias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
    }

    public function onExcelGetStudentNameByGuardian(Event $event, Entity $entity)
    {
        $securityUsers = self::getDynamicTableInstance('Security.Users'); //POCOR-9005

        if (!is_null($entity->student_id)) {
            $getStudent = $securityUsers->find()
                ->select(['Users.first_name', 'Users.last_name'])
                ->leftJoin(['Guardians' => 'student_guardians'], [
                    'Users.id = ' . 'Guardians.student_id'
                ])
                ->where(['Guardians.student_id' => $entity->student_id])
                ->first();

            if (!empty($getStudent)) {
                return $getStudent->first_name . ' ' . $getStudent->last_name;
            }
        }

        return '';
    }

    public function onExcelGetGuardianFatherName(Event $event, Entity $entity)
    {

        $guardianData = self::getDynamicTableInstance('Security.Users'); //POCOR-9005
        $name = '';

        if (!is_null($entity->student_id)) {
            $getGuardian = $guardianData->find()
                ->select(['Users.first_name', 'Users.last_name'])
                ->leftJoin(['Guardians' => 'student_guardians'], [
                    'Users.id = ' . 'Guardians.guardian_id',
                ])
                ->leftJoin(['GuardiansRelation' => 'guardian_relations'], [
                    'Guardians.guardian_relation_id = ' . 'GuardiansRelation.id',
                ])
                ->where(['Guardians.student_id' => $entity->student_id, 'GuardiansRelation.name' => 'Father'])
                ->first();

            if (!empty($getGuardian)) {
                $name = $getGuardian->first_name . ' ' . $getGuardian->last_name;
            }
        }

        return $name;
    }

    public function onExcelGetFatherEmail(Event $event, Entity $entity)
    {

        $guardianData = self::getDynamicTableInstance('Security.Users'); //POCOR-9005
        $fatherEmail = '';

        if (!is_null($entity->student_id)) {
            $getGuardian = $guardianData->find()
                ->select(['Users.email'])
                ->leftJoin(['Guardians' => 'student_guardians'], [
                    'Users.id = ' . 'Guardians.guardian_id',
                ])
                ->leftJoin(['GuardiansRelation' => 'guardian_relations'], [
                    'Guardians.guardian_relation_id = ' . 'GuardiansRelation.id',
                ])
                ->where(['Guardians.student_id' => $entity->student_id, 'GuardiansRelation.name' => 'Father'])
                ->first();

            if (!empty($getGuardian)) {
                $fatherEmail = $getGuardian->email;
            }
        }

        return $fatherEmail;
    }

    public function onExcelGetFatherAddress(Event $event, Entity $entity)
    {

        $guardianData = self::getDynamicTableInstance('Security.Users'); //POCOR-9005
        $fatherAddress = '';

        if (!is_null($entity->student_id)) {
            $getGuardian = $guardianData->find()
                ->select(['Users.address'])
                ->leftJoin(['Guardians' => 'student_guardians'], [
                    'Users.id = ' . 'Guardians.guardian_id',
                ])
                ->leftJoin(['GuardiansRelation' => 'guardian_relations'], [
                    'Guardians.guardian_relation_id = ' . 'GuardiansRelation.id',
                ])
                ->where(['Guardians.student_id' => $entity->student_id, 'GuardiansRelation.name' => 'Father'])
                ->first();

            if (!empty($getGuardian)) {
                $fatherAddress = $getGuardian->address;
            }
        }

        return $fatherAddress;
    }

    public function onExcelGetGuardianMotherName(Event $event, Entity $entity)
    {

        $guardianData = self::getDynamicTableInstance('Security.Users'); //POCOR-9005
        $motherName = '';

        if (!is_null($entity->student_id)) {

            $motherDetails = $guardianData->find()
                ->select(['Users.first_name', 'Users.last_name'])
                ->leftJoin(['Guardians' => 'student_guardians'], [
                    'Users.id = ' . 'Guardians.guardian_id',
                ])
                ->leftJoin(['GuardiansRelation' => 'guardian_relations'], [
                    'Guardians.guardian_relation_id = ' . 'GuardiansRelation.id',
                ])
                ->where(['Guardians.student_id' => $entity->student_id, 'GuardiansRelation.name' => 'Mother'])
                ->first();

            if (!empty($motherDetails)) {
                $motherName = $motherDetails->first_name . ' ' . $motherDetails->last_name;
            }
        }

        return $motherName;
    }

    public function onExcelGetMotherEmail(Event $event, Entity $entity)
    {

        $guardianData = self::getDynamicTableInstance('Security.Users'); //POCOR-9005
        $motherEmail = '';

        if (!is_null($entity->student_id)) {
            $motherDetails = $guardianData->find()
                ->select(['Users.email'])
                ->leftJoin(['Guardians' => 'student_guardians'], [
                    'Users.id = ' . 'Guardians.guardian_id',
                ])
                ->leftJoin(['GuardiansRelation' => 'guardian_relations'], [
                    'Guardians.guardian_relation_id = ' . 'GuardiansRelation.id',
                ])
                ->where(['Guardians.student_id' => $entity->student_id, 'GuardiansRelation.name' => 'Mother'])
                ->first();

            if (!empty($motherDetails)) {
                $motherEmail = $motherDetails->email;
            }
        }

        return $motherEmail;
    }

    public function onExcelGetMotherAddress(Event $event, Entity $entity)
    {

        $guardianData = self::getDynamicTableInstance('Security.Users'); //POCOR-9005
        $motherAddress = '';

        if (!is_null($entity->student_id)) {
            $motherDetails = $guardianData->find()
                ->select(['Users.address'])
                ->leftJoin(['Guardians' => 'student_guardians'], [
                    'Users.id = ' . 'Guardians.guardian_id',
                ])
                ->leftJoin(['GuardiansRelation' => 'guardian_relations'], [
                    'Guardians.guardian_relation_id = ' . 'GuardiansRelation.id',
                ])
                ->where(['Guardians.student_id' => $entity->student_id, 'GuardiansRelation.name' => 'Mother'])
                ->first();

            if (!empty($motherDetails)) {
                $motherAddress = $motherDetails->address;
            }
        }

        return $motherAddress;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {

        $requestData = json_decode($settings['process']['params']);
        $institutionId = $requestData->institution_id;
        $institutionTypeId = $requestData->institution_type_id;
        $academicPeriodId = $requestData->academic_period_id;
        $areaId = $requestData->area_education_id;
        $conditions = [];
        if (!empty($institutionId) && $institutionId > 0) {
            $conditions['Institutions.id'] = $institutionId;
        }
        if (!empty($academicPeriodId) && $academicPeriodId > 0) {
            $conditions['InstitutionClassStudents.academic_period_id'] = $academicPeriodId;
        } //added to check year
        if (!empty($institutionTypeId) && $institutionTypeId > 0) {
            $conditions['Institutions.institution_type_id'] = $institutionTypeId;
        }
        if (!empty($areaId) && $areaId != -1) {
            $conditions['Institutions.area_id'] = $areaId;
        }

        $query
            ->select([
                'student_id' => 'Users.id',
                'student_first_name' => 'Users.first_name',
                'student_last_name' => 'Users.last_name',
                'openemis_no' => 'Users.openemis_no',
                'date_of_birth' => 'Users.date_of_birth',
                'student_status' => 'StudentStatuses.code',
                'institution_name' => 'Institutions.name',
                'education_grade_name' => 'EducationGrades.name',
                'institution_class_name' => 'InstitutionClasses.name',
                'institution_code' => 'Institutions.code',
                'gender_name' => 'Genders.name',
                'guardian_relation_name' => 'GuardianRelations.name',
                'guardian_first_name' => 'Guardian.first_name',
                'guardian_last_name' => 'Guardian.last_name',
                'address' => 'Guardian.address',
                'email' => 'Guardian.email',
                'area_code' => 'Areas.code',
                'area_name' => 'Areas.name',
                'contact_no' => 'UserContacts.value',
                'atoll' => 'AreaAdministrativeLevels.name',//POCOR-6728
                'education_code' => 'AreaAdministratives.code',//POCOR-9005 start
                'region_code' => 'ParentAreas.code',
                'region_name' => 'ParentAreas.name',
                'area_administrative_code' => 'AreaAdministratives.code',
                'area_administrative_name' => 'AreaAdministratives.name', //POCOR-9005 end
            ])
            ->leftJoin(['Users' => 'security_users'], [
                'Users.id = ' . 'Guardians.student_id'
            ])
            ->leftJoin(['Guardian' => 'security_users'], [
                'Guardian.id = ' . 'Guardians.guardian_id',
            ])
            ->leftJoin(['GuardianRelations' => 'guardian_relations'], [
                'GuardianRelations.id = ' . 'Guardians.guardian_relation_id',
            ])
            ->leftJoin(['Genders' => 'genders'], [
                'Users.gender_id = ' . 'Genders.id'
            ])
            ->leftJoin(['InstitutionStudents' => 'institution_students'], [
                'Users.id = ' . 'InstitutionStudents.student_id'
            ])
            ->leftJoin(['EducationGrades' => 'education_grades'], [
                'InstitutionStudents.education_grade_id = ' . 'EducationGrades.id'
            ])
            ->leftJoin(['Institutions' => 'institutions'], [
                'InstitutionStudents.institution_id = ' . 'Institutions.id'
            ])
            ->leftJoin(['Areas' => 'areas'], [
                'Institutions.area_id = ' . 'Areas.id'
            ])
            ->leftJoin( //POCOR-9005 start
                ['ParentAreas' => 'areas'],
                [
                    'ParentAreas.id = Areas.parent_id',
                ]
            )
            ->leftJoin(
                ['AreaLevels' => 'area_levels'],
                ['ParentAreas.area_level_id = AreaLevels.id',
                    'AreaLevels.level != 1']
            ) //POCOR-9005 end
            ->leftJoin(['AreaAdministratives' => 'area_administratives'], [
                'Institutions.area_administrative_id = ' . 'AreaAdministratives.id'
            ])
            ->leftJoin(['AreaAdministrativeLevels' => 'area_administrative_levels'], [
                'AreaAdministratives.area_administrative_level_id = ' . 'AreaAdministrativeLevels.id'
            ])
            ->leftJoin(['InstitutionClassStudents' => 'institution_class_students'], [
                'InstitutionClassStudents.student_id = ' . 'Users.id'
            ])
            ->leftJoin(['InstitutionClasses' => 'institution_classes'], [
                'InstitutionClasses.id = ' . 'InstitutionClassStudents.institution_class_id'
            ])
            ->leftJoin(['StudentStatuses' => 'student_statuses'], [
                'InstitutionStudents.student_status_id = ' . 'StudentStatuses.id'
            ])
            ->leftJoin(['UserContacts' => 'user_contacts'], [
                'Guardians.guardian_id = ' . 'UserContacts.security_user_id'
            ])
            ->group(['Users.id', 'Guardians.id']) //Student may have several guardians
            ->orderAsc('StudentStatuses.code')
            ->where([
                'StudentStatuses.code NOT IN' => ['TRANSFERRED', 'WITHDRAWN'], //students maybe in diff states in prev years
                'InstitutionClassStudents.student_status_id = ' . 'StudentStatuses.id',
//            'Areas.area_level_id !=' . 1,
                $conditions
            ]);
    //POCOR-9005 removed map
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $cloneFields = $fields->getArrayCopy();

        $extraFields[] = [
            'key' => 'Institutions.code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $extraFields[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        $extraFields[] = [
            'key' => 'Areas.code',
            'field' => 'area_code',
            'type' => 'string',
            'label' => __('Area Code')
        ];
        /**POCOR-6728 starts - uncommented area column*/
        $AreaLevelTbl = self::getDynamicTableInstance('Area.AreaLevels'); //POCOR-9005 start
        $AreaLevelArr = $AreaLevelTbl->find()
            ->select(['id', 'name'])->order(['id' => 'DESC'])
            ->where(['name IS NOT' => null, 'name !=' => ''])
            ->limit(2)
            ->disableHydration()
            ->toArray(); //POCOR-9005 end

        $extraFields[] = [
            'key' => '',
            'field' => 'region_name',
            'type' => 'string',
            'label' => __($AreaLevelArr[1]['name'])
        ];

        $extraFields[] = [
            'key' => '',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __($AreaLevelArr[0]['name'])
        ];
        /**POCOR-6728 ends*/

        $extraFields[] = [ // POCOR-6728
            'key' => 'education_code',
            'field' => 'education_code',
            'type' => 'string',
            'label' => __('Education Code')
        ];

        $extraFields[] = [
            'key' => 'Users.first_name',
            'field' => 'student_first_name',
            'type' => 'string',
            'label' => __('Student First Name')
        ];

        $extraFields[] = [
            'key' => 'Users.last_name',
            'field' => 'student_last_name',
            'type' => 'string',
            'label' => __('Student Last Name')
        ];

        $extraFields[] = [
            'key' => 'Users.date_of_birth',
            'field' => 'date_of_birth',
            'type' => 'string',
            'label' => __('Date Of Birth')
        ];

        $extraFields[] = [
            'key' => 'StudentStatuses.code',
            'field' => 'student_status',
            'type' => 'string',
            'label' => __('Student Status')
        ];

        $extraFields[] = [
            'key' => 'EducationGrades.name',
            'field' => 'education_grade_name',
            'type' => 'string',
            'label' => __('Education Grade')
        ];

        $extraFields[] = [
            'key' => 'InstitutionClasses.name',
            'field' => 'institution_class_name',
            'type' => 'string',
            'label' => __('Class')
        ];

        $extraFields[] = [
            'key' => 'Genders.name',
            'field' => 'gender_name',
            'type' => 'string',
            'label' => __('Gender')
        ];

        $extraFields[] = [
            'key' => 'Guardian.first_name',
            'field' => 'guardian_first_name',
            'type' => 'string',
            'label' => __('Guardians First Name')
        ];

        $extraFields[] = [
            'key' => 'Guardian.last_name',
            'field' => 'guardian_last_name',
            'type' => 'string',
            'label' => __('Guardians Last Name')
        ];

        $extraFields[] = [
            'key' => 'GuardianRelations.name',
            'field' => 'guardian_relation_name',
            'type' => 'string',
            'label' => __('Guardian Relationship')
        ];

        $extraFields[] = [
            'key' => 'Guardian.address',
            'field' => 'address',
            'type' => 'string',
            'label' => __('Guardians Address')
        ];

        $extraFields[] = [
            'key' => 'Guardian.email',
            'field' => 'email',
            'type' => 'string',
            'label' => __('Guardians Email')
        ];

        $extraFields[] = [
            'key' => 'contact_no',
            'field' => 'contact_no',
            'type' => 'integer',
            'label' => __('Guardians Primary Phone Contact')
        ];
        $newFields = $extraFields;

        $fields->exchangeArray($newFields);
    }

    /**
     * POCOR-9005 added
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
