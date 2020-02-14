<?php
namespace Report\Model\Table;

use ArrayObject;
use DateInterval;
use DatePeriod;
use DateTime;
use App\Model\Table\AppTable;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Institution\Model\Table\ClassAttendanceRecordsTable as MarkedType;

class ClassAttendanceNotMarkedRecordsTable extends AppTable
{
    private $workingDays = [];
    private $schoolClosedDays = [];

    public function initialize(array $config)
    {
        $this->table('institution_classes');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('InstitutionShifts', ['className' => 'Institution.InstitutionShifts', 'foreignKey' => 'institution_shift_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);

        $this->hasMany('ClassAttendanceRecords', ['className' => 'Institution.ClassAttendanceRecords', 'foreignKey' => 'institution_class_id']);
        $this->hasMany('ClassesSecondaryStaff', ['className' => 'Institution.InstitutionClassesSecondaryStaff', 'saveStrategy' => 'replace', 'foreignKey' => 'institution_class_id']);

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
            'autoFields' => false,
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

        $query
            ->find('byGrades', [
                'education_grade_id' => $educationGradesId,
            ])
            ->contain([
                'Institutions' => [
                    'fields' => [
                        'Institutions.id',
                        'Institutions.code',
                        'Institutions.name'
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
                        'AreaAdministratives.code',
                        'AreaAdministratives.name'
                    ]
                ],
                'Institutions.Types' => [
                    'fields' => [
                        'Types.name'
                    ]
                ],
                'InstitutionShifts.ShiftOptions' => [
                    'fields' => [
                        'ShiftOptions.name'
                    ]
                ],
                'EducationGrades' => [
                    'fields' => [
                        'InstitutionClassGrades.institution_class_id',
                        'EducationGrades.id',
                        'EducationGrades.code',
                        'EducationGrades.name'
                    ]
                ],
                'AcademicPeriods' => [
                    'fields' => [
                        'AcademicPeriods.name'
                    ]
                ],
                'Staff' => [
                    'fields' => [
                        'Staff.id',
                        'Staff.first_name',
                        'Staff.middle_name',
                        'Staff.third_name',
                        'Staff.last_name',
                        'Staff.preferred_name'
                    ]
                ],
                'ClassAttendanceRecords' => function ($q) use ($academicPeriodId, $year, $month) {
                    return $q
                        ->where([
                            'ClassAttendanceRecords.academic_period_id' => $academicPeriodId,
                            'ClassAttendanceRecords.year' => $year,
                            'ClassAttendanceRecords.month' => $month
                        ]);
                }
            ])
            ->select([
                $this->aliasField('id'),
                $this->aliasField('name'),
                'institution_id' => 'Institutions.id',
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'institution_type' => 'Types.name',
                'academic_period_name' => 'AcademicPeriods.name',
                'staff_id' => 'Staff.id',
                'shift_name' => 'ShiftOptions.name',
                'education_stage_order' => $query->func()->min('EducationStages.order')
            ])
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId
            ])
            ->group([
                $this->aliasField('id')
            ])
            ->order([
                'institution_name' => 'ASC',
                'education_stage_order' => 'ASC',
                $this->aliasField('name') => 'ASC'
            ])
            ->formatResults(function (ResultSetInterface $results) use ($schoolClosedDays, $year, $month, $startDay, $endDay) {
                return $results->map(function ($row) use ($schoolClosedDays, $year, $month, $startDay, $endDay) {
                    $institutionId = $row->institution_id;
                    $mark= 0;
                    $unmark= 0;
                    if (!empty($row->class_attendance_records)) {
                        $attendanceRecord = $row->class_attendance_records[0];
                    }

                    for ($day = $startDay; $day <= $endDay; ++$day) {
                        $dayColumn = 'day_' . $day;
                        $dayFormat = (new DateTime($year . '-' . $month . '-' . $day))->format('Y-m-d');
                        $dayText = (new DateTime($year . '-' . $month . '-' . $day))->format('l');

                        if(in_array($dayText, $this->workingDays)){
                            if (isset($schoolClosedDays[$institutionId]) &&
                                isset($schoolClosedDays[$institutionId][$dayFormat]) &&
                                $schoolClosedDays[$institutionId][$dayFormat] == 0) {
                                    $status = __('School Closed');
                            } elseif (isset($attendanceRecord)) {
                                if ($attendanceRecord[$dayColumn] == MarkedType::MARKED) {
                                    $status = __('Marked');
                                    $mark++;
                                } elseif ($attendanceRecord[$dayColumn] == MarkedType::PARTIAL_MARKED) {
                                    $status = __('Partial Marked');
                                    $unmark++;
                                } else { // MarkedType::NOT_MARKED
                                    $status = __('Not Marked');
                                    $unmark++;
                                }
                            } else {
                                // no school closed and no attendances record found - default to NOT_MARKED
                                $status = __('Not Marked');
                            }
                        }

                        $row->total_mark = $mark;
                        $row->total_unmark = $unmark;
                        $row->{$dayColumn} = $status;
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

        return implode(', ', $classGrades);
    }

    public function findByGrades(Query $query, array $options)
    {
        $sortable = array_key_exists('sort', $options) ? $options['sort'] : false;

        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $EducationStages = TableRegistry::get('Education.EducationStages');

        $gradeId = $options['education_grade_id'];
        $join = [
            'table' => 'institution_class_grades',
            'alias' => 'InstitutionClassGrades',
            'conditions' => [
                'InstitutionClassGrades.institution_class_id = ' . $this->aliasField('id')
            ]
        ];

        if ($gradeId > 0) {
            $join['conditions']['InstitutionClassGrades.education_grade_id'] = $gradeId;
        }

        $query = $query
            ->join([$join])

            ->innerJoin(
                [$EducationGrades->alias() => $EducationGrades->table()],
                [$EducationGrades->aliasField('id = ') . 'InstitutionClassGrades.education_grade_id']
            )
            ->innerJoin(
                [$EducationStages->alias() => $EducationStages->table()],
                [$EducationStages->aliasField('id = ') . 'EducationGrades.education_stage_id']
            );

        return $query;
    }

    private function getClassFields()
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'InstitutionClasses.academic_period_id',
            'field' => 'academic_period_name',
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
            'key' => 'InstitutionClasses.Marked',
            'field' => 'total_mark',
            'type' => 'string',
            'label' => 'Marked'
        ];

        $newFields[] = [
            'key' => 'InstitutionClasses.Unmarked',
            'field' => 'total_unmark',
            'type' => 'string',
            'label' => 'Unmarked'
        ];                

        $newFields[] = [
            'key' => 'InstitutionClasses.staff_id',
            'field' => 'staff_id',
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
            $query->find('byAccess', [
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
