<?php
namespace Report\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

/**
 * POCOR-6673
 * Generate Curricular Report data
 * get array data
 */ 
class CurricularsTable extends AppTable
{
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('institution_curriculars');
        parent::initialize($config);
        
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('CurricularTypes', ['className' => 'FieldOption.CurricularTypes']);
        

        $this->addBehavior('Excel', [
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $areaId = $requestData->area_education_id;
        $institutionId = $requestData->institution_id;
        $academicPeriodId = $requestData->academic_period_id;
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $periodEntity = $AcademicPeriods->get($academicPeriodId);
        $startDate = $periodEntity->start_date->format('Y-m-d');
        $endDate = $periodEntity->end_date->format('Y-m-d');
        $InstitutionCurricularStaff = TableRegistry::get('Institution.InstitutionCurricularStaff');
        $InstitutionCurricularStudent = TableRegistry::get('Institution.InstitutionCurricularStudents');
        $InstitutionCurricularPosition = TableRegistry::get('curricular_positions');
        $InstitutionCurriculartypes = TableRegistry::get('curricular_types');
        $staff = TableRegistry::get('Security.Users');
        $student = TableRegistry::get('Security.Users');
        $Genders = TableRegistry::get('User.Genders');
        $IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
        $UserIdentities = TableRegistry::get('User.Identities');
        $conditions = [];
        
        $conditions['AcademicPeriods.id'] = $academicPeriodId; 
        if (!empty($institutionId) && $institutionId > 0) {
            $conditions['Institutions.id'] = $institutionId; 
        }
        if (!empty($areaId) && $areaId != -1) {
            $conditions['Institutions.area_id'] = $areaId; 
        }
        $query
            ->select([
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',               
                'academic_period_name' => 'AcademicPeriods.name',               
                'area_code' => 'Areas.code',
                'area_name' => 'Areas.name',
                'area_administratives_code' => 'AreaAdministratives.code',
                'area_administratives_name' => 'AreaAdministratives.name',
                'first_name_stu' => $student->aliasField('first_name'),
                'last_name_stu' => $student->aliasField('last_name'),
                'curricular_name' => $this->aliasField('name'),
                'curricular_id' => $this->aliasField('id'),
                'curricular_type' => $InstitutionCurriculartypes->aliasField('name'),
                'category' => $this->aliasField('category'),
                'staff_id' => $InstitutionCurricularStaff->aliasField('staff_id'),
                'curricular_positions' => $InstitutionCurricularPosition->aliasField('name'),
                'points' => $InstitutionCurricularStudent->aliasField('points'),
                'hours' => $InstitutionCurricularStudent->aliasField('hours'),
                'location' => $InstitutionCurricularStudent->aliasField('location'),
                'comment' => $InstitutionCurricularStudent->aliasField('comments'),
                'Student_name' => $query->func()->concat([
                    'Users.first_name' => 'literal',
                    " ",
                    'Users.last_name' => 'literal'
                    ]),
                
            ])
            ->contain([
                'AcademicPeriods' => [
                    'fields' => [
                        'AcademicPeriods.id',
                        'AcademicPeriods.name'
                    ]
                ],
                'Institutions' => [
                    'fields' => [
                        'Institutions.id',
                        'Institutions.name',
                        'Institutions.code'
                    ]
                ],
                'Institutions.Areas' => [
                    'fields' => [
                        'Areas.name',
                        'Areas.code'
                    ]
                ],
                'Institutions.AreaAdministratives' => [
                    'fields' => [
                        'AreaAdministratives.name',
                        'AreaAdministratives.code'
                    ]
                ],
            ])
            ->leftJoin(
                    [$InstitutionCurricularStaff->alias() => $InstitutionCurricularStaff->table()],
                    [
                        $InstitutionCurricularStaff->aliasField('institution_curricular_id = ') . $this->aliasField('id'),
                    ]
                )
            ->leftJoin(
                    [$InstitutionCurricularStudent->alias() => $InstitutionCurricularStudent->table()],
                    [
                        $InstitutionCurricularStudent->aliasField('institution_curricular_id = ') . $this->aliasField('id')
                    ]
                )
            ->leftJoin(
                    [$InstitutionCurricularPosition->alias() => $InstitutionCurricularPosition->table()],
                    [
                        $InstitutionCurricularPosition->aliasField('id = ') . $InstitutionCurricularStudent->aliasField('curricular_position_id')
                    ]
                )
             ->leftJoin(
                    [$student->alias() => $student->table()],
                    [
                        $student->aliasField('id = ') . $InstitutionCurricularStudent->aliasField('student_id')
                    ])
             ->leftJoin(
                    [$InstitutionCurriculartypes->alias() => $InstitutionCurriculartypes->table()],
                    [
                        $InstitutionCurriculartypes->aliasField('id = ') . $this->aliasField('curricular_type_id')
                    ])
            ->where([$conditions])
            ->group([$this->aliasField('id')]);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'academic_period_name',
            'field' => 'academic_period_name',
            'type' => 'integer',
            'label' => __('Academic Period')
        ];

        $newFields[] = [
            'key' => 'Institutions.code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $newFields[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
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
            'label' => __('Area')
        ];

        /*$newFields[] = [
            'key' => 'AreaAdministratives.code',
            'field' => 'area_administratives_code',
            'type' => 'string',
            'label' => __('Area Administrative Code')
        ];

        $newFields[] = [
            'key' => 'AreaAdministratives.name',
            'field' => 'area_administratives_name',
            'type' => 'string',
            'label' => __('Area Administrative')
        ];*/

        

        $newFields[] = [
            'key' => 'curricular_name',
            'field' => 'curricular_name',
            'type' => 'string',
            'label' => __('Curricular Name')
        ];

        $newFields[] = [
            'key' => 'category',
            'field' => 'category',
            'type' => 'string',
            'label' => __('Category')
        ];

        $newFields[] = [
            'key' => 'curricular_type',
            'field' => 'curricular_type',
            'type' => 'string',
            'label' => __('Type')
        ];

        $newFields[] = [
            'key' => 'staff_name',
            'field' => 'staff_name',
            'type' => 'string',
            'label' => __('Staff Name')
        ];

        $newFields[] = [
            'key' => 'Student_name',
            'field' => 'Student_name',
            'type' => 'string',
            'label' => __('Student Name')
        ];

        $newFields[] = [
            'key' => 'hours',
            'field' => 'hours',
            'type' => 'integer',
            'label' => __('hours')
        ];

        $newFields[] = [
            'key' => 'points',
            'field' => 'points',
            'type' => 'string',
            'label' => __('Points')
        ];
        $newFields[] = [
            'key' => 'curricular_positions',
            'field' => 'curricular_positions',
            'type' => 'string',
            'label' => __('Curricular Positions')
        ];

        $newFields[] = [
            'key' => 'location',
            'field' => 'location',
            'type' => 'string',
            'label' => __('Location')
        ];
        $newFields[] = [
            'key' => 'comment',
            'field' => 'comment',
            'type' => 'string',
            'label' => __('Comment')
        ];

        $fields->exchangeArray($newFields);
    }

    public function onExcelGetStaffName(Event $event, Entity $entity)
    {
        $staffdata = [];
        $InstitutionCurricularStaff = TableRegistry::get('Institution.InstitutionCurricularStaff');
        $staff = TableRegistry::get('Security.Users');
        $staff = $InstitutionCurricularStaff->find()
                    ->select(['openemis_no' => $staff->aliasField('openemis_no'),
                        'first_name' => $staff->aliasField('first_name'),
                        'middle_name' => $staff->aliasField('middle_name'),
                        'third_name' => $staff->aliasField('third_name'),
                        'last_name' => $staff->aliasField('last_name')
                    ])
                    ->leftJoin(
                    [$staff->alias() => $staff->table()],
                    [
                        $staff->aliasField('id = ') . $InstitutionCurricularStaff->aliasField('staff_id'),
                    ])
                    ->where([$InstitutionCurricularStaff->aliasField('institution_curricular_id') => $entity->curricular_id])->toArray();
        foreach ($staff as $key => $value) {
            $staffdata[] = $value->openemis_no.' '.$value->first_name.' '.$value->middle_name.' '.$value->third_name.' '.$value->last_name;
        }

        return implode(', ', $staffdata); //display as comma seperated
    }

    public function onExcelGetCategory(Event $event, Entity $entity)
    {
         return $entity->category ? __('Curricular') : __('Extracurricular');
    }
}
