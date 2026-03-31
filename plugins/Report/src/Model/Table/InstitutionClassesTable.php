<?php

namespace Report\Model\Table;

use ArrayObject;
use Cake\Log\Log;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table; // POCOR-8929
use Cake\Utility\Inflector; // POCOR-8929

class InstitutionClassesTable extends AppTable
{
    // POCOR-6606 starts <vikas.rathore@mail.valuecoders.com>
    const CLASS_TEACHER = 'Home Room Teacher';
    const ASSISTANT_TEACHER = 'Secondary Teacher';

    // POCOR-6606 ends <vikas.rathore@mail.valuecoders.com>

    public function initialize(array $config): void
    {
        $this->setTable('institution_classes');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('InstitutionShifts', ['className' => 'Institution.InstitutionShifts', 'foreignKey' => 'institution_shift_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->hasMany('ClassesSecondaryStaff', ['className' => 'Institution.InstitutionClassesSecondaryStaff', 'saveStrategy' => 'replace', 'foreignKey' => 'institution_class_id']);

        $this->belongsToMany('EducationGrades', [
            'className' => 'Education.EducationGrades',
            'through' => 'Institution.InstitutionClassGrades',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'education_grade_id',
            'dependent' => true
        ]);

        $this->addBehavior('Excel', [
            'excludes' => [
                'class_number',
                'total_male_students',
                'total_female_students'
            ],
            'autoFields' => false
        ]);
        $this->addBehavior('Report.AreaList');//POCOR-7794
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.InstitutionSecurity');
        // POCOR-9126 start
        $this->addBehavior('Report.CustomFieldList', [
            'model' => 'Institution.InstitutionClasses',
            'formFilterClass' => null,
            'fieldValueClass' => ['className' => 'InstitutionCustomField.InstitutionClassesCustomFieldValues', 'foreignKey' => 'institution_class_id', 'dependent' => true, 'cascadeCallbacks' => true],
            'tableCellClass' => ['className' => 'InstitutionCustomField.InstitutionCustomTableCells', 'foreignKey' => 'institution_class_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
        ]);
        // POCOR-9126 end
    }

    public function beforeAction(EventInterface $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
        $this->field('institution_unit_id', ['visible' => false]);//POCOR-6863
        $this->field('institution_course_id', ['visible' => false]);//POCOR-6863
    }

    public function onUpdateFieldFeature(EventInterface $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->controller->getFeatureOptions('Institutions');
        return $attr;
    }

    public function onExcelGetInstitutionShiftId(EventInterface $event, Entity $entity)
    {
        return $entity->shift_name;
    }


    public static function getMaleCountByClass($classId)
    {
        $gender_id = 1; // male
        $count = self::getStudentCountByClassAndGender($classId, $gender_id);
        return $count;
    }

    public static function getFemaleCountByClass($classId)
    {
        $gender_id = 2; // female
        $count = self::getStudentCountByClassAndGender($classId, $gender_id);
        return $count;
    }

    public function onExcelGetEducationGrades(EventInterface $event, Entity $entity)
    {
        $classGrades = [];
        if ($entity->education_grades) {
            foreach ($entity->education_grades as $key => $value) {
                $classGrades[] = $value->name;
            }
        }
        return implode(', ', $classGrades); //display as comma seperated
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $institution_id = $requestData->institution_id;
        $gradesId = $requestData->education_grade_id;
        $areaId = $requestData->area_education_id;
        $areaLevelId = $requestData->area_level_id;//POCOR-7794
        $where = [];
        if ($institution_id > 0) { // POCOR-8929 escape null
            $where['Institutions.id'] = $institution_id;
        }
        //POCOR-7794 start
        $areaList = [];
        if (
            $areaLevelId > 1 && $areaId > 1
        ) {
            $areaList = $this->getAreaList($areaLevelId, $areaId);
        } elseif ($areaLevelId > 1) {

            $areaList = $this->getAreaList($areaLevelId, 0);
        } elseif ($areaId > 1) {
            $areaList = $this->getAreaList(0, $areaId);
        }
        if (!empty($areaList)) {
            $where['Institutions.area_id IN'] = $areaList;
        }
        //POCOR-7794 end
        if ($gradesId > -1) { // POCOR-8929 escape null
            $where['InstitutionClassGrades.education_grade_id'] = $gradesId;
        }

        $academic_period_id = $requestData->academic_period_id;
        $InstitutionClassesSecondaryStaff = self::getDynamicTableInstance('Institution.InstitutionClassesSecondaryStaff'); // POCOR-8929 removed not used
        $query
            ->select([
                $this->aliasField('id'),
                'class_id' => $this->aliasField('id'),
                'academic_period_id' => 'InstitutionClasses.academic_period_id',
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'institution_type' => 'Types.name',
                'area_name' => 'Areas.name',
                'area_code' => 'Areas.code',
                'area_administrative_code' => 'AreaAdministratives.code',
                'area_administrative_name' => 'AreaAdministratives.name',
                'shift_name' => 'ShiftOptions.name',
                'name' => 'InstitutionClasses.name',
                'capacity' => 'InstitutionClasses.capacity', //POCOR-6787
                'staff_name' => $query->func()->concat([
                    'Staff.openemis_no' => 'literal',
                    " - ",
                    'Staff.first_name' => 'literal',
                    " ",
                    'Staff.last_name' => 'literal'
                ]),
                'secondary_staff_name' => $query->func()->group_concat([
                    'SecurityUsers.openemis_no' => 'literal',
                    " - ",
                    'SecurityUsers.first_name' => 'literal',
                    " ",
                    'SecurityUsers.last_name' => 'literal'
                ]),
                'total_male_students' => 'InstitutionClasses.total_male_students',
                'total_female_students' => 'InstitutionClasses.total_female_students',
                'total_students' => $query->newExpr('InstitutionClasses.total_male_students + InstitutionClasses.total_female_students')
            ])
            ->contain([
                'AcademicPeriods' => [
                    'fields' => [
                        'AcademicPeriods.name'
                    ]
                ],
                'Institutions.Types',
                'Institutions.Areas',
                'Institutions.AreaAdministratives',
                'InstitutionShifts.ShiftOptions',
                'EducationGrades' => [
                    'fields' => [
                        'InstitutionClassGrades.institution_class_id',
                        'EducationGrades.id',
                        'EducationGrades.code',
                        'EducationGrades.name'
                    ]
                ],
                'Staff' => [
                    'fields' => [
                        'Staff.openemis_no',
                        'Staff.first_name',
                        'Staff.middle_name',
                        'Staff.third_name',
                        'Staff.last_name'
                    ]
                ]
            ])
            ->leftJoin(
                ['InstitutionClassesSecondaryStaff' => 'institution_classes_secondary_staff'],
                [
                    'InstitutionClassesSecondaryStaff.institution_class_id = ' . $this->aliasField('id')
                ]
            )
            ->leftJoin(
                ['SecurityUsers' => 'security_users'],
                [
                    'SecurityUsers.id = ' . $InstitutionClassesSecondaryStaff->aliasField('secondary_staff_id')
                ]
            )
            ->leftJoin(
                ['InstitutionClassGrades' => 'institution_class_grades'],
                [
                    'InstitutionClassGrades.institution_class_id = ' . $this->aliasField('id')
                ]
            )
            ->where([
                'InstitutionClasses.academic_period_id' => $academic_period_id,
                // 'InstitutionClassGrades.education_grade_id' => $gradesId,

                $where
            ])
            ->group([
                'InstitutionClasses.id'
            ])
            ->order([
//                'AcademicPeriods.order', // removed not used
                'Institutions.code',
                'InstitutionClasses.id'
            ]);

        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {

                $class_id = $row['class_id'];
                //POCOR-9613[START]

                // $femaleCountByClass = self::getFemaleCountByClass($class_id);

                // if ($femaleCountByClass != $row->total_female_students) {
                //     $this->updateAll(['total_female_students' => $femaleCountByClass], ['id' => $class_id]);
                //     $row['total_female_students'] = $femaleCountByClass;
                // }
                // $maleCountByClass = self::getMaleCountByClass($class_id);
                // if ($maleCountByClass != $row->total_male_students) {
                //     $this->updateAll(['total_male_students' => $maleCountByClass], ['id' => $class_id]);
                //     $row['total_male_students'] = $maleCountByClass;
                // }

                $row['total_female_students'] = $row['total_female_students'];
                $row['total_male_students'] = $row['total_male_students'];
                $row['total_students'] = $row['total_male_students'] + $row['total_female_students'];
                // $row['total_students'] = $maleCountByClass + $femaleCountByClass;
                //POCOR-9613[END]

               //POCOR-8739 start
                $areas1 = TableRegistry::getTableLocator()->get('Area.Areas');
                $areasData = $areas1
                    ->find()
                    ->where(['Areas.code' => $row->area_code])
                    ->first();
                //POCOR-8739 end
                $row['region_code'] = '';
                $row['region_name'] = '';
                if ($areasData->parent_id) { // POCOR-9070
                    $areas = self::getDynamicTableInstance('Area.Areas'); // POCOR-8929
                    $areaLevels = self::getDynamicTableInstance('area_levels'); // POCOR-8929
                    $institutions = self::getDynamicTableInstance('institutions'); // POCOR-8929
                    $val = $areas
                        ->find()
                        ->select([
                            $areas1->aliasField('code'),
                            $areas1->aliasField('name'),
                        ])
                        ->leftJoin(
                            [$areaLevels->getAlias() => $areaLevels->getTable()],
                            [
                                $areas->aliasField('area_level_id  = ') . $areaLevels->aliasField('id')
                            ]
                        )
                        ->leftJoin(
                            [$institutions->getAlias() => $institutions->getTable()],
                            [
                                $areas->aliasField('id  = ') . $institutions->aliasField('area_id')
                            ]
                        )
                        ->where([
                            $areaLevels->aliasField('level !=') => 1,
                            $areas->aliasField('id') => $areasData->parent_id
                        ])->first();

                    if (!empty($val->name) && !empty($val->code)) {
                        $row['region_code'] = $val->code;
                        $row['region_name'] = $val->name;
                    }
                }

                return $row;
            });
        });

    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
        //redeclare all for sorting purpose.
        $newFields[] = [
            'key' => 'InstitutionClasses.academic_period_id',
            'field' => 'academic_period_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'region_code',
            'type' => 'string',
            'label' => 'Region Code'
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'region_name',
            'type' => 'string',
            'label' => 'Region Name'
        ];

        $newFields[] = [
            'key' => 'Areas.code',
            'field' => 'area_code',
            'type' => 'string',
            'label' => __('Area Code')
        ];

        $newFields[] = [
            'key' => 'Areas.name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area Name')
        ];

        $newFields[] = [
            'key' => 'Institutions.institution_code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Institutions.institution_name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Types.institution_type',
            'field' => 'institution_type',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionClasses.institution_shift_id',
            'field' => 'institution_shift_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Education.education_grades',
            'field' => 'education_grades',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionClasses.name',
            'field' => 'name',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'staff_name',
            'type' => 'string',
            'label' => self::CLASS_TEACHER
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'secondary_staff_name',
            'type' => 'string',
            'label' => self::ASSISTANT_TEACHER
        ];

        //Start:POCOR-6787
        $newFields[] = [
            'key' => 'InstitutionClasses.capacity',
            'field' => 'capacity',
            'type' => 'string',
            'label' => 'Class Capacity'
        ];
        //End:POCOR-6787

        $newFields[] = [
            'key' => 'InstitutionClasses.total_male_students',
            'field' => 'total_male_students',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionClasses.total_female_students',
            'field' => 'total_female_students',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'total_students',
            'type' => 'integer',
            'label' => 'Total Students'
        ];

        $fields->exchangeArray($newFields);
    }

    /**
     * @param $classId
     * @param $gender_id
     * @return int
     */
    private static function getStudentCountByClassAndGender($classId, $gender_id)
    {
        $InstitutionClassStudents = self::getDynamicTableInstance('Institution.InstitutionClassStudents'); // POCOR-8929
        $count = $InstitutionClassStudents
            ->find()
            ->contain('Users')
            ->matching('StudentStatuses', function ($q) {
                return $q->where(['StudentStatuses.code NOT IN' => ['TRANSFERRED', 'WITHDRAWN']]);
            })
            ->where([$InstitutionClassStudents->Users->aliasField('gender_id') => $gender_id])
            ->where([$InstitutionClassStudents->aliasField('institution_class_id') => $classId])
            ->disableHydration() // POCOR-8929
            ->count();
        return $count;
    }

    /**
     * POCOR-8929 added
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName
     * @return \Cake\ORM\Table
     */
    private static function getDynamicTableInstance(string $alias, array $options = []): Table
    {
        // Parse plugin and table names if dot notation is used
        $locator = TableRegistry::getTableLocator();
        try {
            return $locator->get($alias, $options);
        } catch (\Exception $exception) {

        }
        $parts = explode('.', $alias);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($alias);
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
        return $locator->get($tableFullAlias, $options);
    }
}
