<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Validation\Validator;
use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\ORM\Query; 
use Cake\ORM\TableRegistry;
use Cake\Core\Configure;
use App\Model\Table\ControllerActionTable;

class AbsencesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        /*POCOR-6313 changed main table as per client requirement*/
        $this->table('institution_student_absence_details');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AbsenceTypes', ['className' => 'Institution.AbsenceTypes', 'foreignKey' => 'absence_type_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades', 'foreignKey' => 'education_grade_id']);
        $this->hasMany('InstitutionSubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents', 'foreignKey' => 'education_grade_id']);
        if (!in_array('Cases', (array) Configure::read('School.excludedPlugins'))) {
            $this->addBehavior('Institution.Case');
        }
        $this->toggle('add', false);
        $this->toggle('edit', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.custom.onUpdateToolbarButtons' => 'onUpdateToolbarButtons'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
        switch ($action) {
			case 'index':
                    $toolbarButtons['edit'] = $buttons['index'];
                    $toolbarButtons['edit']['url'][0] = 'index';
                    $toolbarButtons['edit']['action'] = 'abc';
                    $toolbarButtons['edit']['type'] = 'button';
                    $toolbarButtons['edit']['label'] = '<i class="fa fa-folder"></i>';
                    $toolbarButtons['edit']['attr'] = $attr;
                    $toolbarButtons['edit']['attr']['title'] = __('Archive');
                    if($toolbarButtons['edit']['url']['action'] == 'Absences'){
                        $toolbarButtons['edit']['url']['action'] = 'InstitutionStudentAbsencesArchived';
                    }
				break;
		}
	}

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        // $this->fields['student_absence_reason_id']['type'] = 'select';
        $this->fields['institution_student_absence_day_id']['visible'] = false;
        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
       
        if ($this->action == 'remove') {
            $institutionId = $this->Session->read('Institution.Institutions.id');
            $userData = $this->Session->read();
            $userId =  $userData['Institution']['StudentUser']['primaryKey']['id'];
            $date = date_format($userData['leave_date'], 'Y-m-d');
            $academicPeriod = !empty($this->request->query) ? $this->request->query('academic_period') :  $AcademicPeriod->getCurrent();
            
            if ($userData) {
                $event->stopPropagation();
                $this->deleteAll([
                    $this->aliasField('student_id') => $userId,
                    $this->aliasField('institution_id') =>  $institutionId,
                    $this->aliasField('academic_period_id') => $academicPeriod,
                    $this->aliasField('date') => $date
                ]);
                
                $this->Alert->success('StudentAbsence.deleteRecord', ['reset'=>true]);
                return $this->controller->redirect(['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'Absences','index']);
            }
        }
    }
    /*POCOR-6313 starts*/
    public function indexBeforeAction(Event $event, ArrayObject $settings)
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
        if ($this->controller->name == 'Directories') {
            unset($settings['indexButtons']['remove']);
        }
        if ($this->controller->name == 'Profiles') {
            unset($settings['indexButtons']['view']);
        }
    }

    public function onGetDate(Event $event, Entity $entity)
    {
        $this->Session->write('leave_date', $entity->date);  
        return $this->Date = date_format($entity->date, 'F d, Y');
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
    /*POCOR-6313 ends*/
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
            } else {
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

            if ($this->controller->name == 'Directories') {
                $userData = $this->Session->read();
                $userId =  $userData['Institution']['StudentUser']['primaryKey']['id'];
                $query
                    ->find('all')
                    ->where([
                        $conditions,
                        $this->aliasField('student_id') => $userId
                    ])->toArray(); 
            } elseif ($this->controller->name == 'Profiles') {
                $userData = $this->Session->read();
                /**
                 * Need to add current login id as param when no data found in existing variable
                 * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
                 * @ticket POCOR-6548
                 */
                //# START: [POCOR-6548] Check if user data not found then add current login user data
                $userId =  $userData['Student']['ExaminationResults']['student_id'];
                if ($userId == null || empty($userId) || $userId == '') {
                    $studentId['id'] = $userData['Auth']['User']['id'];//POCOR-6701
                } else {
                $studentId = $this->ControllerAction->paramsDecode($userId);
                }
                //# END: [POCOR-6548] Check if user data not found then add current login user data
                
                $query
                    ->find('all')
                    ->where([
                        $conditions,
                        $this->aliasField('student_id') => $studentId['id']
                    ])->toArray(); 
            } else {
                $query
                ->find('all')
                ->where($conditions);
                
            }
        }
    }
    
    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        parent::onUpdateActionButtons($event, $entity, $buttons);
        unset($buttons['edit']);
        
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
                if ($sId) {
                    $studentId = $this->ControllerAction->paramsDecode($sId)['id'];
                }
                $studentId = $userData['Student']['Students']['id'];
            }
        } else {
            $studentId = $userData['Auth']['User']['id'];
        }

        /*POCOR-6267 starts*/
        if ($this->request->controller == 'GuardianNavs') {
            $where[$this->aliasField('student_id')] = $studentId;
        }/*POCOR-6267 ends*/ else {
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

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $extra['toolbarButtons']['remove']['type'] = 'hidden';
        $this->fields['institution_student_absence_day_id']['visible'] = false;
        $this->fields['institution_student_absence_day_id']['visible'] = false;
        $this->fields['education_grade_id']['visible'] = true;
        $this->fields['education_grade_id']['attr']['label'] = __('Grade');
        $this->fields['comment']['visible'] = false;
        $this->fields['student_absence_reason_id']['visible'] = false;
        $this->field('absence_type_id', ['visible' => true, 'attr' => ['label' => __('Type')]]);
        $this->field('student', ['visible' => true]);
        $this->field('Date', ['visible' => true]);
        $this->field('academic_period', ['visible' => true]);
        $this->field('class', ['visible' => true]);
        $this->setFieldOrder(['absence_type_id', 'student', 'Date', 'academic_period', 'class', 'education_grade_id']);
        
        $toolbarButtons = $extra['toolbarButtons'];
        if ($toolbarButtons->offsetExists('back')) {
            $encodedParams = $this->request->params['pass'][1];
            if ($this->controller->name == 'Directories') {
                $backUrl = [
                    'plugin' => 'Directory',
                    'controller' => 'Directories',
                    'action' => 'Absences',
                    'index',
                    $encodedParams
                ];
            } else {
                $backUrl = [
                    'plugin' => 'Student',
                    'controller' => 'Students',
                    'action' => 'Absences',
                    'index',
                    $encodedParams
                ];
            }
            
            $toolbarButtons['back']['url'] = $backUrl;
        }
    }

    public function onGetStudent(Event $event, Entity $entity)
    {   
        if (isset($entity->user->name_with_id)) {
            if ($this->action == 'view') {
                return $event->subject()->Html->link($entity->user->name_with_id, [
                    'plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'StudentUser',
                    'view',
                    $this->paramsEncode(['id' => $entity->user->id])
                ]);
            } else {
                return $entity->user->name_with_id;
            }
        }
    }

    public function onGetAcademicPeriod(Event $event, Entity $entity)
    {   
        $result = $this->AcademicPeriods
            ->find()
            ->select(['name'])
            ->where(['id' => $entity->academic_period_id])
            ->first();
        return $this->academicPeriod = $result->name;
    }
}
