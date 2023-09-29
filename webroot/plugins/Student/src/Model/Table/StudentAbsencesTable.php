<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Validation\Validator;
use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\ORM\Query; 
use Cake\ORM\TableRegistry;

use App\Model\Table\ControllerActionTable;

class StudentAbsencesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_student_absence_details');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        // $this->belongsTo('StudentAbsenceReasons', ['className' => 'Institution.StudentAbsenceReasons']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AbsenceTypes', ['className' => 'Institution.AbsenceTypes', 'foreignKey' => 'absence_type_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades', 'foreignKey' => 'education_grade_id']);
        $this->hasMany('InstitutionSubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents', 'foreignKey' => 'education_grade_id']);

    }


    public function beforeAction(Event $event, ArrayObject $extra)
    {  
        $this->fields['institution_student_absence_day_id']['visible'] = false;
        $this->fields['education_grade_id']['visible'] = false;
        $this->fields['comment']['visible'] = false;
        $this->fields['student_absence_reason_id']['visible'] = false;
        $this->field('institution_class_id', ['visible' => true]);
        $this->field('Date', ['visible' => true, 'attr' => ['label' => __('Date')]]);
        $this->field('class', ['visible' => true]);
        $this->field('periods', ['visible' => true]);
        $this->field('subjects', ['visible' => true]);
    
        $this->setFieldOrder(['Date', 'periods', 'subjects', 'class', 'absence_type_id']);
    }

    public function onGetDate(Event $event, Entity $entity)
    {  
        return $this->Date = date_format($entity->date, 'F d, Y');
    }

    public function onGetPeriods(Event $event, Entity $entity)
    {  
        $StudentAttendancePerDayPeriods = TableRegistry::get('Attendance.StudentAttendancePerDayPeriods');
        $result = $StudentAttendancePerDayPeriods
            ->find()
            ->select(['name'])
            ->where(['id' => $entity->period])
            ->first();

        return $this->periods = $result->name;
    }

    public function onGetSubjects(Event $event, Entity $entity)
    {   
        $InstitutionSubjects = TableRegistry::get('institution_subjects');
        $result = $InstitutionSubjects
            ->find()
            ->select(['name'])
            ->where(['id' => $entity->subjects])
            ->first();
        return $this->subjects = $result->name;
    }

    public function onGetClass(Event $event, Entity $entity)
    {   
        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $result = $InstitutionClasses
            ->find()
            ->select(['name'])
            ->where(['id' => $entity->institution_class_id])
            ->first();
        return $this->class = $result->name;
    }

    private function setupTabElements()
    {
        $options['type'] = 'student';
        $tabElements = $this->controller->getAcademicTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function indexAfterAction(Event $event, $data)
    {
        $this->setupTabElements();
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');

        $institutionId = $this->Session->read('Institution.Institutions.id');
        if ($this->request->query('user_id') !== null) {
            $staffId = $this->request->query('user_id');
            $this->Session->write('Staff.Staff.id', $staffId);
        } else {
            $staffId = $this->Session->read('Staff.Staff.id');
        }

        $academicPeriodList = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        if (empty($this->request->query['academic_period'])) {
            $this->request->query['academic_period'] = $AcademicPeriod->getCurrent();
        }
        $selectedPeriod = $this->request->query['academic_period'];

        $this->request->query['academic_period'] = $selectedPeriod;
        $this->advancedSelectOptions($academicPeriodList, $selectedPeriod);


        // $selectedPeriod = $this->request->query['academic_period_id'];
        $dateFrom = $this->request->query['dateFrom'];
        $dateTo = $this->request->query['dateTo'];
        $selectedSubject = $this->request->query['education_subject_id'];

        $this->request->query['academic_period_id'] = $selectedPeriod;
        $this->request->query['dateFrom'] = $dateFrom;
        $this->request->query['dateTo'] = $dateTo;

        if ($selectedPeriod != 0) {
            $this->controller->set(compact('academicPeriodList', 'selectedPeriod'));

            // Setup dateFrom options
            $dateFrom = $AcademicPeriod->getDateFrom($selectedPeriod);
            $dateFromOptions = [];
            $currentdateFrom = null;


            $dateTo = $AcademicPeriod->getDateFrom($selectedPeriod);
            $dateToOptions = [];
            $currentdateTo = null;

            foreach ($dateFrom as $index => $dates) {
                $dateFromOptions[$index] = sprintf($this->formatDate($dates[0]));
            }

            foreach ($dateTo as $index => $dates) {
                $dateToOptions[$index] = sprintf($this->formatDate($dates[0]));
            }

            $dateFromOptions = ['-1' => __('Select Date from')] + $dateFromOptions;

            $dateToOptions = ['-1' => __('Select Date To')] + $dateToOptions;
            /*POCOR-6267 starts*/
            if (!is_null($institutionId)) {
                $conditions = [
                    $this->aliasField('academic_period_id') => $selectedPeriod,
                    $this->aliasField('institution_id') => $institutionId,
                ];
            } else {
                $conditions = [
                    $this->aliasField('academic_period_id') => $selectedPeriod
                ];
            }
            /*POCOR-6267 ends*/
            if(!empty($this->request->query('dateFrom')) && $this->request->query('dateFrom') != '-1'){
                $academicPeriodObj = $AcademicPeriod->get($selectedPeriod);
                $startYear = $academicPeriodObj->start_year;
                $endYear = $academicPeriodObj->end_year;
                
                if (date("Y") >= $startYear && date("Y") <= $endYear && !is_null($currentdateFrom)) {
                    $selectedDateFrom = !is_null($this->request->query('dateFrom')) ? $this->request->query('dateFrom') : $currentdateFrom;
                } else {
                    $selectedDateFrom = $this->queryString('dateFrom', $dateFromOptions);
                }
                if (date("Y") >= $startYear && date("Y") <= $endYear) {
                    $selectedDateTo = !is_null($this->request->query('dateTo')) ? $this->request->query('dateTo') : $currentdateTo;
                } else {
                    $selectedDateTo = $this->queryString('dateTo', $dateToOptions);
                }
                $weekStartDate = $dateFrom[$selectedDateFrom][0];
                $weekEndDate = $dateFrom[$selectedDateTo][0];
                $startDate = $weekStartDate;
                $endDate = $weekEndDate;
                $selectedFormatStartDate = date_format($startDate, 'Y-m-d');
                $selectedFormatEndDate = date_format($endDate, 'Y-m-d');
                if(empty($endDate) || !isset($endDate)){
                    $dateConditions = [
                        $this->aliasField('date >=') => $selectedFormatStartDate
                    ];
                }else{
                    $dateConditions = [
                        $this->aliasField('date >=') => $selectedFormatStartDate,
                        $this->aliasField('date <=') => $selectedFormatEndDate
                    ];
                }
                $conditions = array_merge($conditions, $dateConditions);
            }else{
                /*POCOR-6267 starts*/
                if (!is_null($institutionId)) {
                    $conditions = [
                        $this->aliasField('academic_period_id') => $selectedPeriod,
                        $this->aliasField('institution_id') => $institutionId,
                    ];
                } else {
                    $conditions = [
                        $this->aliasField('academic_period_id') => $selectedPeriod
                    ];
                }
                /*POCOR-6267 ends*/
            }

            $this->advancedSelectOptions($dateFromOptions, $selectedDateFrom);
            $this->controller->set(compact('dateFromOptions', 'selectedDateFrom'));

            $this->advancedSelectOptions($dateToOptions, $selectedDateTo);
            $this->controller->set(compact('dateToOptions', 'selectedDateTo'));

            $extra['elements']['controls'] = ['name' => 'Student.Absences/controls', 'data' => [], 'options' => [], 'order' => 1];
            $query
                ->find('all')
                ->where($conditions);
            //echo "<pre>";print_r($query);die();
        }
    }
}