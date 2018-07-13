<?php
namespace Institution\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;

class StudentAttendancesTable extends AppTable
{
    private $allDayOptions = [];
    private $selectedDate;

    public function initialize(array $config)
    {
        $this->table('institution_class_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'student_id']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->hasMany('InstitutionClassGrades', ['className' => 'Institution.InstitutionClassGrades']);

        $this->addBehavior('Institution.Calendar');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentAttendances' => ['index', 'view']
        ]);
    }

    

    public function beforeAction(Event $event)
    {
        $this->ControllerAction->field('openemis_no');
        $this->ControllerAction->field('student_id');
        $this->ControllerAction->field('institution_class_id', ['visible' => false]);
        $this->ControllerAction->field('education_grade_id', ['visible' => false]);
        $this->ControllerAction->field('academic_period_id', ['visible' => false]);
        $this->ControllerAction->field('status', ['visible' => false]);
        $this->ControllerAction->field('student_status_id', ['visible' => false]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $settings)
    {
        // academic_period_id filter
        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $periodOptions = $AcademicPeriod->getYearList();

        if (empty($this->request->query['academic_period_id'])) {
            $this->request->query['academic_period_id'] = $AcademicPeriod->getCurrent();
        }
        $selectedPeriod = $this->queryString('academic_period_id', $periodOptions);
        $Classes = TableRegistry::get('Institution.InstitutionClasses');
        $institutionId = $this->Session->read('Institution.Institutions.id');

        $this->advancedSelectOptions($periodOptions, $selectedPeriod, [
            'message' => '{{label}} - ' . $this->getMessage('general.noClasses'),
            'callable' => function ($id) use ($Classes, $institutionId) {
                return $Classes->findByInstitutionIdAndAcademicPeriodId($institutionId, $id)->count();
            }
        ]);

        $this->request->query['academic_period_id'] = $selectedPeriod;

        if ($selectedPeriod != 0) {
            $this->controller->set(compact('periodOptions', 'selectedPeriod'));
            // academic_period_id filter - end
            
            // week options filter
            $todayDate = date("Y-m-d");
            $weeks = $AcademicPeriod->getAttendanceWeeks($selectedPeriod);
            $weekStr = 'Week %d (%s - %s)';
            $weekOptions = [];
            $currentWeek = null;
            foreach ($weeks as $index => $dates) {
                if ($todayDate >= $dates[0]->format('Y-m-d') && $todayDate <= $dates[1]->format('Y-m-d')) {
                    $weekStr = __('Current Week') . ' %d (%s - %s)';
                    $currentWeek = $index;
                } else {
                    $weekStr = __('Week').' %d (%s - %s)';
                }
                $weekOptions[$index] = sprintf($weekStr, $index, $this->formatDate($dates[0]), $this->formatDate($dates[1]));
            }

            $academicPeriodObj = $AcademicPeriod->get($selectedPeriod);
            $startYear = $academicPeriodObj->start_year;
            $endYear = $academicPeriodObj->end_year;

            if (date("Y") >= $startYear && date("Y") <= $endYear && !is_null($currentWeek)) {
                $selectedWeek = !is_null($this->request->query('week')) ? $this->request->query('week') : $currentWeek;
            } else {
                $selectedWeek = $this->queryString('week', $weekOptions);
            }

            if (empty($this->request->query['week'])) {
                $this->request->query['week'] = $selectedWeek;
            }

            $weekStartDate = $weeks[$selectedWeek][0];
            $weekEndDate = $weeks[$selectedWeek][1];

            $this->advancedSelectOptions($weekOptions, $selectedWeek);
            $this->controller->set(compact('weekOptions', 'selectedWeek'));
            // week options filter - end

            // day options filter
            $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
            $firstDayOfWeek = $ConfigItems->value('first_day_of_week');
            $daysPerWeek = $ConfigItems->value('days_per_week');
            $schooldays = [];

            for ($i=0; $i<$daysPerWeek; $i++) {
                // sunday should be '7' in order to be displayed
                $schooldays[] = 1 + ($firstDayOfWeek + 6 + $i) % 7;
            }

            $week = $weeks[$selectedWeek];
            if (is_null($this->request->query('mode'))) {
                $dayOptions = [-1 => ['value' => -1, 'text' => __('All Days')]];
            }
            $firstDayOfWeek = $week[0]->copy();
            $firstDay = -1;
            $today = null;

            do {
                if (in_array($firstDayOfWeek->dayOfWeek, $schooldays)) {
                    if ($firstDay == -1) {
                        $firstDay = $firstDayOfWeek->dayOfWeek;
                    }
                    if ($firstDayOfWeek->isToday()) {
                        $today = $firstDayOfWeek->dayOfWeek;
                    }

                    // POCOR-2377 adding the school closed text
                    $schoolClosed = $this->isSchoolClosed($firstDayOfWeek) ? __('School Closed') : '';
                    ;

                    $dayOptions[$firstDayOfWeek->dayOfWeek] = [
                        'value' => $firstDayOfWeek->dayOfWeek,
                        'text' => __($firstDayOfWeek->format('l')) . ' (' . $this->formatDate($firstDayOfWeek) . ') ' . $schoolClosed,
                    ];
                    $this->allDayOptions[strtolower($firstDayOfWeek->format('l'))] = [
                        'date' => $firstDayOfWeek->format('Y-m-d'),
                        'text' => __($firstDayOfWeek->format('l'))
                    ];
                }
                $firstDayOfWeek->addDay();
            } while ($firstDayOfWeek->lte($week[1]));

            $selectedDay = -1;
            if (isset($this->request->query['day'])) {
                $selectedDay = $this->request->query('day');
                if (!array_key_exists($selectedDay, $dayOptions)) {
                    $selectedDay = $firstDay;
                }
            } else {
                if (!is_null($today)) {
                    $selectedDay = $today;
                } else {
                    $selectedDay = $firstDay;
                }
            }
            $dayOptions[$selectedDay][] = 'selected';

            // added to query string to find the selected day
            if (empty($this->request->query['day'])) {
                $this->request->query['day'] = $selectedDay;
            }

            $currentDay = $week[0]->copy();
            if ($selectedDay != -1) {
                if ($currentDay->dayOfWeek != $selectedDay) {
                    $this->selectedDate = $currentDay->next($selectedDay);
                } else {
                    $this->selectedDate = $currentDay;
                }

                if (!is_null($this->request->query('mode'))) {
                    if ($this->isSchoolClosed($this->selectedDate)) {
                        unset($this->request->query['mode']);
                    }
                }
            } else {
                $this->selectedDate = $week;
            }
            $this->controller->set(compact('dayOptions', 'selectedDay'));
            // day options filter - end

            // class options filter
            $userId = $this->Auth->user('id');
            $AccessControl = $this->AccessControl;
            $classOptions = $Classes
                ->find('list')
                ->find('byAccess', ['userId' => $userId, 'accessControl' => $AccessControl, 'controller' => $this->controller]) // restrict user to see own class if permission is set
                ->where([
                    $Classes->aliasField('institution_id') => $institutionId,
                    $Classes->aliasField('academic_period_id') => $selectedPeriod
                ])
                ->order(['name'])
                ->toArray();

            $selectedClass = $this->queryString('class_id', $classOptions);
            $this->advancedSelectOptions($classOptions, $selectedClass);
            $this->controller->set(compact('classOptions', 'selectedClass'));
            // class options filter - end

            // period list options
            $StudentAttendanceMarkTypes = TableRegistry::get('Attendance.StudentAttendanceMarkTypes');
            $attendancePeriodOptions = $StudentAttendanceMarkTypes->getAttendancePerDayOptionsByClass($selectedClass, $selectedPeriod);
        
            $selectedAttendancePeriod = $this->queryString('attendance_period_id', $attendancePeriodOptions);
            $this->advancedSelectOptions($attendancePeriodOptions, $selectedAttendancePeriod);
            $this->controller->set(compact('attendancePeriodOptions', 'selectedAttendancePeriod'));


            // query
            $query = $settings['query'];

            if ($selectedDay == -1) {
                $startDate = $weekStartDate->format('Y-m-d');
                $endDate = $weekEndDate->format('Y-m-d');
            } else {
                $startDate = $this->selectedDate->format('Y-m-d');
                $endDate = $startDate;
            }

            $conditions = [];
            $conditions['OR'] = [
                'OR' => [
                    [
                        'InstitutionStudents.end_date IS NOT NULL',
                        'InstitutionStudents.start_date <=' => $startDate,
                        'InstitutionStudents.end_date >=' => $startDate
                    ],
                    [
                        'InstitutionStudents.end_date IS NOT NULL',
                        'InstitutionStudents.start_date <=' => $endDate,
                        'InstitutionStudents.end_date >=' => $endDate
                    ],
                    [
                        'InstitutionStudents.end_date IS NOT NULL',
                        'InstitutionStudents.start_date >=' => $startDate,
                        'InstitutionStudents.end_date <=' => $endDate
                    ]
                ],
                [
                    'InstitutionStudents.end_date IS NULL',
                    'InstitutionStudents.start_date <=' => $endDate
                ]
            ];

            $query
                ->contain(['Users'])
                ->find('withAbsence', ['date' => $this->selectedDate])
                ->innerJoin(['InstitutionClasses' => 'institution_classes'], [
                    'InstitutionClasses.id = '.$this->aliasField('institution_class_id')
                ])
                ->innerJoin(['InstitutionStudents' => 'institution_students'], [
                    'InstitutionStudents.academic_period_id = InstitutionClasses.academic_period_id',
                    'InstitutionStudents.institution_id = InstitutionClasses.institution_id',
                    'InstitutionStudents.education_grade_id = '. $this->aliasField('education_grade_id'),
                    'InstitutionStudents.student_id = '. $this->aliasField('student_id'),
                ])
                ->where([$this->aliasField('institution_class_id') => $selectedClass])
                // ->where($conditions);
                ->where(['1 = 0']);
        }

        $toolbarElements[] = [
            'name' => 'Institution.Attendance/controls',
            'data' => [],
            'options' => []
        ];
        $this->controller->set('toolbarElements', $toolbarElements);

        $this->ControllerAction->field('type', ['tableColumnClass' => 'vertical-align-top']);
        $this->ControllerAction->field('reason', ['tableColumnClass' => 'vertical-align-top']);
    }

    public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options)
    {
        $requestQuery = $request->query;
        $selectedAcademicPeriodId = array_key_exists('academic_period_id', $requestQuery) ? $requestQuery['academic_period_id'] : null;
        $selectedClassId = array_key_exists('class_id', $requestQuery) ? $requestQuery['class_id'] : null;

        // sort
        $sortList = ['Users.openemis_no', 'Users.first_name'];
        if (array_key_exists('sortWhitelist', $options)) {
            $sortList = array_merge($options['sortWhitelist'], $sortList);
        }
        $options['sortWhitelist'] = $sortList;

        $query
            ->contain(['Users'])
            ->find('withAbsence', ['date' => $this->selectedDate])
            ->where([
                $this->aliasField('academic_period_id') => $selectedAcademicPeriodId,
                $this->aliasField('institution_class_id') => $selectedClassId,
                $this->aliasField('student_status_id') => $this->StudentStatuses->getIdByCode('CURRENT'),
            ]);

        $sortable = array_key_exists('sort', $requestQuery) ? $requestQuery['sort'] : false;
        if (!$sortable) {
            $query->order(['Users.first_name' => 'ASC']);
        }
    }

    public function indexAfterAction(Event $event, $data)
    {
        $this->ControllerAction->field('openemis_no', ['visible' => true, 'type' => 'string', 'sort' => ['field' => 'Users.openemis_no']]);
        $this->ControllerAction->field('student_id', ['visible' => true, 'type' => 'string', 'sort' => ['field' => 'Users.first_name']]);

        $this->ControllerAction->setFieldOrder(['openemis_no', 'student_id', 'type', 'reason']);
    }

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        $sessionPath = 'Users.institution_student_absences.';
        $timeError = $this->Session->read($sessionPath.$entity->student_id.'.timeError');
        $startTimestamp = $this->Session->read($sessionPath.$entity->student_id.'.startTimestamp');
        $endTimestamp = $this->Session->read($sessionPath.$entity->student_id.'.endTimestamp');
        $this->Session->delete($sessionPath.$entity->student_id.'.timeError');
        $this->Session->delete($sessionPath.$entity->student_id.'.startTimestamp');
        $this->Session->delete($sessionPath.$entity->student_id.'.endTimestamp');
        $html = $event->subject()->Html->link($entity->user->openemis_no, [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'StudentUser',
            'view',
            $this->paramsEncode(['id' => $entity->user->id])
        ]);

        if ($timeError) {
            $startTime = __('Must be within shift timing, from') . ' ' . date('h:i A', $startTimestamp);
            $endTime = __('to') . ' ' . date('h:i A', $endTimestamp);

            $error = $startTime . ' ' . $endTime;
            $html .= '&nbsp;<i class="fa fa-exclamation-circle fa-lg table-tooltip icon-red" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="'.$error.'"></i>';
        }

        return $html;
    }

    public function onGetType(Event $event, Entity $entity)
    {
        pr('onGetType');
        pr($entity);
        die;
    }

    public function findWithAbsence(Query $query, array $options)
    {
        $date = $options['date'];

        $conditions = ['StudentAbsences.student_id = StudentAttendances.student_id'];
        if (is_array($date)) {
            $startDate = $date[0]->format('Y-m-d');
            $endDate = $date[1]->format('Y-m-d');

            $conditions[] = [
                'StudentAbsences.end_date IS NOT NULL',
                'StudentAbsences.date >= ' => $startDate,
                'StudentAbsences.date <= ' => $endDate,
            ];

            // $conditions['OR'] = [
            //     'OR' => [
            //         [
            //             'StudentAbsences.end_date IS NOT NULL',
            //             'StudentAbsences.start_date >=' => $startDate,
            //             'StudentAbsences.start_date <=' => $endDate
            //         ],
            //         [
            //             'StudentAbsences.end_date IS NOT NULL',
            //             'StudentAbsences.start_date <=' => $startDate,
            //             'StudentAbsences.end_date >=' => $startDate
            //         ],
            //         [
            //             'StudentAbsences.end_date IS NOT NULL',
            //             'StudentAbsences.start_date <=' => $endDate,
            //             'StudentAbsences.end_date >=' => $endDate
            //         ],
            //         [
            //             'StudentAbsences.end_date IS NOT NULL',
            //             'StudentAbsences.start_date >=' => $startDate,
            //             'StudentAbsences.end_date <=' => $endDate
            //         ]
            //     ],
            //     [
            //         'StudentAbsences.end_date IS NULL',
            //         'StudentAbsences.start_date <=' => $endDate
            //     ]
            // ];
        } else {
            $conditions['StudentAbsences.date <= '] = $date->format('Y-m-d');
            $conditions['StudentAbsences.date >= '] = $date->format('Y-m-d');
        }
        return $query
            ->select([
                $this->aliasField('student_id'),
                'Users.openemis_no', 'Users.first_name', 'Users.middle_name', 'Users.third_name','Users.last_name', 'Users.id',
                'StudentAbsences.date',
                'StudentAbsences.student_id',
                'StudentAbsences.institution_id',
                'StudentAbsences.institution_class_id',
                'StudentAbsences.academic_period_id',
                'StudentAbsences.start_time',
                'StudentAbsences.end_time',
                'StudentAbsences.absence_type_id',
                'StudentAbsences.student_absence_reason_id'
            ])
            ->join([
                [
                    'table' => 'institution_student_absences',
                    'alias' => 'StudentAbsences',
                    'type' => 'LEFT',
                    'conditions' => $conditions
                ]
            ]);
    }
}
