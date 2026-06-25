<?php
//POCOR-9268 Starts
namespace Report\Model\Table;

use ArrayObject;
use DateInterval;
use DatePeriod;
use DateTime;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Database\Expression\ExpressionInterface;

class MealDetailsTable extends AppTable
{
    use OptionsTrait;
    protected $workingDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    public function initialize(array $config): void
    {
        $this->setTable('institution_meal_students');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('AreaLevels', ['className' => 'Area.AreaLevels']);
        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        $this->belongsTo('AreaAdministratives', ['className' => 'Area.AreaAdministratives']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('SecurityUsers', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('MealProgrammes', [
            'className' => 'Meal.MealProgrammes',
            'foreignKey' => 'meal_programmes_id'
        ]);
        $this->belongsTo('InstitutionClasses', [
            'className' => 'Institution.InstitutionClasses',
            'foreignKey' => 'institution_class_id',
            'joinType' => 'INNER',
        ]);

        $this->hasOne('InstitutionClassGrades', [
            'className' => 'Institution.InstitutionClassGrades',
            'foreignKey' => 'institution_class_id',
            'bindingKey' => 'institution_class_id',
            'joinType' => 'LEFT',
        ]);

        $this->belongsTo('EducationGrades', [
            'className' => 'Education.EducationGrades',
            'foreignKey' => 'education_grade_id',
            'joinType' => 'LEFT',
        ]);
        $this->addBehavior('Excel');
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.AreaList');
        $this->addBehavior('Report.InstitutionSecurity');
    }

    public function onExcelBeforeStart(EventInterface $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $requestData = json_decode($settings['process']['params']);
        $sheetsData = $this->generateSheetsData($requestData);
        $sheets->exchangeArray($sheetsData);        
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $sheetData = $settings['sheet']['sheetData'];
        $areaId = $requestData->area_education_id;
        $selectedArea = $requestData->area_education_id;
        $areaLevelId = $requestData->area_level_id;
        $institutionId = $requestData->institution_id;
        $academicPeriodId = $requestData->academic_period_id;

        $year = $sheetData['year'];
        $month = $sheetData['month'];

        $startDay = $sheetData['startDay'];
        $endDay = $sheetData['endDay'];

        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        $InstitutionClassGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionClassGrades');
        $EducationGrades = TableRegistry::getTableLocator()->get('Education.EducationGrades');
        $MealProgrammes = TableRegistry::getTableLocator()->get('Meal.MealProgrammes');
        $SecurityUsers = TableRegistry::getTableLocator()->get('Security.Users');
        $Areas = TableRegistry::getTableLocator()->get('Area.Areas');

        $conditions = [];

        if (!empty($academicPeriodId)) {
            $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }

        if (!empty($institutionId) && $institutionId > 0) {
            $conditions[$this->aliasField('institution_id')] = $institutionId;
        }else{
            $superAdmin = $requestData->super_admin;
            $userId = $requestData->user_id;
            
            $institutionIds = [];
            if (!$superAdmin) {
                $InstitutionsTable = TableRegistry::getTableLocator()->get('Institution.Institutions');
                $instituitionData = $InstitutionsTable->find('byAccess', ['userId' => $userId])->toArray();
                if (isset($instituitionData)) {
                    foreach ($instituitionData as $key => $value) {
                        $institutionIds[] = $value->id;
                    }
                }
                if ($institutionId == 0) {
                    $conditions[$this->aliasField('institution_id IN')] = $institutionIds;
                }
            }
        }
        
        if ($areaId != -1 && $areaId != '') {
            $areaIds = [];
            $allgetArea = $this->getChildren($selectedArea, $areaIds);
            $selectedArea1[]= $selectedArea;
            if(!empty($allgetArea)){
                $allselectedAreas = array_merge($selectedArea1, $allgetArea);
            }else{
                $allselectedAreas = $selectedArea1;
            }
            $conditions['Institutions.area_id IN'] = $allselectedAreas;
        }

        if ($areaLevelId > 1) {
           // $conditions[$this->aliasField('Areas.area_level_id')] = $areaLevelId;
        }

        $monthNameExpr = $query->func()->date_format([
            'MealDetails.date' => 'identifier',
            "'%Y %M'" => 'literal'
        ]);
        $fullNameExpr = new \Cake\Database\Expression\QueryExpression("TRIM(CONCAT_WS(' ', SecurityUsers.first_name, SecurityUsers.middle_name, SecurityUsers.third_name, SecurityUsers.last_name))");
        $query
            ->select([
                'academic_period'         => 'AcademicPeriods.name',
                'Institutions.id',
                'institution_code'        => 'Institutions.code',
                'institution_name'        => 'Institutions.name',
                'education_grade'         => 'EducationGrades.name',
                'class'                   => 'InstitutionClasses.name',
                'meal_programme'          => 'MealProgrammes.name',
                'MealDetails.institution_class_id', 
                'MealDetails.institution_id',
                'MealDetails.date',
                'education_grade_id'      => 'InstitutionClassGrades.education_grade_id',     // Optional
                'openemis_no'             => 'SecurityUsers.openemis_no',
                'gender_name'             => 'Genders.name',
                'student_name' => $fullNameExpr,
                'month' => $monthNameExpr
            ]);
            
            // Add day-wise aggregation (Day 1 to Day 31)
            for ($day = $startDay; $day <= $endDay; $day++) {
                $caseExpr = $query->newExpr("SUM(CASE WHEN DAY(MealDetails.date) = {$day} THEN 1 ELSE 0 END)");
                $query->select(["day_{$day}" => $caseExpr]);
            }
        
            $query    
                ->contain([
                    'SecurityUsers' => ['fields' => ['id', 'first_name','middle_name','third_name','last_name','openemis_no', 'gender_id']],
                    'InstitutionClasses' => ['fields' => ['id', 'name']],
                    'InstitutionClasses.InstitutionClassGrades.EducationGrades' => ['fields' => ['id', 'name']],
                    'Institutions' => ['fields' => ['id', 'code', 'name', 'area_id']],
                    'Institutions.Areas' => ['fields' => ['id', 'area_level_id']],
                ])
                //->leftJoinWith('SecurityUsers')
                ->leftJoinWith('InstitutionClasses.InstitutionClassGrades.EducationGrades') // important for JOIN
                ->leftJoinWith('Institutions.Areas') // to use Areas in WHERE
                ->leftJoin(
                    ['AcademicPeriods' => 'academic_periods'],
                    ['AcademicPeriods.id = MealDetails.academic_period_id']
                )
                ->leftJoin(
                    ['MealProgrammes' => 'meal_programmes'],
                    ['MealProgrammes.id = MealDetails.meal_programmes_id']
                )
                ->leftJoin(
                    ['SecurityUsers' => 'security_users'],
                    ['SecurityUsers.id = MealDetails.student_id']
                )
                ->InnerJoin(
                    ['Genders' => 'genders'],
                    ['Genders.id = SecurityUsers.gender_id']
                )
                ->where($conditions)
                ->andWhere(function ($exp) use ($year, $month) {
                    return $exp
                        ->eq("YEAR(MealDetails.date)", $year)
                        ->eq("MONTH(MealDetails.date)", $month);
                })
                ->group([
                    'AcademicPeriods.name',
                    'Institutions.id',
                    'EducationGrades.name',
                    'InstitutionClasses.name',
                    'MealProgrammes.name',
                    'SecurityUsers.id',
                    $monthNameExpr
                ])
                ->order([
                    'AcademicPeriods.name' => 'ASC',
                    'Institutions.name' => 'ASC',
                    'EducationGrades.name' => 'ASC',
                    'InstitutionClasses.name' => 'ASC',
                    'MealProgrammes.name' => 'ASC',
                    'month' => 'ASC'
                ]);
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
    {
        $sheetData = $settings['sheet']['sheetData'];
        $newFields = $this->getMealDetailFields($event, $settings, $fields);

        $year = (int)$sheetData['year'];
        $month = str_pad($sheetData['month'], 2, '0', STR_PAD_LEFT);

        // Get number of days in the month
        $totalDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        for ($day = 1; $day <= $totalDays; ++$day) {
            $date = new DateTime("$year-$month-$day");
            $dayText = $date->format('l'); // e.g. Monday, Tuesday

            // Only include if it’s a working day
            if (in_array($dayText, $this->workingDays)) {
                $dayColumnFormat = $dayText . ' (' . $this->formatDate($date) . ')';
                $newFields[] = [
                    'key'   => 'day_' . $day,
                    'field' => 'day_' . $day,
                    'type'  => 'string',
                    'label' => $dayColumnFormat
                ];
            }
        }

        $fields->exchangeArray($newFields);
    }

    public function getMealDetailFields(EventInterface $event, ArrayObject $settings, $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'academic_period', 
            'field' => 'academic_period',
            'type' => 'string',
            'label' => __('Academic Period')
        ];

        $newFields[] = [
            'key' => 'institution_code', 
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $newFields[] = [
            'key' => 'institution_name', 
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        $newFields[] = [
            'key' => 'education_grade',
            'field' => 'education_grade',
            'type' => 'string',
            'label' => __('Education Grade')
        ];

        $newFields[] = [
            'key' => 'class',
            'field' => 'class',
            'type' => 'string',
            'label' => __('Class')
        ];

        $newFields[] = [
            'key' => 'meal_programme', 
            'field' => 'meal_programme',
            'type' => 'string',
            'label' => __('Meal Programme')
        ];

        $newFields[] = [
            'key' => 'openemis_no', 
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('Student OpenEMIS No')
        ];

        $newFields[] = [
            'key' => 'student_name', 
            'field' => 'student_name',
            'type' => 'string',
            'label' => __('Student Name')
        ];

        $newFields[] = [
            'key' => 'gender_name', 
            'field' => 'gender_name',
            'type' => 'string',
            'label' => __('Gender Name')
        ];

        $newFields[] = [
            'key' => 'month', 
            'field' => 'month',
            'type' => 'string',
            'label' => __('Month')
        ];

        return $newFields;
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

    private function generateSheetsData($requestData)
    {
        $startDate = $requestData->report_start_date;
        $endDate = $requestData->report_end_date;

        $reportStartDate = new DateTime($startDate);
        $reportEndDate = new DateTime($endDate);

        $sheetStartDate = (new DateTime($startDate))->modify('first day of this month');
        $sheetEndDate = (new DateTime($endDate))->modify('first day of next month');
        $interval = DateInterval::createFromDateString('1 month');
        $period   = new DatePeriod($sheetStartDate, $interval, $sheetEndDate);

        $sheets = [];

        foreach ($period as $date) {
            $month = $date->format('n');
            $year = $date->format('Y');
            $amountOfDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);

            $reportStartDay = 1;
            $reportEndDay = $amountOfDays;

            if ($month == $reportStartDate->format('n')) {
                $reportStartDay = $reportStartDate->format('j');
            }
            if ($month == $reportEndDate->format('n')) {
                $reportEndDay = $reportEndDate->format('j');
            }

            $sheets[] = [
                'sheetData' => [
                    'year' => $year,
                    'month' => $month,
                    'startDay' => $reportStartDay,
                    'endDay' => $reportEndDay
                ],
                'name' => $date->format('Y') . ' - ' . $date->format('F'),
                'table' => $this,
                'query' => $this->find()
            ];
        }
        return $sheets;
    }
}
//POCOR-9268 Ends