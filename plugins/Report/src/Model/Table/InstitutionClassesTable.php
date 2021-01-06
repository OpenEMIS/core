<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

class InstitutionClassesTable extends AppTable
{
    public const CLASS_TEACHER = 'Home Room Teacher';
    public const ASSISTANT_TEACHER = 'Secondary Teacher';

    public function initialize(array $config)
    {
        $this->table('institution_classes');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Staff', ['className' => 'User.Users',                       'foreignKey' => 'staff_id']);
        $this->belongsTo('InstitutionShifts', ['className' => 'Institution.InstitutionShifts',    'foreignKey' => 'institution_shift_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions',         'foreignKey' => 'institution_id']);
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
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.InstitutionSecurity');
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->controller->getFeatureOptions('Institutions');
        return $attr;
    }

    public function onExcelGetInstitutionShiftId(Event $event, Entity $entity)
    {
        return $entity->shift_name;
    }

    public function onExcelGetEducationGrades(Event $event, Entity $entity)
    {
        $classGrades = [];
        if ($entity->education_grades) {
            foreach ($entity->education_grades as $key => $value) {
                $classGrades[] = $value->name;
            }
        }

        return implode(', ', $classGrades); //display as comma seperated
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $institution_id = $requestData->institution_id;
        $where = [];
        if ($institution_id != 0) {
            $where['Institutions.id'] = $institution_id;
        }
        $academic_period_id = $requestData->academic_period_id;
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $StaffPositionTitles = TableRegistry::get('Institution.StaffPositionTitles');
        $Institutions = TableRegistry::get('Institution.Institutions');
        $InstitutionClassesSecondaryStaff = TableRegistry::get('Institution.InstitutionClassesSecondaryStaff');
        $query
            ->select([
                $this->aliasField('id'),
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
                'InstitutionClassesSecondaryStaff.institution_class_id = '. $this->aliasField('id')
            ]
            )
            ->leftJoin(
            ['SecurityUsers' => 'security_users'],
            [
                'SecurityUsers.id = '. $InstitutionClassesSecondaryStaff->aliasField('secondary_staff_id')
            ]
            )
            ->where([
                'InstitutionClasses.academic_period_id' => $academic_period_id,
                $where
            ])
            ->group([
                'InstitutionClasses.id'
            ])
            ->order([
                'AcademicPeriods.order',
                'Institutions.code',
                'InstitutionClasses.id'
            ]);


        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                
                $areas1 = TableRegistry::get('areas');
                $areasData = $areas1
                            ->find()
                            ->where([$areas1->alias('code')=>$row->area_code])
                            ->first();
                $row['region_code'] = '';            
                $row['region_name'] = '';
                if(!empty($areasData)){
                    $areas = TableRegistry::get('areas');
                    $areaLevels = TableRegistry::get('area_levels');
                    $institutions = TableRegistry::get('institutions');
                    $val = $areas
                                ->find()
                                ->select([
                                    $areas1->aliasField('code'),
                                    $areas1->aliasField('name'),
                                    ])
                                ->leftJoin(
                                    [$areaLevels->alias() => $areaLevels->table()],
                                    [
                                        $areas->aliasField('area_level_id  = ') . $areaLevels->aliasField('id')
                                    ]
                                )
                                ->leftJoin(
                                    [$institutions->alias() => $institutions->table()],
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

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
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
            'label' => __('District Code')
        ];

        $newFields[] = [
            'key' => 'Areas.name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('District Name')
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
}
