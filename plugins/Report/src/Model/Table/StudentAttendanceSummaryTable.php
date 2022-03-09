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
use Institution\Model\Table\ClassAttendanceRecordsTable as RecordMarkedType;
use Cake\Log\Log;

class StudentAttendanceSummaryTable extends AppTable
{
    private $workingDays = [];
    private $schoolClosedDays = [];

    const MALE = 'M';
    const FEMALE = 'F';

    public function initialize(array $config)
    {
        $this->table('report_student_attendance_summary');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions',
            'foreignKey' => 'institution_id'
        ]);
        $this->hasMany('InstitutionClassStudents', ['className' => 'Institution.InstitutionClassStudents']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);

        $this->addBehavior('Excel', [
            'excludes' => [
                'class_number',
                'capacity',
                'total_male_students',
                'total_female_students',
                'staff_id',
                'secondary_staff_id',
                'institution_shift_id'
            ],
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.InstitutionSecurity');
        $this->addBehavior('Institution.Calendar');

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
        $academicPeriodId = $requestData->academic_period_id;
        $educationGradeId = $requestData->education_grade_id;
        $institutionId = $requestData->institution_id;
        $institutionTypeId = $requestData->institution_type_id;
        $areaId = $requestData->area_education_id;
        $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->getIdByCode('CURRENT');
        $reportStartDate = new DateTime($requestData->report_start_date);
        $reportEndDate = new DateTime($requestData->report_end_date);
        $startDate = $reportStartDate->format('Y-m-d');
        $endDate = $reportEndDate->format('Y-m-d');

        $conditions = [];

        $institutions = TableRegistry::get('Institution.Institutions');
        $studentAttendanceMarkedRecords = TableRegistry::get('Attendance.StudentAttendanceMarkedRecords');
        $institutionIds = $institutions->find('list', [
                                                    'keyField' => 'id',
                                                    'valueField' => 'id'
                                                ])
                        ->where(['institution_type_id' => $institutionTypeId])
                        ->toArray();
        if ($institutionId != 0) {
            $conditions[$this->aliasField('institution_id')] = $institutionId;
        }

        if (!empty($academicPeriodId)) {
            $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }
        if ($areaId  != -1) {
            $conditions['Institutions.area_id'] = $areaId;
        }
        if ($educationGradeId != -1) {
            $conditions[$this->aliasField('education_grade_id')] = $educationGradeId;
        }
        /*POCOR-6439 starts - added date condition to get data on the bases of selected date range*/
        if (!empty($startDate)) {
            $conditions[$this->aliasField('attendance_date >=')] = $startDate;
        }

        if (!empty($startDate)) {
            $conditions[$this->aliasField('attendance_date <=')] = $endDate;
        }
        /*POCOR-6439 ends*/
        $query
            ->select([
                'name' => $this->aliasField('class_name'),
                'institution_name' => $this->aliasField('institution_name'),
                'academic_period' => $this->aliasField('academic_period_name'),
                'subject' => $this->aliasField('subject_name'),
                'period' => $this->aliasField('period_name'),
                'date' => $this->aliasField('attendance_date'),
                'total_female_students' => $this->aliasField('female_count'),
                'total_male_students' => $this->aliasField('male_count'),
                'total_students' => $this->aliasField('total_count'),
                'total_female_students_present' => $this->aliasField('present_female_count'),
                'total_male_students_present' => $this->aliasField('present_male_count'),
                'total_students_present' => $this->aliasField('present_total_count'),
                'total_female_students_absent' => $this->aliasField('absent_female_count'),
                'total_male_students_absent' => $this->aliasField('absent_male_count'),
                'total_students_absent' => $this->aliasField('absent_total_count'),
                'total_female_students_late' => $this->aliasField('late_female_count'),
                'total_male_students_late' => $this->aliasField('late_male_count'),
                'total_students_late' => $this->aliasField('late_total_count')
            ])
            ->where([$conditions])
            /*POCOR-6439 starts*/
            ->group([
                $this->aliasField('attendance_date'),
                $this->aliasField('period_name'),
                $this->aliasField('class_id')
            ])
            /*POCOR-6439 ends*/
            ->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                return $results->map(function ($row) {
                    if ($row->total_female_students == 0) {
                        $row->total_female_students = '-';
                    }
                    if ($row->total_male_students == 0) {
                        $row->total_male_students = '-';
                    }
                    if ($row->total_students == 0) {
                        $row->total_students = '-';
                    }
                    if ($row->total_female_students_present == 0) {
                        $row->total_female_students_present = '-';
                    }
                    if ($row->total_male_students_present == 0) {
                        $row->total_male_students_present = '-';
                    }
                    if ($row->total_students_present == 0) {
                        $row->total_students_present = '-';
                    }
                    if ($row->total_female_students_absent == 0) {
                        $row->total_female_students_absent = '-';
                    }
                    if ($row->total_male_students_absent == 0) {
                        $row->total_male_students_absent = '-';
                    }
                    if ($row->total_students_absent == 0) {
                        $row->total_students_absent = '-';
                    }
                    if ($row->total_female_students_late == 0) {
                        $row->total_female_students_late = '-';
                    }
                    if ($row->total_male_students_late == 0) {
                        $row->total_male_students_late = '-';
                    }
                    if ($row->total_students_late == 0) {
                        $row->total_students_late = '-';
                    }
                    return $row; 
                });
            });
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $extraField[] = [
            'key' => '',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Name')
        ];

        $extraField[] = [
            'key' => '',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        $extraField[] = [
            'key' => '',
            'field' => 'academic_period',
            'type' => 'string',
            'label' => __('Academic Period')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'subject',
            'type' => 'string',
            'label' => __('Subject')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'period',
            'type' => 'string',
            'label' => __('Period')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'date',
            'type' => 'string',
            'label' => __('Date')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'total_female_students',
            'type' => 'string',
            'label' => __('No. of Female Students')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'total_male_students',
            'type' => 'string',
            'label' => __('No. of Male Students')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'total_students',
            'type' => 'string',
            'label' => __('Total No. Students')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'total_female_students_present',
            'type' => 'string',
            'label' => __('No. of Female Students Present')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'total_male_students_present',
            'type' => 'string',
            'label' => __('No. of Male Students Present')
        ];
        $extraField[] = [
           'key' => '',
            'field' => 'total_students_present',
            'type' => 'string',
            'label' => __('Total No. Students Present')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'total_female_students_absent',
            'type' => 'string',
            'label' => __('No. of Female Students Absent')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'total_male_students_absent',
            'type' => 'string',
            'label' => __('No. of Male Students Absent')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'total_students_absent',
            'type' => 'string',
            'label' => __('Total No. Students Absent')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'total_female_students_late',
            'type' => 'string',
            'label' => __('No. of Female Students Late')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'total_male_students_late',
            'type' => 'string',
            'label' => __('No. of Male Students Late')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'total_students_late',
            'type' => 'string',
            'label' => __('Total No. Students Late')
        ];
        
        $fields->exchangeArray($extraField);
    }

    private function getSchoolClosedDate($requestData)
    {
        $institutionId = [$requestData->institution_id];
        $startDate = new DateTime($requestData->report_start_date);
        $endDate = new DateTime($requestData->report_end_date);
        $closedDates = $this->getInstitutionClosedDates($startDate, $endDate, $institutionId);
        return $closedDates;
    }

    private function generateSheetsData($requestData)
    {
        $institutionId = $requestData->institution_id;
        $academicPeriodId = $requestData->academic_period_id;
        $educationGradeId = $requestData->education_grade_id;
        $institutionTypeId = $requestData->institution_type_id;

        $ids ='';
        $institutions = TableRegistry::get('Institution.Institutions');
        $institutionIds = $institutions->find('list', [
                                                    'keyField' => 'id',
                                                    'valueField' => 'id'
                                                ])
                        ->where(['institution_type_id' => $institutionTypeId])
                        ->toArray();

        $InstitutionGradesTable = TableRegistry::get('Institution.InstitutionGrades');
        $institutionGradeResults = $InstitutionGradesTable->getGradeOptions($institutionId, $academicPeriodId, true);
        $gradeOptions = [];
        
        if ($educationGradeId != -1) {
            if(in_array($educationGradeId, $institutionGradeResults)){
                $gradeOptions[$educationGradeId] = $institutionGradeResults[$educationGradeId];
            }else{
                $EducationGrades = TableRegistry::get('Education.EducationGrades');
                $educationGradesOptions = $EducationGrades
                    ->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'name'
                    ])
                    ->select([
                        'id' => $EducationGrades->aliasField('id'),
                        'name' => $EducationGrades->aliasField('name'),
                        'education_programme_name' => 'EducationProgrammes.name'
                    ])
                    ->contain(['EducationProgrammes'])
                    ->order([
                        'EducationProgrammes.order' => 'ASC',
                        $EducationGrades->aliasField('name') => 'ASC'
                    ])
                    ->toArray();

                $gradeOptions[$educationGradeId] = $educationGradesOptions[$educationGradeId];
            }
        } else {
            $gradeOptions = $institutionGradeResults;
        }

        $sheets = [];
        foreach ($gradeOptions as $gradeId => $gradeName) {
            $where = [];
            if ($institutionId != 0) {
                $where[$this->aliasField('institution_id')] = $institutionId;
            }
            $query = $this
                ->find()
                ->where([
                    $where,
                    $this->aliasField('academic_period_id') => $academicPeriodId
                ])
                ->matching('EducationGrades', function ($q) use ($gradeId) {
                    return $q->where([
                        'EducationGrades.id' => $gradeId
                    ]);
                });

            $sheets[] = [
                'sheetData' => [
                    'education_grade_id' => $gradeId
                ],
                'name' => preg_replace("/\([^)]+\)/","",$gradeName),
                'table' => $this,
                'query' => $query,
                'orientation' => 'landscape'
            ];
        }
        
        return $sheets;
    }
}
