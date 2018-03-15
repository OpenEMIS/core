<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\Log\Log;
use DateTime;
use Cake\I18n\Date;
use DateInterval;
use DatePeriod;
use Cake\Datasource\ResultSetInterface;

class ClassAttendanceNotMarkedRecordsTable extends AppTable
{
    private $schoolHoliday = [];
    private $workingDays = [];
    private $schoolClosedDays = [];

    public function initialize(array $config)
    {
        $this->table('institution_classes');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('SecondaryStaff', ['className' => 'User.Users', 'foreignKey' => 'secondary_staff_id']);
        $this->belongsTo('InstitutionShifts', ['className' => 'Institution.InstitutionShifts', 'foreignKey' => 'institution_shift_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);

        $this->hasMany('ClassAttendanceRecords', ['className' => 'Institution.ClassAttendanceRecords', 'foreignKey' => 'institution_class_id']);

        $this->belongsToMany('EducationGrades', [
            'className' => 'Education.EducationGrades',
            'through' => 'Institution.InstitutionClassGrades',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'education_grade_id',
            'dependent' => true
        ]);

        $this->addBehavior('Institution.Calendar');
        $this->addBehavior('Report.InstitutionSecurity');
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Excel', [
            // 'autoFields' => true
            'excludes' => [
                'class_number'
            ]
        ]);

        $AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $this->workingDays = $AcademicPeriodTable->getWorkingDaysOfWeek();
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $requestData = json_decode($settings['process']['params']);
        $sheetsData = $this->generateSheetsData($requestData);
        $sheets->exchangeArray($sheetsData);

        $this->schoolClosedDays = $this->getSchoolClosedDate($requestData);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $sheetData = $settings['sheet']['sheetData'];

        $academicPeriodId = $requestData->academic_period_id;
        $educationGradesId = $requestData->education_grade_id;
        $year = $sheetData['year'];
        $month = $sheetData['month'];
        $startDay = $sheetData['startDay'];
        $endDay = $sheetData['endDay'];
        $schoolClosedDays = $this->schoolClosedDays;

        // select * from institution_classes
        // left join class_attendance_records
        // on class_attendance_records.institution_class_id = institution_classes.id
        // and class_attendance_records.academic_period_id = academic_period_id
        // and class_attendance_records.year = '2018'
        // and class_attendance_records.month = '2'
        // }])
        
        $query
            ->contain([
                'Institutions.Areas',
                'Institutions.AreaAdministratives',
                'Institutions.Types',
                'EducationGrades',
                'InstitutionShifts.ShiftOptions',
                'AcademicPeriods'
            ])
            ->contain([ 'ClassAttendanceRecords' => function ($q) use ($academicPeriodId, $year, $month) {
                return $q
                    ->where([
                        'ClassAttendanceRecords.academic_period_id' => $academicPeriodId,
                        'ClassAttendanceRecords.year' => $year,
                        'ClassAttendanceRecords.month' => $month
                    ]);
            }])
            ->select([
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'area_name' => 'Areas.name',
                'area_code' => 'Areas.code',
                'institution_type' => 'Types.name',
                'shift_name' => 'ShiftOptions.name',
                'area_administrative_code' => 'AreaAdministratives.code',
                'area_administrative_name' => 'AreaAdministratives.name'
            ])
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId
            ])
            ->order([
                'AcademicPeriods.order',
                'Institutions.code'
            ])
            ->formatResults(function (ResultSetInterface $results) use ($schoolClosedDays, $year, $month, $startDay, $endDay) {
                return $results->map(function ($row) use ($schoolClosedDays, $year, $month, $startDay, $endDay) {
                    $institutionId = $row->institution_id;
                    if (!empty($row['class_attendance_records'])) {
                        $attendanceRecord = $row['class_attendance_records'][0];
                    }

                    for ($day = $startDay; $day <= $endDay; ++$day) {
                        $dayColumn = 'day_' . $day;
                        $dayFormat = (new DateTime($year . '-' . $month . '-' . $day))->format('Y-m-d');
                        $status = __('Not Marked');

                        if (isset($schoolClosedDays[$institutionId]) &&
                            isset($schoolClosedDays[$institutionId][$dayFormat]) &&
                            $schoolClosedDays[$institutionId][$dayFormat] == 0) {
                            $status = __('School Closed');
                        } else {
                            if (isset($attendanceRecord) && $attendanceRecord[$dayColumn] == 1) {
                                $status = __('Marked');
                            }
                        }

                        $row[$dayColumn] = $status;
                    }

                    return $row;
                });
            })
        ;
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $sheetData = $settings['sheet']['sheetData'];
        $newFields = $this->getClassFields();
        
        $year = $sheetData['year'];
        $month = $sheetData['month'];
        $startDay = $sheetData['startDay'];
        $endDay = $sheetData['endDay'];

        for ($day = $startDay; $day <= $endDay; ++$day) {
            $date = new DateTime($year . '-' . $month . '-' . $day);
            $dayText = $date->format('l');

            if (in_array($dayText, $this->workingDays)) {
                $dayColumnFormat = $dayText . ' (' . $this->formatDate($date) . ')';
                $newFields[] = [
                    'key' => 'ClassAttendanceRecords.day_' . $day,
                    'field' => 'day_' . $day,
                    'type' => 'string',
                    'label' => $dayColumnFormat
                ];
            }
        }


        $fields->exchangeArray($newFields);
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

    private function getClassFields()
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'InstitutionClasses.academic_period_id',
            'field' => 'academic_period_id',
            'type' => 'integer',
            'label' => ''
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

        $newFields[] = [
            'key' => 'AreaAdministratives.code',
            'field' => 'area_administrative_code',
            'type' => 'string',
            'label' => __('Area Administrative Code')
        ];

        $newFields[] = [
            'key' => 'AreaAdministratives.name',
            'field' => 'area_administrative_name',
            'type' => 'string',
            'label' => __('Area Administrative')
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
            'key' => 'InstitutionClasses.staff_id',
            'field' => 'staff_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionClasses.secondary_staff_id',
            'field' => 'secondary_staff_id',
            'type' => 'integer',
            'label' => ''
        ];

        return $newFields;
    }

    private function getSchoolClosedDate($requestData)
    {
        $superAdmin = $requestData->super_admin;
        $userId = $requestData->user_id;
        $startDate = new DateTime($requestData->report_start_date);
        $endDate = new DateTime($requestData->report_end_date);

        $query = $this->find();

        if (!$superAdmin) {
            $query->find('ByAccess', [
                'user_id' => $userId,
                'institution_field_alias' => $this->aliasField($this->association('Institutions')->foreignKey())
            ]);
        }
            
        $institutionList = $query
            ->group('institution_id')
            ->extract('institution_id')
            ->toArray();

        return $this->getInstitutionClosedDates($startDate, $endDate, $institutionList);
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
            } elseif ($month == $reportEndDate->format('n')) {
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
 
    // public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    // {
    //     $requestData = json_decode($settings['process']['params']);
    //     $academicPeriodId = $requestData->academic_period_id;

    //     if (!is_null($academicPeriodId) && $academicPeriodId != 0) {
    //         $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
    //         $periodEntity = $AcademicPeriods->get($academicPeriodId);

    //         $startDate = $periodEntity->start_date->format('Y-m-d');
    //         $endDate = $periodEntity->end_date->format('Y-m-d');
    //     }

    //     $query
    //         // ->select([
    //         //     'institution_name' => 'Institutions.name',
    //         //     'institution_code' => 'Institutions.code',
    //         //     'academic_period' => 'AcademicPeriods.name',
    //         //     'institution_class_name' => 'InstitutionClasses.name',
    //         //     'homeroom_teacher_first_name' => 'Staff.first_name',
    //         //     'homeroom_teacher_middle_name' => 'Staff.middle_name',
    //         //     'homeroom_teacher_third_name' => 'Staff.third_name',
    //         //     'homeroom_teacher_last_name' => 'Staff.last_name',
    //         //     'secondary_homeroom_teacher_first_name' => 'SecondaryStaff.first_name',
    //         //     'secondary_homeroom_teacher_middle_name' => 'SecondaryStaff.middle_name',
    //         //     'secondary_homeroom_teacher_third_name' => 'SecondaryStaff.third_name',
    //         //     'secondary_homeroom_teacher_last_name' => 'SecondaryStaff.last_name',
    //         // ])
    //         // ->contain([
    //         //     'InstitutionClasses.Institutions',
    //         //     'InstitutionClasses.Staff',
    //         //     'InstitutionClasses.SecondaryStaff',
    //         //     'AcademicPeriods',
    //         // ])
    //         ->contain(['InstitutionClasses' => function ($q) {
    //             return $q->select([
    //                 'InstitutionClasses.id',
    //                 'InstitutionClasses.name'
    //             ]);
    //         }])
    //         ->contain(['InstitutionClasses.Institutions' => function ($q) {
    //             return $q->select([
    //                 'Institutions.name',
    //                 'Institutions.code'
    //             ]);
    //         }])
    //         ->contain(['InstitutionClasses.Staff' => function ($q) {
    //             return $q->select([
    //                 'Staff.first_name',
    //                 'Staff.middle_name',
    //                 'Staff.third_name',
    //                 'Staff.last_name'
    //             ]);
    //         }])
    //         ->contain(['InstitutionClasses.SecondaryStaff' => function ($q) {
    //             return $q->select([
    //                 'SecondaryStaff.first_name',
    //                 'SecondaryStaff.middle_name',
    //                 'SecondaryStaff.third_name',
    //                 'SecondaryStaff.last_name'
    //             ]);
    //         }])
    //         ->contain(['AcademicPeriods' => function ($q) {
    //             return $q->select(['AcademicPeriods.name']);
    //         }])
    //         ->contain(['InstitutionClasses.EducationGrades' => function ($q) {
    //             return $q->select(['EducationGrades.name']);
    //         }]);

    //     // $result = $query->toArray();
    // }
}
