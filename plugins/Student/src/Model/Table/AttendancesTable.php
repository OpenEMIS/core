<?php

namespace Student\Model\Table;

use Archive\Model\Table\DataManagementConnectionsTable as ArchiveConnections;
use ArrayObject;
use Cake\Event\Event;
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
//POCOR-6658

class AttendancesTable extends ControllerActionTable
{
    private $allDayOptions = [];
    private $selectedDate;
    private $_absenceData = [];

    public function initialize(array $config): void
    {
        $this->setTable('institution_class_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        //$this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' => 'institution_class_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
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

        $AbsenceTypesTable = TableRegistry::get('Institution.AbsenceTypes');
        $this->absenceList = $AbsenceTypesTable->getAbsenceTypeList();
        $this->absenceCodeList = $AbsenceTypesTable->getCodeList();

        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentAttendances' => ['index', 'view']
        ]);
    }
    private function setupTabElements()
    {
        $options['type'] = 'student';
        $tabElements = $this->getAcademicTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Absences');
    }
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        extract($this->getAcademicPeriodOptions());

        // Get selected month from query params (default to current month if not set)
        $selectedMonth = $this->request->getQuery('month') ?: array_key_first($monthOptions);

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

        $this->setIndexQuery($query, $selectedMonth);


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

    public function indexBeforeAction(Event $event, ArrayObject $extra){
        $this->setupFields();
        $this->setupTabElements();
    }
    public function setupFields()
    {
        $this->fields['next_institution_class_id']['visible'] = false;
        $this->fields['institution_student_absence_day_id']['visible'] = false;
        $this->fields['education_grade_id']['visible'] = false;
        $this->field('comment', ['visible' => true, 'attr' => ['label' => __('Comate')]]);
        $this->fields['student_absence_reason_id']['visible'] = true;
        $this->field('institution_class_id', ['visible' => true, 'type' => 'text']);
        $this->field('date', ['visible' => true, 'attr' => ['label' => __('Date')]]);
        $this->field('periods', ['visible' => true]);
        $this->field('subjects', ['visible' => true]);
        $this->setFieldOrder(['date', 'periods', 'subjects', 'institution_class_id', 'absence_type_id']);

    }



    public function onGetInstitutionClassId(Event $event, $entity)
    {
//        return 'a';
        return $entity->institution_class->name;

//        return $result->name;
    }
    public function onGetComment(Event $event, Entity $entity)
    {

        return $entity->comment;
    }

    /**
     * @param Query $query
     * @param string $selectedMonth
     * @return void
     */
    private function setIndexQuery(Query $query, string $selectedMonth): void
    {
        $query
            ->select([
                'student_id' => $this->aliasField('student_id'),
                'date' => 'AttendanceMarkedRecords.date',
                'period' => 'AttendanceMarkedRecords.period',
                'subject_id' => 'AttendanceMarkedRecords.subject_id',
                'comment' => 'Absences.comment',
                'absence_type_id' => 'Absences.absence_type_id',
                'student_absence_reason_id' => 'Absences.student_absence_reason_id',
                $this->aliasField('academic_period_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('education_grade_id')
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
                [
                    'AttendanceMarkedRecords.institution_class_id = ' . $this->aliasField('institution_class_id'),
                    'AttendanceMarkedRecords.date BETWEEN InstitutionStudents.start_date AND IFNULL(InstitutionStudents.end_date, AttendanceMarkedRecords.date)',
                    'DATE_FORMAT(AttendanceMarkedRecords.date, "%Y-%m") = ' => $selectedMonth, // Filters by YYYY-MM
                    ]
            )
            ->leftJoin(
                ['Absences' => 'institution_student_absence_details'],
                [
                    'Absences.student_id = ' . $this->aliasField('student_id'),
                    'Absences.date = AttendanceMarkedRecords.date'
                ]
            );
    }


}
