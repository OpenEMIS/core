<?php
namespace GuardianNav\Model\Table;

use ArrayObject;
use Cake\Validation\Validator;
use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\ORM\Query; 
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class AbsencesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_student_absences');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        // $this->belongsTo('StudentAbsenceReasons', ['className' => 'Institution.StudentAbsenceReasons']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AbsenceTypes', ['className' => 'Institution.AbsenceTypes', 'foreignKey' => 'absence_type_id']);
        $this->belongsTo('InstitutionStudentAbsenceDays', ['className' => 'Institution.InstitutionStudentAbsenceDays', 'foreignKey' => 'institution_student_absence_day_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades', 'foreignKey' => 'education_grade_id']);
        $this->hasMany('InstitutionSubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents', 'foreignKey' => 'education_grade_id']);

    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function beforeAction($event)
    {
        // $this->fields['student_absence_reason_id']['type'] = 'select';
        $this->fields['institution_student_absence_day_id']['visible'] = false;
        // POCOR-5245
        $queryString = $this->request->query('queryString');
        if ($queryString) {
            $event->stopPropagation();
            $condition = $this->paramsDecode($queryString);            
            $entity = $this->get($condition['id']);            
            $institutionStudentAbsenceDaysEntity = $this->InstitutionStudentAbsenceDays->get($entity->institution_student_absence_day_id);
            $this->InstitutionStudentAbsenceDays->delete($institutionStudentAbsenceDaysEntity);
            TableRegistry::get('InstitutionStudentAbsenceDetails')
                    ->deleteAll(['student_id'=>$entity->student_id,
                            'date'=>$entity->date,
                            ]);            
            
            $this->delete($entity);
            $this->Alert->success('StudentAbsence.deleteRecord', ['reset'=>true]);
            return $this->controller->redirect(['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'Absences','index']);
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $settings)
    {  
        $this->fields['academic_period_id']['visible'] = false;
        $this->fields['institution_id']['visible'] = false;
        $this->fields['education_grade_id']['visible'] = false;
        $this->fields['date']['visible'] = true;

        $this->field('periods', ['visible' => true]);
        $this->field('subjects', ['visible' => true]);
        $this->setFieldOrder('date');
    }

    public function onGetPeriods(Event $event, Entity $entity)
    {   
        $StudentAttendancePerDayPeriods = TableRegistry::get('Attendance.StudentAttendancePerDayPeriods');
        $result = $StudentAttendancePerDayPeriods
            ->find()
            ->select(['name'])
            ->where(['id' => $entity->periods])
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
            $conditions = [
                $this->aliasField('academic_period_id') => $selectedPeriod,
                //$this->aliasField('institution_id') => $institutionId,
                ];
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
                $conditions = [
                    $this->aliasField('academic_period_id') => $selectedPeriod,
                    //$this->aliasField('institution_id') => $institutionId,
                    ];
            }

            $this->advancedSelectOptions($dateFromOptions, $selectedDateFrom);
            $this->controller->set(compact('dateFromOptions', 'selectedDateFrom'));

            $this->advancedSelectOptions($dateToOptions, $selectedDateTo);
            $this->controller->set(compact('dateToOptions', 'selectedDateTo'));

            
            $query
                ->find('all')
                ->where($conditions)
                ->group(['subjects','periods' ]); //POCOR-7275
                $extra['elements']['controls'] = ['name' => 'GuardianNav.Absences/controls', 'data' => [], 'options' => [], 'order' => 1];
        }
    }
    
    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        parent::onUpdateActionButtons($event, $entity, $buttons);
        unset($buttons['edit']);
        if (array_key_exists('view', $buttons)) {
            $institutionId = $entity->institution->id;
            $url = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'StudentAbsences',
                'view',
                $this->paramsEncode(['id' => $entity->id]),
                'institution_id' => $institutionId,
            ];
            $buttons['view']['url'] = $url;

            // POCOR-1893 unset the view button on profiles controller
            if ($this->controller->name == 'Profiles') {
                unset($buttons['view']);
            }
            // end POCOR-1893
        }
        
        if (array_key_exists('remove', $buttons)) {
            $institutionId = $entity->institution->id;
            $url = [
                'plugin' => 'Student',
                'controller' => 'Students',
                'action' => 'Absences',
                'remove',
                'queryString' => $this->paramsEncode(['id' => $entity->id])
            ];
            $buttons['remove']['url'] = $url;

            // POCOR-5245 unset the view button on profiles controller
            if ($this->controller->name == 'Profiles') {
                unset($buttons['remove']);
            }
            // end POCOR-5245
        }

        return $buttons;
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

    public function beforeFind( Event $event, Query $query )
    {
		$userData = $this->Session->read();
        $session = $this->request->session();//POCOR-6267
        if ($userData['Auth']['User']['is_guardian'] == 1) { 
            /*POCOR-6267 starts*/
            if ($this->request->controller == 'GuardianNavs') {
                $studentId = $session->read('Student.Students.id');
            }/*POCOR-6267 ends*/ else {
                $sId = $userData['Student']['ExaminationResults']['student_id']; 
                if (!empty($sId)) {
                    $studentId = $this->ControllerAction->paramsDecode($sId)['id'];
                } else {
                    $studentId = $session->read('Student.Students.id');
                }
            }
        } else {
            $studentId = $userData['Auth']['User']['id'];
        }

        if ($this->request->controller == 'GuardianNavs') {
            $where[$this->aliasField('student_id')] = $studentId;
        } else {
            if(!empty($userData['System']['User']['roles']) & !empty($userData['Student']['Students']['id'])) {
                $where[$this->aliasField('student_id')] = $userData['Student']['Students']['id'];
            } else {
                if (!empty($studentId)) {
                    $where[$this->aliasField('student_id')] = $studentId;
                }
            }
        }
		
        $InstitutionStudentAbsenceDetails = TableRegistry::get('Institution.InstitutionStudentAbsenceDetails');
        $query
            ->find('all')
            ->autoFields(true)
            ->select([
                'comment' => $InstitutionStudentAbsenceDetails->aliasField('comment'),
                'periods' => $InstitutionStudentAbsenceDetails->aliasField('period'),
                'subjects' => $InstitutionStudentAbsenceDetails->aliasField('subject_id')
            ])
            ->leftJoin(
            [$InstitutionStudentAbsenceDetails->alias() => $InstitutionStudentAbsenceDetails->table()],
            [
                $InstitutionStudentAbsenceDetails->aliasField('student_id = ') . $this->aliasField('student_id'),
                $InstitutionStudentAbsenceDetails->aliasField('date = ') . $this->aliasField('date'),
                $InstitutionStudentAbsenceDetails->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                $InstitutionStudentAbsenceDetails->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                $InstitutionStudentAbsenceDetails->aliasField('institution_class_id = ') . $this->aliasField('institution_class_id')
            ]
        )->where($where);
    }
    
}
