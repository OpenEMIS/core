<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;

/**
 * POCOR-9064
 * Teacher Classes Report
 *
 * Generates an Excel report listing all institution classes where a teacher
 * (Home Room Teacher) has been assigned (staff_id != 0).
 * @auth -divya.vishwakarma@dataforall.org
 * Filters  : Academic Period, Area Level, Area, Institution, Education Grade
 */
class TeacherClassesTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_classes');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', [
            'className'  => 'AcademicPeriod.AcademicPeriods',
            'foreignKey' => 'academic_period_id'
        ]);
        $this->belongsTo('Institutions', [
            'className'  => 'Institution.Institutions',
            'foreignKey' => 'institution_id'
        ]);
        $this->belongsTo('Staff', [
            'className'  => 'User.Users',
            'foreignKey' => 'staff_id'
        ]);
        $this->belongsToMany('EducationGrades', [
            'className'       => 'Education.EducationGrades',
            'through'         => 'Institution.InstitutionClassGrades',
            'foreignKey'      => 'institution_class_id',
            'targetForeignKey'=> 'education_grade_id'
        ]);

        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Excel', [
            'pages'      => false,
            'autoFields' => false
        ]);
    }

    public function onExcelBeforeStart(EventInterface $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheets[] = [
            'name'        => $this->getAlias(),
            'table'       => $this,
            'query'       => $this->find(),
            'orientation' => 'landscape'
        ];
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $requestData      = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id ?? null;
        $institutionId    = $requestData->institution_id ?? 0;
        $areaId           = $requestData->area_education_id ?? null;
        $selectedArea     = $areaId;
        $gradeId          = $requestData->education_grade_id ?? 0;

        $where = [
            $this->aliasField('staff_id') . ' != 0'
        ];

        //Academic Period filter
        if (!empty($academicPeriodId)) {
            $where[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }

        //Institution filter
        if ($institutionId > 0) {
            $where['Institutions.id'] = $institutionId;
        }

        //Grade filter
        if ($gradeId > 0) {
            $where['InstitutionClassGrades.education_grade_id'] = $gradeId;
        }

        //Area filter 
        if ($areaId != -1 && !empty($areaId)) {
            $areaIds = [];
            $allgetArea = $this->getChildren($selectedArea, $areaIds);

            $selectedAreaArr = [$selectedArea];

            $allselectedAreas = !empty($allgetArea)
                ? array_merge($selectedAreaArr, $allgetArea)
                : $selectedAreaArr;

            $where['Institutions.area_id IN'] = $allselectedAreas;
        }
        $query
            ->select([
                'academic_period'   => 'AcademicPeriods.name',
                'institution_code'  => 'Institutions.code',
                'institution_name'  => 'Institutions.name',
                'area_level'        => 'AreaLevels.name',
                'area_name'         => 'Areas.name',
                'education_grade'   => $query->func()->group_concat([
                    'EducationGrades.name' => 'literal'
                ]),
                'class_name'        => $this->aliasField('name'),
                'openemis_no'       => 'Staff.openemis_no',
                'teacher_name' => $query->func()->concat([
                        'Staff.first_name' => 'literal',
                        " ",
                        'Staff.last_name' => 'literal'
                    ]),
                ])

            ->innerJoin(
                ['AcademicPeriods' => 'academic_periods'],
                ['AcademicPeriods.id = ' . $this->aliasField('academic_period_id')]
            )

            ->innerJoin(
                ['Institutions' => 'institutions'],
                ['Institutions.id = ' . $this->aliasField('institution_id')]
            )

            ->leftJoin(
                ['Areas' => 'areas'],
                ['Areas.id = Institutions.area_id']
            )

            ->leftJoin(
                ['AreaLevels' => 'area_levels'],
                ['AreaLevels.id = Areas.area_level_id']
            )

            ->leftJoin(
                ['InstitutionClassGrades' => 'institution_class_grades'],
                ['InstitutionClassGrades.institution_class_id = ' . $this->aliasField('id')]
            )

            ->leftJoin(
                ['EducationGrades' => 'education_grades'],
                ['EducationGrades.id = InstitutionClassGrades.education_grade_id']
            )

            ->innerJoin(
                ['Staff' => 'security_users'],
                ['Staff.id = ' . $this->aliasField('staff_id')]
            )

            ->where($where)

            ->group([
                $this->aliasField('id')
            ]);
            
    }
    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key'   => 'academic_period',
            'field' => 'academic_period',
            'type'  => 'string',
            'label' => __('Academic Period')
        ];
        $newFields[] = [
            'key'   => 'area_level',
            'field' => 'area_level',
            'type'  => 'string',
            'label' => __('Area ')
        ];
        $newFields[] = [
            'key'   => 'area_name',
            'field' => 'area_name',
            'type'  => 'string',
            'label' => __('Area Education')
        ];

        $newFields[] = [
            'key'   => 'Institutions.code',
            'field' => 'institution_code',
            'type'  => 'string',
            'label' => __('Institution Code')
        ];

        $newFields[] = [
            'key'   => 'Institutions.name',
            'field' => 'institution_name',
            'type'  => 'string',
            'label' => __('Institution Name')
        ];

        $newFields[] = [
            'key'   => 'InstitutionClasses.name',
            'field' => 'class_name',
            'type'  => 'string',
            'label' => __('Class Name')
        ];

        $newFields[] = [
            'key'   => 'EducationGrades.name',
            'field' => 'education_grade',
            'type'  => 'string',
            'label' => __('Education Grade')
        ];

        $newFields[] = [
            'key'   => 'Staff.name',
            'field' => 'teacher_name',
            'type'  => 'string',
            'label' => __('Teacher Name')
        ];

        $newFields[] = [
            'key'   => 'openemis_no',
            'field' => 'openemis_no',
            'type'  => 'string',
            'label' => __('Openemis No')
        ];

        $fields->exchangeArray($newFields);
    }

    public function getChildren($id, $idArray) {
        $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
        $result = $Areas->find()
                           ->where([
                               $Areas->aliasField('parent_id') => $id
                            ])
                             ->toArray();
       foreach ($result as $key => $value) {
            $idArray[] = $value['id'];
           $idArray = $this->getChildren($value['id'], $idArray);
        }
        return $idArray;
    }
}
