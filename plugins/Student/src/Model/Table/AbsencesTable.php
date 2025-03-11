<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Database\Schema\Table;
use Cake\Datasource\ConnectionManager;
use Cake\Validation\Validator;
use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Core\Configure;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\Locator\TableLocator;

class AbsencesTable extends ControllerActionTable
{
    private $institutionId = null;
    private $studentId = null;
    public function initialize(array $config): void
    {
        /*POCOR-6313 changed main table as per client requirement*/
        $this->setTable('institution_student_absence_details');
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
        $this->toggle('delete', false); // POCOR-8299
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['Absences' =>['student_id','institution_id','academic_period_id','institution_class_id','date','period','subject_id']
            ]
        ]);
        // $this->addBehavior('Student.StudentTab', [
        //     'appliedAction' => ['Absences' =>['id', 'institution_id']
        //     ]
        // ]);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.custom.onUpdateToolbarButtons' => 'onUpdateToolbarButtons'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        // $this->fields['student_absence_reason_id']['type'] = 'select';
        $this->fields['institution_student_absence_day_id']['visible'] = false;
        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');

        if ($this->action == 'remove') {
            $institutionId = $this->getInstitutionID();
            $userData = $this->Session->read();
            $userId =  $userData['Institution']['StudentUser']['primaryKey']['id'];
            $date = date_format($userData['leave_date'], 'Y-m-d');
            $academicPeriod = !empty($this->request->getQuery()) ? $this->request->getQuery('academic_period') :  $AcademicPeriod->getCurrent();
            $encodedQueryStringR = $this->paramsEncode(['id' =>  $queryString['id'],'student_id' =>  $queryString['student_id'],'type' =>  $queryString['type'],'institution_id' => $institutionId, 'user_id' =>  $queryString['user_id'],]);
            if ($userData) {
                $event->stopPropagation();
                $this->deleteAll([
                    $this->aliasField('student_id') => $userId,
                    $this->aliasField('institution_id') =>  $institutionId,
                    $this->aliasField('academic_period_id') => $academicPeriod,
                    $this->aliasField('date') => $date
                ]);

                $this->Alert->success('StudentAbsence.deleteRecord', ['reset'=>true]);
                return $this->controller->redirect(['plugin' => $this->controller->getPlugin(), 'controller' => $this->controller->getName(), 'action' => 'Absences','0'=>'index','1'=> $encodedQueryStringR]);
            }
        }
        $session = $this->request->getSession();
        $institutionId = $this->getInstitutionID();
        /*if ($session->check('Institution.Institutions.id')) {
            $institutionId = $session->read('Institution.Institutions.id');
        }*/
        $studentId = $this->getStudentID();
        $this->institutionId = $institutionId;
        $this->studentId = $studentId;

		// Start POCOR-5188
		if($this->request->getParam('controller') == 'Students'){
			$is_manual_exist = $this->getManualUrl('Personal','Absences','Students - Academic');
			if(!empty($is_manual_exist)){
				$btnAttr = [
					'class' => 'btn btn-xs btn-default icon-big',
					'data-toggle' => 'tooltip',
					'data-placement' => 'bottom',
					'escape' => false,
					'target'=>'_blank'
				];

				$helpBtn['url'] = $is_manual_exist['url'];
				$helpBtn['type'] = 'button';
				$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
				$helpBtn['attr'] = $btnAttr;
				$helpBtn['attr']['title'] = __('Help');
				$extra['toolbarButtons']['help'] = $helpBtn;
			}
		}elseif($this->request->getParam('controller') == 'Directories'){
			$is_manual_exist = $this->getManualUrl('Directory','Absences','Students - Academic');
			if(!empty($is_manual_exist)){
				$btnAttr = [
					'class' => 'btn btn-xs btn-default icon-big',
					'data-toggle' => 'tooltip',
					'data-placement' => 'bottom',
					'escape' => false,
					'target'=>'_blank'
				];

				$helpBtn['url'] = $is_manual_exist['url'];
				$helpBtn['type'] = 'button';
				$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
				$helpBtn['attr'] = $btnAttr;
				$helpBtn['attr']['title'] = __('Help');
				$extra['toolbarButtons']['help'] = $helpBtn;
			}

		}
		// End POCOR-5188
        $toolbarButtons = $extra['toolbarButtons'];
        if ($toolbarButtons->offsetExists('back')) {
            $encodedParams = $this->request->getAttribute('params')['pass'][1];
            if ($this->controller->getName() == 'Directories') {
                $backUrl = [
                    'plugin' => 'Directory',
                    'controller' => 'Directories',
                    'action' => 'Absences',
                    'index',
                    $encodedQueryString
                ];
            } else {
                $backUrl = [
                    'plugin' => 'Student',
                    'controller' => 'Students',
                    'action' => 'Absences',
                    'index',
                    $encodedQueryString
                ];
            }

            $toolbarButtons['back']['url'] = $backUrl;
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
        if ($this->controller->getName() == 'Directories') {
            unset($settings['indexButtons']['remove']);
        }
        if ($this->controller->getName() == 'Profiles') {
            unset($settings['indexButtons']['view']);
        }
        $this->addExtraButtons($settings);
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
        $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
        $result = $InstitutionSubjects
            ->find()
            ->select(['name'])
            ->where(['id' => $entity->subjects])
            ->first();
        return $this->subjects = $result->name;
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');

        $institutionId = $this->getInstitutionID();
        if ($this->request->getQuery('user_id') !== null) {
            $staffId = $this->request->getQuery('user_id');
            $this->Session->write('Staff.Staff.id', $staffId);
        } else {
            $staffId = $this->getStaffID();
        }

        $academicPeriodList = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        if (empty($this->request->getQuery('academic_period'))) {
            $selectedPeriod = $AcademicPeriod->getCurrent();
            //$this->request = $this->request->withQueryParams(['academic_period' => $selectedPeriod]);
        } else {
            $selectedPeriod = $this->request->getQuery('academic_period');
        }

        $this->advancedSelectOptions($academicPeriodList, $selectedPeriod);

        $dateFrom = $this->request->getQuery('dateFrom');
        $dateTo = $this->request->getQuery('dateTo');
        $selectedSubject = $this->request->getQuery('education_subject_id');

        $this->request = $this->request->withQueryParams([
            'academic_period' => $selectedPeriod,
            'academic_period_id' => $selectedPeriod,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        ]);

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
                    $this->aliasField('absence_type_id !=') => 0//POCOR-7167
                ];
            } else {
                $conditions = [
                    $this->aliasField('academic_period_id') => $selectedPeriod,
                    $this->aliasField('absence_type_id !=') => 0//POCOR-7167
                ];
            }
            /*POCOR-6267 ends*/

            if(!empty($this->request->getQuery('dateFrom')) && $this->request->getQuery('dateFrom') != '-1'){
                $academicPeriodObj = $AcademicPeriod->get($selectedPeriod);
                $startYear = $academicPeriodObj->start_year;
                $endYear = $academicPeriodObj->end_year;

                if (date("Y") >= $startYear && date("Y") <= $endYear && !is_null($currentdateFrom)) {
                    $selectedDateFrom = !is_null($this->request->getQuery('dateFrom')) ? $this->request->getQuery('dateFrom') : $currentdateFrom;
                } else {
                    $selectedDateFrom = $this->queryString('dateFrom', $dateFromOptions);
                }
                if (date("Y") >= $startYear && date("Y") <= $endYear) {
                    $selectedDateTo = !is_null($this->request->getQuery('dateTo')) ? $this->request->getQuery('dateTo') : $currentdateTo;
                } else {
                    $selectedDateTo = $this->queryString('dateTo', $dateToOptions);
                }

                $weekStartDate = $dateFrom[$selectedDateFrom][0];
                $weekEndDate = $dateFrom[$selectedDateTo][0];
                $startDate = $weekStartDate;
                $endDate = $weekEndDate;
                $selectedFormatStartDate = !empty($startDate) ? date_format($startDate, 'Y-m-d') : '';
                $selectedFormatEndDate = !empty($endDate) ? date_format($endDate, 'Y-m-d') : '';

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
                        $this->aliasField('absence_type_id !=') => 0//POCOR-7167
                    ];
                } else {
                    $conditions = [
                        $this->aliasField('academic_period_id') => $selectedPeriod,
                        $this->aliasField('absence_type_id !=') => 0//POCOR-7167
                    ];
                }
                /*POCOR-6267 ends*/
            }
            $this->advancedSelectOptions($dateFromOptions, $selectedDateFrom);
            $this->controller->set(compact('dateFromOptions', 'selectedDateFrom'));

            $this->advancedSelectOptions($dateToOptions, $selectedDateTo);
            $this->controller->set(compact('dateToOptions', 'selectedDateTo'));

            $extra['elements']['controls'] = ['name' => 'Student.Absences/controls', 'data' => ['encodedQueryString' => $encodedQueryString], 'options' => [], 'order' => 1];

            if ($this->controller->getName() == 'Directories') {
                $userData = $this->Session->read();
                $userId =  $userData['Institution']['StudentUser']['primaryKey']['id'];
                if(empty($userId)) {
                    $userId = $queryString['student_id'];
                }
                $query
                    ->find('all')
                    ->where([
                        $conditions,
                        $this->aliasField('student_id') => $userId
                    ])->toArray();
            } elseif ($this->controller->getName() == 'Profiles') {
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
                    ])->distinct([$this->aliasField('date')])->toArray(); //POCOR-7416
            } else {
                $query
                ->find('all')
                ->where($conditions);
                // ->distinct([$this->aliasField('date')]);//7416

            }

        }

        // echo "<pre>"; print_r($this->request);
        // die;
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
        //$tabElements = $this->controller->getAcademicTabElements($options);
        $tabElements = $this->getAcademicTabElements($options);
        if($this->controller->getName() == 'Directories') {
			$tabElements = $this->controller->getAcademicTabElements($options);
		}
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Absences');
    }

    public function indexAfterAction(Event $event, $data)
    {
        $this->setupTabElements();
    }

    public function beforeFind( Event $event, Query $query )
    {
		$userData = $this->Session->read();
        $session = $this->request->getSession();//POCOR-6267
        if ($userData['Auth']['User']['is_guardian'] == 1) {
            /*POCOR-6267 starts*/
            if ($this->request->getParam('controller') == 'GuardianNavs') {
                $studentId = $this->getStudentID();
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
        if ($this->request->getParam('controller') == 'GuardianNavs') {
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

        $tableLocator = new TableLocator();
        $InstitutionStudentAbsenceDetails = $tableLocator->get('InstitutionStudentAbsenceDetails');
        $query
            ->find('all')
            ->enableAutoFields(true)
            ->select([
                'comment' => $InstitutionStudentAbsenceDetails->aliasField('comment'),
                'periods' => $InstitutionStudentAbsenceDetails->aliasField('period'),
                'subjects' => $InstitutionStudentAbsenceDetails->aliasField('subject_id')
            ])
            ->leftJoin(
            [$InstitutionStudentAbsenceDetails->getAlias() => $InstitutionStudentAbsenceDetails->getTable()],
            [
                $InstitutionStudentAbsenceDetails->aliasField('student_id = ') . $this->aliasField('student_id'),
                $InstitutionStudentAbsenceDetails->aliasField('date = ') . $this->aliasField('date'),
                $InstitutionStudentAbsenceDetails->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                $InstitutionStudentAbsenceDetails->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                $InstitutionStudentAbsenceDetails->aliasField('institution_class_id = ') . $this->aliasField('institution_class_id'),
                $InstitutionStudentAbsenceDetails->aliasField('period = ') . $this->aliasField('period')//POCOR-7167
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
            $encodedParams = $this->request->getAttribute('params')['pass'][1];
            if ($this->controller->getName() == 'Directories') {
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
                return $event->getSubject()->Html->link($entity->user->name_with_id, [
                    'plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'StudentUser',
                    'view',
                    $this->paramsEncode(['id' => $entity->user->id, 'institution_id' => $entity->institution_id, 'student_id'=>$entity->user->id])
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

    private function addExtraButtons(ArrayObject $extra)
    {
        $toolbarButtons = $extra['toolbarButtons'];
        // $this->addArchiveButton($toolbarButtons);
    }



    /**
     * @param $toolbarButtons
     */
    private function addArchiveButton($toolbarButtons)
    {
        // POCOR-7895: removed unnecessary lines
            $customButtonName = 'archive';
            $customButtonUrl = [
                'plugin' => 'Student',
                'controller' => 'Students',
                'action' => 'ArchivedAbsences'
            ];
            $customButtonLabel = '<i class="fa fa-folder"></i>';
            $customButtonTitle = __('Archive');
            $this->generateButton($toolbarButtons, $customButtonName, $customButtonTitle, $customButtonLabel, $customButtonUrl);
    }

    private function isArchiveExists()
    {
        $is_archive_exists = false;
        $targetTableExists = $this->hasArchiveTable($this);
        if (!$targetTableExists) {
            return $is_archive_exists;
        }
        $institutionId = $this->institutionId;
        $studentId = $this->studentId;
        $AssessmentItemResultsArchived = TableRegistry::get('Student.ArchivedAbsences');
        $count = $AssessmentItemResultsArchived->find()
//            ->distinct([$AssessmentItemResultsArchived->aliasField('student_id')])// POCOR-7339-HINDOL
            ->select([$AssessmentItemResultsArchived->aliasField('student_id')])// POCOR-7339-HINDOL
            ->where([
                $AssessmentItemResultsArchived->aliasField('institution_id') => $institutionId,
                $AssessmentItemResultsArchived->aliasField('student_id') => $studentId,
            ])->first();
        if($count) {
            $is_archive_exists = true;
        }
        if(!$count) {
            $is_archive_exists = false;
        }
        return $is_archive_exists;
    }

    public function hasArchiveTable($sourceTable)
    {
        $sourceTableName = $sourceTable->getTable();
        $targetTableName = $sourceTableName . '_archived';
        $connection = ConnectionManager::get('default');
        $schemaCollection = new \Cake\Database\Schema\Collection($connection);
        $existingTables = $schemaCollection->listTables();
        $tableExists = in_array($targetTableName, $existingTables);

        if ($tableExists) {
            return true;
        }

        $sourceTableSchema = $schemaCollection->describe($sourceTableName);

        // Create a new table schema for the target table
        $targetTableSchema = new Table($targetTableName);

        // Copy the columns from the source table to the target table
        foreach ($sourceTableSchema->columns() as $column) {
            $columnDefinition = $sourceTableSchema->getColumn($column);
            $targetTableSchema->addColumn($column, $columnDefinition);
        }
        $randomString = $this->generateRandomString();
        // Copy the indexes from the source table to the target table
        foreach ($sourceTableSchema->indexes() as $index) {
            $indexDefinition = $sourceTableSchema->index($index);
            $targetTableSchema->addIndex($index . $randomString, $indexDefinition);
        }

        // Copy the constraints from the source table to the target table
        // FIX for random FK name

        foreach ($sourceTableSchema->constraints() as $constraint) {
            $constraintDefinition = $sourceTableSchema->constraint($constraint);
            $targetTableSchema->addConstraint($constraint . $randomString, $constraintDefinition);
        }



        // Generate the SQL statement to create the target table
        $createTableSql = $targetTableSchema->createSql($connection);

        // Execute the SQL statement to create the target table
        foreach ($createTableSql as $sql) {
            $connection->execute($sql);
        }

        // Check if the target table was created successfully
        $existingTables = $schemaCollection->listTables();
        $tableExists = in_array($targetTableName, $existingTables);
        if ($tableExists) {
            return true;
        }

        return false; // Return false if the table couldn't be created
    }

    private function generateRandomString($length = 4) {
        $bytes = random_bytes($length);
        return substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $length);
    }

    private function generateButton(ArrayObject $toolbarButtons, $name, $title, $label, $url, $btnAttr = null)
    {
        if (!$btnAttr) {
            $btnAttr = $this->getButtonAttr();
        }
        $customButton = [];
        if (isset($url['_ext'])) {
            unset($customButton['url']['_ext']);
        }
        if (isset($url['pass'])) {
            unset($customButton['url']['pass']);
        }
        if (isset($url['paging'])) {
            unset($customButton['url']['paging']);
        }
        if (isset($url['filter'])) {
            unset($customButton['url']['filter']);
        }
        $customButton['type'] = 'button';
        $customButton['attr'] = $btnAttr;
        $customButton['attr']['title'] = $title;
        $customButton['label'] = $label;
        $customButton['url'] = $url;
        $name = 'archive';
        $toolbarButtons[$name] = $customButton;
    }

    public
    function getInstitutionID($debugString = "")
    {
        // POCOR-8115;
        // institution_id should always be in query string, if not, die as an error
        $institution_id = $this->getQueryString('institution_id');
        if (!$institution_id) {
            if ($debugString != "") {
                die($debugString . 'For Developer: You should put institution_id into query string first');
            }
        }
        return $institution_id;
    }

    public
    function getStudentID($debugString = "")
    {
        // POCOR-8115;
        // student_id should always be in query string, if not, die as an error
        $student_id = $this->getQueryString('student_id');
        if (!$student_id) {
            if ($debugString != "") {
                die($debugString . 'For Developer: You should put student_id into query string first');
            }
        }
        return $student_id;
    }

    public
    function getStaffID($debugString = "")
    {
        // POCOR-8115;
        // staff_id should always be in query string, if not, die as an error
        $staff_id = $this->getQueryString('staff_id');
        if (!$staff_id) {
            if ($debugString != "") {
                die($debugString . 'For Developer: You should put staff_id into query string first');
            }
        }
        return $staff_id;
    }

}
