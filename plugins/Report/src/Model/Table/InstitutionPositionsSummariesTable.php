<?php
namespace Report\Model\Table;

use ArrayObject;
use DateTime;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Log\Log;
use Cake\Datasource\ResultSetInterface;

class InstitutionPositionsSummariesTable extends AppTable
{
    private $features = [];

    public function initialize(array $config)
    {
        $this->table('institution_positions');
        parent::initialize($config);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('StaffPositionTitles', ['className' => 'Institution.StaffPositionTitles']);
        $this->belongsTo('StaffPositionGrades', ['className' => 'Institution.StaffPositionGrades']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Assignees', ['className' => 'Security.Users']);
        $this->hasMany('InstitutionStaff', ['className' => 'Institution.Staff']);
        $this->addBehavior('Excel', [
            'autoFields' => false
        ]);
        $this->addBehavior('Report.InstitutionSecurity');
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('AcademicPeriod.Period');
    }

    // query change in POCOR-7460
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $academicperiodid = $requestData->academic_period_id;
        $area_level_id = $requestData->area_level_id;
        $statusFilter = $requestData->position_status;  //POCOR-7445
        $AcademicPeriodsTable = TableRegistry::get('academic_periods');

        $institutionId = $requestData->institution_id;
        $areaId = $requestData->area_education_id;
        $selectedArea = $requestData->area_education_id;
        $where = [];
        

        if ($statusFilter == 0) {
            $where = NULL;
            $statusFilters = NULL;
        }else{
            $where[$this->aliasField('status_id')] = $statusFilter;  
           $statusFilters = "AND institution_positions.status_id = ".$statusFilter;
        }
        if ($academicperiodid != -1) {
            $where[$AcademicPeriodsTable->aliasField('id')] = $academicperiodid; 
        }
        if ($institutionId == 0) { 
           $condition = NULL;
        }else{
            $where['institutions.id'] = $institutionId; 
            $condition = "AND institution_staff.institution_id = ".$institutionId;
        }

        //POCOR-7407 start
       if ($areaId != -1 && $areaId != '') {
            $areaIds = [];
            $allgetArea = $this->getChildren($selectedArea, $areaIds);
            $selectedArea1[]= $selectedArea;
            if(!empty($allgetArea)){
                $allselectedAreas = array_merge($selectedArea1, $allgetArea);
            }else{
                $allselectedAreas = $selectedArea1;
            }
                $where['Institutions.area_id IN'] = $allselectedAreas;
                $selectArea = "AND institutions.area_id IN = ".$allselectedAreas;
        }
        $this->InstitutionStaff = TableRegistry::get('institution_staff');
        $join = [];
        //POCOR-7407 end
        $query->select([
                'area_code' => 'areas.code',
                'area_name' => 'areas.name',
                'institutions_code' => 'institutions.code',
                'institutions_name' => 'institutions.name',
                'staff_position_titles' => 'staff_position_titles.name',
                'Category' => 'staff_position_categories.name',
                'staff_position_grades' =>'IFNULL(teaching_staff_info.staff_position_grades_name, "")',
                'total_male' => 'SUM(CASE WHEN teaching_staff_info.gender_id = 1 THEN 1 ELSE 0 END)',
                'total_female' => 'SUM(CASE WHEN teaching_staff_info.gender_id = 2 THEN 1 ELSE 0 END)',
                'total' =>'SUM(CASE WHEN teaching_staff_info.gender_id IN (1,2) THEN 1 ELSE 0 END)'
                ])
            ->innerJoin('institutions', [
                'institutions.id ='. $this->aliasField('institution_id')
            ])
            ->innerJoin('areas', [
                'areas.id = institutions.area_id'
            ])
            ->innerJoin('staff_position_titles', [
                'staff_position_titles.id ='. $this->aliasField('staff_position_title_id')
            ])
            ->innerJoin('staff_position_categories', [
                'staff_position_categories.id = staff_position_titles.staff_position_categories_id'
            ])->innerJoin('academic_periods', [
                '(
                    (institutions.date_closed IS NOT NULL AND institutions.date_opened <= academic_periods.start_date AND institutions.date_closed >= academic_periods.start_date)
                    OR
                    (institutions.date_closed IS NOT NULL AND institutions.date_opened <= academic_periods.end_date AND institutions.date_closed >= academic_periods.end_date)
                    OR
                    (institutions.date_closed IS NOT NULL AND institutions.date_opened >= academic_periods.start_date AND institutions.date_closed <= academic_periods.end_date)
                    OR
                    (institutions.date_closed IS NULL AND institutions.date_opened <= academic_periods.end_date)
                )']);

        $join['teaching_staff_info'] = [
        'type' => 'left',
         'table' => "(SELECT institution_staff.staff_position_grade_id
                    ,staff_position_grades.name staff_position_grades_name
                    ,institution_positions.id institution_position_id
                    ,security_users.gender_id
                FROM institution_staff
                INNER JOIN institution_positions
                ON institution_positions.id = institution_staff.institution_position_id
                INNER JOIN academic_periods
                ON (((institution_staff.end_date IS NOT NULL AND institution_staff.start_date <= academic_periods.start_date AND institution_staff.end_date >= academic_periods.start_date) OR (institution_staff.end_date IS NOT NULL AND institution_staff.start_date <= academic_periods.end_date AND institution_staff.end_date >= academic_periods.end_date) OR (institution_staff.end_date IS NOT NULL AND institution_staff.start_date >= academic_periods.start_date AND institution_staff.end_date <= academic_periods.end_date)) OR (institution_staff.end_date IS NULL AND institution_staff.start_date <= academic_periods.end_date))
                INNER JOIN security_users
                ON security_users.id = institution_staff.staff_id
                INNER JOIN staff_position_grades
                ON staff_position_grades.id = institution_staff.staff_position_grade_id 
                WHERE  academic_periods.id = $academicperiodid
                 $statusFilters $condition AND institution_staff.staff_status_id = 1
                GROUP BY institution_staff.staff_id,institution_positions.id )",
                'conditions' => ['teaching_staff_info.institution_position_id='. $this->aliasField('id')],
                ];
    $query->where($where)->group(['institutions.id','staff_position_titles.id','teaching_staff_info.staff_position_grade_id'])
    ->order(['areas.name','institutions.name','staff_position_titles.name']);
    $query->join($join);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $newFields = [];
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
            'key' => 'Institutions.code',
            'field' => 'institutions_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $newFields[] = [
            'key' => 'Institutions.name',
            'field' => 'institutions_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        $newFields[] = [
            'key' => 'StaffPositionTitles.name',
            'field' => 'staff_position_titles',
            'type' => 'string',
            'label' => __('Title')
        ];

        $newFields[] = [
            'key' => 'Category',
            'field' => 'Category',
            'type' => 'string',
            'label' => __('Category')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'total_male',
            'type' => 'string',
            'label' => __('Total Male')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'total_female',
            'type' => 'string',
            'label' => __('Total Female')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'total',
            'type' => 'string',
            'label' => __('Total')
        ];

        $fields->exchangeArray($newFields);
    }

    //POCOR-7407
    public function getChildren($id, $idArray) {
        $Areas = TableRegistry::get('Area.Areas');
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