<?php

namespace Student\Model\Table;

use Archive\Model\Table\DataManagementConnectionsTable as ArchiveConnections;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\I18n\Time;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ResultSetInterface;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Locator\TableLocator;
use App\Model\Table\ControllerActionTable;
use Cake\I18n\FrozenTime;
use Cake\I18n\I18n;
use Cake\Utility\Text;

//POCOR-6658

class AttendancesTable extends ControllerActionTable
{
    private $absenceList = [];
    private $absenceReasonList = [];

    public function initialize(array $config): void
    {
        $this->setTable('institution_class_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        //$this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' => 'institution_class_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
//        $this->belongsTo('NextInstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' => 'next_institution_class_id']);
//        $this->hasMany('InstitutionClassGrades', ['className' => 'Institution.InstitutionClassGrades']);
        //$this->hasOne('StudentAbsencesPeriodDetails', ['className' => 'Institution.StudentAbsencesPeriodDetails']);institution_class_id

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('delete', false);
        $this->toggle('view', false);
        $this->toggle('remove', false);
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['Attendances' =>['student_id','institution_id','academic_period_id','institution_class_id','date','period','subject_id']
            ]
        ]);


        $this->absenceList = $this->getTypeList('Institution.AbsenceTypes');
        $this->absenceReasonList = $this->getTypeList('Institution.StudentAbsenceReasons');
        $this->addBehavior('Excel', [
            'excludes' => ['id'],
            'autoFields' => false
        ]);
    }

    public function getTypeList($tableAlias) {
        $TypesTable = TableRegistry::getTableLocator()->get($tableAlias);
        $result = $TypesTable
            ->find('list')
            ->toArray();
        foreach ($result as $key => $value) {
            $result[$key] = __($value);
        }
        return $result;
    }
    private function setupTabElements()
    {
        $options['type'] = 'student';
        $tabElements = $this->getAcademicTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Absences');
    }
    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        extract($this->getAcademicPeriodOptions());

        // Get selected month from query params (default to current month if not set)
        $selectedMonth = $this->request->getQuery('month') ?? null;

        // Store selected period and month in the request
        $this->request = $this->request->withQueryParams([
            'academic_period' => $selectedPeriod,
            'academic_period_id' => $selectedPeriod,
            'month' => $selectedMonth,
        ]);

        // Pass values to the view
        $this->advancedSelectOptions($academicPeriodList, $selectedPeriod);
        $queryString = $this->controller->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $this->controller->set(compact('academicPeriodList', 'selectedPeriod', 'monthOptions', 'selectedMonth', 'encodedQueryString'));

        $extra['elements']['controls'] = ['name' => 'Student.Attendances/controls', 'order' => 1];

        $query = $this->setIndexQuery($query, $selectedMonth, $selectedPeriod);


//        dd($query);
    }



    /**
     * Generate a list of months based on the selected academic period, formatted as "YYYY-MM".
     *
     * @param int $selectedPeriod Academic period ID.
     * @return array<string, string> List of months formatted as "YYYY-MM".
     */
    private function getMonthOptionsForPeriod(int $selectedPeriod): array
    {
        $monthOptions = ['-1' => '-- ' . __('Select Month') . ' --'];

        // Retrieve the academic period
        $AcademicPeriods = $this->AcademicPeriods;
        $academicPeriod = $AcademicPeriods->get($selectedPeriod, ['fields' => ['start_date', 'end_date']]);

        if (!$academicPeriod) {
            return $monthOptions;
        }

        // Get start and end dates
        $startDate = FrozenTime::parse($academicPeriod->start_date);
        $endDate = FrozenTime::parse($academicPeriod->end_date);

        // Loop through months between start_date and end_date
        while ($startDate <= $endDate) {
            $formattedMonth = $startDate->format('Y-m'); // "YYYY-MM"
            $monthName = $startDate->i18nFormat('MMMM Y'); // "Month Name Year"

            $monthOptions[$formattedMonth] = $monthName;
            $startDate = $startDate->modify('+1 month'); // Move to next month
        }

        return $monthOptions;
    }



    /**
     * Retrieve available academic period options along with month options.
     *
     * @return array<string, mixed> Array containing academic period list, selected period, and month options.
     */
    private function getAcademicPeriodOptions(): array
    {

        $academicPeriodList = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedPeriod = $this->request->getQuery('academic_period_id') ?: $this->AcademicPeriods->getCurrent();

        $monthOptions = $this->getMonthOptionsForPeriod($selectedPeriod);

        return compact('monthOptions', 'academicPeriodList', 'selectedPeriod');
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra){
        $this->setupFields();
        $this->setupTabElements();
    }
    public function setupFields()
    {
        $this->fields['next_institution_class_id']['visible'] = false;
        $this->fields['institution_student_absence_day_id']['visible'] = false;
        $this->fields['education_grade_id']['visible'] = false;
        $this->fields['student_status_id']['visible'] = false;
        $this->field('date', ['visible' => true, 'attr' => ['label' => __('Date')]]);
        $this->field('period_name', ['visible' => true, 'attr' => ['label' => __('Period')]]);
        $this->field('subject_name', ['visible' => true, 'attr' => ['label' => __('Subject')]]);
        $this->field('absence_type', ['visible' => true, 'attr' => ['label' => __('Absence Type')]]);
        $this->field('absence_reason', ['visible' => true, 'attr' => ['label' => __('Absence Reason')]]);
        $this->field('comment', ['visible' => true, 'attr' => ['label' => __('Comments')]]);

        $this->field('institution_class_id', ['visible' => true, 'type' => 'text']);
        $this->setFieldOrder(['academic_period_id',
            'institution_id',
            'institution_class_id',
            'date',
            'period_name',
            'subject_name',
            'absence_type',
            'absence_reason',
            'comment']);

    }



    public function onGetComment(EventInterface $event, Entity $entity)
    {
//        dd($entity);
        return $this->getShortComment($entity->comment);
    }

    public function onGetPeriodName(EventInterface $event, Entity $entity)
    {
        // POCOR-9025 start
        if(is_numeric($entity->subject_id) && $entity->subject_id > 0){
            return '';
        }
        // POCOR-9025 end
        return __('Period {0}', $entity->period);

//        return $this->getShortComment($entity->comment);
    }

    public function onExcelGetPeriodName(EventInterface $event, Entity $entity)
    {
        return __('Period {0}', $entity->period);
//        return $this->getShortComment($entity->comment);
    }


    /**
     * Truncate a comment to a maximum length without breaking words,
     * supporting international characters (UTF-8).
     *
     * @param string|null $comment The original comment text.
     * @param int $maxLength Maximum allowed length (default: 250).
     * @return string Truncated comment with "..." if necessary.
     */
    private function getShortComment(?string $comment, int $maxLength = 250): string
    {
        if (empty($comment)) {
            return ''; // Return empty string if no comment
        }

        // Use CakePHP's Text::truncate() to ensure words are not cut in half
        return Text::truncate($comment, $maxLength, [
            'ellipsis' => '...',   // Add "..." if truncated
            'exact' => false,      // Ensure we don't break words
            'html' => false        // No HTML processing, treat as plain text
        ]);
    }


    /**
     * @param Query $query
     * @param string $selectedMonth
     * @return Query $query
     */
    private function setIndexQuery(Query $query, string $selectedMonth = null, $selectedYear = null): Query
    {
        $Types = TableRegistry::getTableLocator()->get('Institution.AbsenceTypes');
        if(!$selectedYear){
            $selectedYear = $this->AcademicPeriods->getCurrent();
        }
        $absence_type_name = $Types->aliasField('name');

        if (!$selectedMonth || $selectedMonth < 1) {
            $joinConditions = [
                'AttendanceMarkedRecords.institution_class_id = ' . $this->aliasField('institution_class_id'),
                'AttendanceMarkedRecords.date BETWEEN InstitutionStudents.start_date AND IFNULL(InstitutionStudents.end_date, AttendanceMarkedRecords.date)',
                'AttendanceMarkedRecords.academic_period_id = ' . $selectedYear
            ];
        } else {
            $joinConditions = [
                'AttendanceMarkedRecords.institution_class_id = ' . $this->aliasField('institution_class_id'),
                'AttendanceMarkedRecords.date BETWEEN InstitutionStudents.start_date AND IFNULL(InstitutionStudents.end_date, AttendanceMarkedRecords.date)',
                'DATE_FORMAT(AttendanceMarkedRecords.date, "%Y-%m") = ' => $selectedMonth, // Filters by YYYY-MM
            ];
        }
        $query
            ->select([
                'student_id' => $this->aliasField('student_id'),
                'date' => 'AttendanceMarkedRecords.date',
                'period' => 'AttendanceMarkedRecords.period',
                'subject_id' => 'AttendanceMarkedRecords.subject_id',
                'comment' => 'Absences.comment',
                'absence_type_id' => 'Absences.absence_type_id',
                'absence_type' => "COALESCE($absence_type_name, 'Present')",
                'student_absence_reason_id' => 'Absences.student_absence_reason_id',
                'absence_reason' => 'AbsenceReasons.name',
                'period_name' => 'AttendanceMarkedRecords.period',
                'subject_name' => 'Subjects.name',
                $this->aliasField('academic_period_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('institution_class_id')
            ])
            ->innerJoin(
                ['InstitutionStudents' => 'institution_students'],
                [
                    'InstitutionStudents.student_id = ' . $this->aliasField('student_id'),
                    'InstitutionStudents.institution_id = ' . $this->aliasField('institution_id'),
                    'InstitutionStudents.student_status_id = ' . $this->aliasField('student_status_id'),
                    'InstitutionStudents.education_grade_id = ' . $this->aliasField('education_grade_id'),
                ]
            )
            ->innerJoin(
                ['AttendanceMarkedRecords' => 'student_attendance_marked_records'],
                $joinConditions
            )
            ->leftJoin(
                ['Absences' => 'institution_student_absence_details'],
                [
                    'Absences.student_id = ' . $this->aliasField('student_id'),
                    'Absences.date = AttendanceMarkedRecords.date', // POCOR-9025
                    'Absences.period = AttendanceMarkedRecords.period', // POCOR-9025
                    'Absences.subject_id = AttendanceMarkedRecords.subject_id', // POCOR-9025
                ]
            )
            ->leftJoin(
                ['AbsenceReasons' => 'student_absence_reasons'],
                [
                    'Absences.student_absence_reason_id = AbsenceReasons.id'
                ]
            )
//            ->leftJoin(
//                ['Periods' => 'student_attendance_per_day_periods'],
//                [
//                    'AttendanceMarkedRecords.period = Periods.id'
//                ]
//            )
            ->leftJoin(
                ['Subjects' => 'institution_subjects'],
                [
                    'AttendanceMarkedRecords.subject_id = Subjects.id' // POCOR-9025
                ]
            )
            ->leftJoin(
                [$Types->getAlias() => $Types->getTable()],
                [
                    'Absences.absence_type_id = ' . $Types->aliasField('id')
                ]
            )->group([ // POCOR-9025 start
                'AttendanceMarkedRecords.date',
                'Absences.absence_type_id',
                'Absences.student_absence_reason_id',
                'AttendanceMarkedRecords.period',
                'Absences.subject_id'
            ]); // POCOR-9025 end
        ;
        return $query;
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $selectedMonth = $this->request->getQuery('month') ?? null;
        $selectedPeriod = $this->request->getQuery('academic_period_id') ?? null;
        $student_id = $this->getQueryString('student_id');

        if (!$selectedMonth) {
            $event->stopPropagation();
        }
        $query = $this->setIndexQuery($query, $selectedMonth, $selectedPeriod);
        $query->contain(['Users','Institutions','AcademicPeriods','InstitutionClasses']);
        $query->select(['Users.first_name',
            'Users.last_name',
            'Institutions.name',
            'AcademicPeriods.name',
            'InstitutionClasses.name']);
        $query->where([$this->aliasField('student_id = ') . $student_id]);
//        dd($query);
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
    {

        $extraField = [
            [
                "key" => "Attendances.student_id",
                "field" => "student_id",
                "type" => "integer",
                "label" => __("Student")
            ],
            [
                "key" => "Attendances.academic_period_id",
                "field" => "academic_period_id",
                "type" => "integer",
                "label" => __("Academic Period")
            ],
            [
                "key" => "AttendanceMarkedRecords.date",
                "field" => "date",
                "type" => "string",
                "label" => __("Date")
            ],
            [
                "key" => "Attendances.institution_id",
                "field" => "institution_id",
                "type" => "integer",
                "label" => __("Institution")
            ],
            [
                "key" => "Attendances.institution_class_id",
                "field" => "institution_class_id",
                "type" => "integer",
                "label" => __("Institution Class")
            ],
            [
                "key" => "",
                "field" => "period_name",
                "type" => "integer",
                "label" => __("Period")
            ],
            [
                "key" => "",
                "field" => "subject",
                "type" => "integer",
                "label" => __("Subject"),
            ],
            [
                "key" => "",
                "field" => "absence_type",
                "type" => "string",
                "label" => __("Absence Type")
            ],
            [
                "key" => "",
                "field" => "absence_reason",
                "type" => "string",
                "label" => __("Absence Reason")
            ],
            [
                "key" => "Absences.comment",
                "field" => "comment",
                "type" => "string",
                "label" => __("Comment"),
            ],
        ];

        $fields->exchangeArray($extraField);
    }
}
