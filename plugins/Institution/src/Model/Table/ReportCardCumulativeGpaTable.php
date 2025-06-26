<?php

namespace Institution\Model\Table;

use ArrayObject;
use ZipArchive;
use DateTime;
use DateTimeZone;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\ResultSet;
use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\I18n\Date;
use Cake\Log\Log;
use Cake\Datasource\ConnectionManager;
use App\Model\Table\ControllerActionTable;
use Cake\Http\Session; // POCOR-9162
use Cake\I18n\FrozenTime; // POCOR-9162
use Cake\ORM\Table; // POCOR-9162
use Cake\Utility\Inflector; // POCOR-9162

// POCOR-9162

/**
 * ReportCardCumulativeGpaTable class, Generate cumulative GPA for student.
 * POCOR-8222
 * This class is responsible for handling the cumulative GPA data for students' report cards.
 * It extends from the `ControllerActionTable` class and interacts with the database to manage
 * and process cumulative GPA information for report cards. The class may include logic for
 * calculating or retrieving cumulative GPAs, ensuring that the data aligns with institutional requirements.
 */
class ReportCardCumulativeGpaTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_class_students');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Institutions', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->ReportCards = self::getDynamicTableInstance('ReportCard.ReportCards');
        $this->ReportCardProcesses = self::getDynamicTableInstance('ReportCard.ReportCardProcesses');
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['ReportCardCumulativeGpa' =>['id','student_id','academic_period_id','education_grade_id','institution_class_id']
            ]
        ]);

        }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.generate'] = 'generate';
        $events['ControllerAction.Model.generateAll'] = 'generateAll';
        return $events;
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $educationGradeId = $this->request->getQuery('education_grade_id');
        if (is_null($educationGradeId)) {
            return $buttons;
        }
        $queryString = $this->request->getQuery('queryString');
       if (isset($buttons['view'])) {
            $url = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'ReportCardCumulativeGpa',
                0 =>  'view',
                1 => $this->paramsEncode(['id' => $entity->id,'institution_id' => $this->getInstitutionID(),'student_id'=> $entity->student_id,'institution_class_id' => $entity->institution_class_id]),

            ];
        }

        $params = [
            'education_grade_id' => $educationGradeId,
            'student_id' => $entity->student_id,
            'institution_id' => $entity['institution']['id'],
            'academic_period_id' => $entity->academic_period_id,
            'education_grade_id' => $entity->education_grade_id,
        ];

        $params['institution_class_id'] = $entity->institution_class_id;
       // $buttons['view']['url'] = $url;
        // Generate button, all statuses
        $buttons = $this->addGenerateButton($buttons, $params);

        return $buttons;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('openemis_no', ['sort' => ['field' => 'Users.openemis_no']]);
        $this->field('student_name', ['type' => 'integer','sort' => ['field' => 'Users.first_name']]);

        $this->field('student_id', ['type' => 'hidden']);
        $this->field('next_institution_class_id', ['type' => 'hidden']);
        $this->field('student_status_id', ['type' => 'hidden']);
        $this->field('cumulative_gpa');
        $this->field('created',['visible' => true, 'sort' => false]);

        $this->fields['academic_period_id']['visible'] = false;

    }


    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {

        $institutionId = $this->getInstitutionID();
        $Classes = self::getDynamicTableInstance('Institution.InstitutionClasses');
        $institutionGrade = self::getDynamicTableInstance('Institution.InstitutionGrades');
        $gpaGrades = self::getDynamicTableInstance('Gpa.Cumulative');

        // Academic Periods filter
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->getQuery('academic_period_id')) ? $this->request->getQuery('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        //End

        $gradeGpaTable = self::getDynamicTableInstance('Gpa.EducationGradesGpa');
        /* Commenting old code for POCOR-8962
        $availableGrades =  $gpaGrades->find()->leftJoin(
                        [$gradeGpaTable->getAlias() => $gradeGpaTable->getTable()],
                        [
                            $gpaGrades->aliasField('main_education_grade_id = ') . $gradeGpaTable->aliasField('education_grade_id'),
                        ]
                    )
        ->where(['EducationGradesGpa.academic_period_id' => $selectedAcademicPeriod])
        ->group(['main_education_grade_id'])
        ->extract('main_education_grade_id')
        ->toArray();
        */
        //POCOR-8962
        $availableGrades = $institutionGrade->find()
                        ->where([
                            $institutionGrade->aliasField('academic_period_id') => $selectedAcademicPeriod,
                            $institutionGrade->aliasField('institution_id') => $institutionId,
                        ])
                        ->extract('education_grade_id')
                        ->toArray();

        // Grade filter
        $educationGradeOptions = [];
        if (!empty($availableGrades)) {
            $educationGradeOptions = $this->EducationGrades->find('list')
                ->where([
                    $this->EducationGrades->aliasField('id IN ') => $availableGrades
                ])
                ->toArray();
        } else {
            $this->Alert->warning('ReportCardStatuses.noProgrammes');
        }
        $educationGradeOptions = ['-1' => '-- '.__('Select Education Grade').' --'] + $educationGradeOptions;
        $selectedGrade = !is_null($this->request->getQuery('education_grade_id')) ? $this->request->getQuery('education_grade_id') : -1;
        $this->controller->set(compact('educationGradeOptions', 'selectedGrade'));
        //End

        // Class filter
        $classOptions = [];
        $selectedClass = !is_null($this->request->getQuery('class_id')) ? $this->request->getQuery('class_id') : -1;

        //  $educationGradeByReportCardId = '';
            if (!empty($this->request->getQuery('education_grade_id'))) {
                $classOptions = $Classes->find('list')
                    ->matching('ClassGrades')
                    ->where([
                        $Classes->aliasField('academic_period_id') => $selectedAcademicPeriod,
                        $Classes->aliasField('institution_id') => $institutionId,
                        'ClassGrades.education_grade_id' => $this->request->getQuery('education_grade_id')
                    ])
                    ->order([$Classes->aliasField('name')])
                    ->toArray();
               // $educationGradeByReportCardId = $reportCardEntity->education_grade_id;
            } else {

                $selectedClass = -1;
            }

        if (!empty($classOptions)) {
            $classOptions['all'] = "All Classes";
        }

        $classOptions = ['-1' => '-- ' . __('Select Class') . ' --'] + $classOptions;
        $this->controller->set(compact('classOptions', 'selectedClass'));
        if($selectedClass != 'all'){
            $where[$this->aliasField('institution_class_id')] = $selectedClass;
        }
        $where[$this->aliasField('institution_id')] = $institutionId;
        $where[$this->aliasField('student_status_id NOT IN')] = 3;
        $where[$this->aliasField('education_grade_id')] = $selectedGrade;

        //End
        $UsersTable = self::getDynamicTableInstance('Security.Users');
        $query
            ->select([
                'id' => $this->aliasField('id'),
                'institution_id' => $this->aliasField('institution_id'), //POCOR-8699
                'institution_class_id' => $this->aliasField('institution_class_id'),
                'education_grade_id' => $this->aliasField('education_grade_id'),
                'academic_period_id' => $this->aliasField('academic_period_id'),
                'student_id' => $this->aliasField('student_id'),
                'student_name' => $UsersTable->find()->func()->concat([
                    $UsersTable->aliasField('first_name') => 'literal',
                    ' ',
                    $UsersTable->aliasField('last_name') => 'literal'
                ]),
                'openemis_no' => $UsersTable->aliasField('openemis_no'),
            ])
            ->innerJoin(
                [$UsersTable->getAlias() => $UsersTable->getTable()],
                [$UsersTable->aliasField('id') . ' = ' . $this->aliasField('student_id')]
            )
            ->where($where)->group([$this->aliasField('student_id')]);

        if (is_null($this->request->getQuery('sort'))) {
            $query
                ->contain('Users')
                ->order(['Users.first_name', 'Users.last_name']);
        }
        $encodedQueryString = $this->request->getParam('pass')[1];

        $extra['elements']['controls'] = ['name' => 'Institution.Gpa/cumulative', 'data' => ['encodedQueryString' => $encodedQueryString], 'options' => [], 'order' => 1];

        // sort
        $sortList = ['report_card_status', 'Users.first_name', 'Users.openemis_no'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;

        // search
        $search = $this->getSearchKey();
        if (!empty($search)) {
            $nameConditions = $this->getNameSearchConditions(['alias' => 'Users', 'searchTerm' => $search]);
            $extra['OR'] = $nameConditions;
        }

    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $educationGradeId = $this->request->getQuery('education_grade_id');
        $classId = $this->request->getQuery('class_id');
        $loginUserIdUser = $this->Auth->User('id');
        $securityRoles = $this->AccessControl->getRolesByUser($loginUserIdUser)->toArray();
        $securityRoleIds = [];
        foreach ($securityRoles as $key => $value) {
            $securityRoleIds[] = $value->security_role_id;
        }
        $userId = $this->Auth->user('id');
        $userSuperAddmin = $this->Auth->user('super_admin');
        if ($userSuperAddmin == 1) {
            if (!is_null($educationGradeId) && !is_null($classId)) {
                $existingClass = $this->InstitutionClasses->exists([$this->InstitutionClasses->getPrimaryKey() => $classId]);
                if ($existingClass) {
                    $toolbarAttr = [
                        'class' => 'btn btn-xs btn-default',
                        'data-toggle' => 'tooltip',
                        'data-placement' => 'bottom',
                        'escape' => false
                    ];

                    $params = [
                        'institution_id' => $this->getInstitutionID(),
                        'institution_class_id' => $classId,
                        'education_grade_id' => $educationGradeId
                    ];


                    $SecurityFunctions = self::getDynamicTableInstance('Security.SecurityFunctions');
                    $SecurityFunctionsGenerateAllData = $SecurityFunctions
                        ->find()
                        ->where([
                            $SecurityFunctions->aliasField('name') => 'Cumulative Gpa Generate All'])
                        ->first();
                    $SecurityRoleFunctionsTable = self::getDynamicTableInstance('Security.SecurityRoleFunctions');
                    $SecurityRoleFunctionsTableGenerateAllData = $SecurityRoleFunctionsTable
                        ->find()
                        ->where([
                            $SecurityRoleFunctionsTable->aliasField('security_function_id') => $SecurityFunctionsGenerateAllData->id,
                            //$SecurityRoleFunctionsTable->aliasField('_execute') => 1,/
                        ])
                        ->count();

                    // Generate all button
                    $generateButton['url'] = $this->setQueryString($this->url('generateAll'), $params);
                    $generateButton['type'] = 'button';
                    $generateButton['label'] = '<i class="fa fa-refresh"></i>';
                    $generateButton['attr'] = $toolbarAttr;
                    $generateButton['attr']['title'] = __('Generate All');
                    //$ReportCards = self::getDynamicTableInstance('ReportCard.ReportCards');
                    if (!is_null($this->request->getQuery('education_grade_id'))) {
                        $educationGradeId = $this->request->getQuery('education_grade_id');
                    }

                    $ReportCardsData = $this->ReportCards
                        ->find()
                        ->where([
                            $this->ReportCards->aliasField('education_grade_id') => $educationGradeId])
                        ->first();

                    if (!empty($ReportCardsData->generate_start_date)) {
                        $generateStartDate = $ReportCardsData->generate_start_date->format('Y-m-d');
                    }

                    if (!empty($ReportCardsData->generate_end_date)) {
                        $generateEndDate = $ReportCardsData->generate_end_date->format('Y-m-d');
                    }
                    $date = Time::now()->format('Y-m-d');

                    if ($this->AccessControl->isAdmin()) {

                        if (!empty($generateStartDate) && !empty($generateEndDate)) {
                            $extra['toolbarButtons']['generateAll'] = $generateButton;
                        } else {
                            $generateButton['attr']['data-html'] = true;
                            //$generateButton['attr']['title'] .= __('<br>' . $this->getMessage('ReportCardStatuses.date_closed'));
                           // $generateButton['url'] = 'javascript:void(0)';
                            $extra['toolbarButtons']['generateAll'] = $generateButton;
                        }
                    } else {
                        if ($SecurityRoleFunctionsTableGenerateAllData >= 1) {
                            if (!empty($generateStartDate) && !empty($generateEndDate) && $date >= $generateStartDate && $date <= $generateEndDate) {
                                $extra['toolbarButtons']['generateAll'] = $generateButton;
                            } else {
                                $generateButton['attr']['data-html'] = true;
                                //$generateButton['attr']['title'] .= __('<br>' . $this->getMessage('ReportCardStatuses.date_closed'));
                              //  $generateButton['url'] = 'javascript:void(0)';
                                $extra['toolbarButtons']['generateAll'] = $generateButton;
                            }
                        }
                    }



                }
            }
        } else {
            if (!is_null($educationGradeId) && !is_null($classId) && !empty($securityRoleIds)) {
                $existingClass = $this->InstitutionClasses->exists([$this->InstitutionClasses->getPrimaryKey() => $classId]);
                if ($existingClass) {
                    $toolbarAttr = [
                        'class' => 'btn btn-xs btn-default',
                        'data-toggle' => 'tooltip',
                        'data-placement' => 'bottom',
                        'escape' => false
                    ];

                    $params = [
                        'institution_id' => $this->getInstitutionID(),
                        'institution_class_id' => $classId,
                        'education_grade_id' => $educationGradeId
                    ];

                    $SecurityFunctions = self::getDynamicTableInstance('Security.SecurityFunctions');

                    $SecurityFunctions = self::getDynamicTableInstance('Security.SecurityFunctions');
                    $SecurityFunctionsGenerateAllData = $SecurityFunctions
                        ->find()
                        ->where([
                            $SecurityFunctions->aliasField('name') => 'Cumulative Gpa Generate All'])
                        ->first();

                    $SecurityRoleFunctionsTable = self::getDynamicTableInstance('Security.SecurityRoleFunctions');
                    $SecurityRoleFunctionsTableGenerateAllData = $SecurityRoleFunctionsTable
                        ->find()
                        ->where([
                            $SecurityRoleFunctionsTable->aliasField('security_function_id') => $SecurityFunctionsGenerateAllData->id,
                            $SecurityRoleFunctionsTable->aliasField('_execute') => 1,
                            $SecurityRoleFunctionsTable->aliasField('security_role_id IN') => $securityRoleIds])
                        ->count();

                    // Generate all button
                    $generateButton['url'] = $this->setQueryString($this->url('generateAll'), $params);
                    $generateButton['type'] = 'button';
                    $generateButton['label'] = '<i class="fa fa-refresh"></i>';
                    $generateButton['attr'] = $toolbarAttr;
                    $generateButton['attr']['title'] = __('Generate All');
                    //$ReportCards = self::getDynamicTableInstance('ReportCard.ReportCards');
                    if (!is_null($this->request->getQuery('education_grade_id'))) {
                        $educationGradeId = $this->request->getQuery('education_grade_id');
                    }

                    $ReportCardsData = $this->ReportCards
                        ->find()
                        ->where([
                            $this->ReportCards->aliasField('education_grade_id') => $educationGradeId])
                        ->first();
                    if (!empty($ReportCardsData->generate_start_date)) {
                        $generateStartDate = $ReportCardsData->generate_start_date->format('Y-m-d');
                    }

                    if (!empty($ReportCardsData->generate_end_date)) {
                        $generateEndDate = $ReportCardsData->generate_end_date->format('Y-m-d');
                    }
                    $date = Time::now()->format('Y-m-d');

                    if ($this->AccessControl->isAdmin()) {
                        if (!empty($generateStartDate) && !empty($generateEndDate) && $date >= $generateStartDate && $date <= $generateEndDate) {
                            $extra['toolbarButtons']['generateAll'] = $generateButton;
                        } else {
                            $generateButton['attr']['data-html'] = true;
                            $generateButton['attr']['title'] .= __('<br>' . $this->getMessage('ReportCardStatuses.date_closed'));
                            $generateButton['url'] = 'javascript:void(0)';
                            $extra['toolbarButtons']['generateAll'] = $generateButton;
                        }
                    } else {

                        $ExcludedSecurityRoleEntity = $this->canGenerateAnyDate();
                        if ($SecurityRoleFunctionsTableGenerateAllData >= 1) {
                            if ((!empty($generateStartDate) && !empty($generateEndDate) && $date >= $generateStartDate && $date <= $generateEndDate) || ($ExcludedSecurityRoleEntity == 1)) {
                                $extra['toolbarButtons']['generateAll'] = $generateButton;
                            } else {
                                $generateButton['attr']['data-html'] = true;
                              //  $generateButton['attr']['title'] .= __('<br>' . $this->getMessage('ReportCardStatuses.date_closed'));
                                //$generateButton['url'] = 'javascript:void(0)';
                                $extra['toolbarButtons']['generateAll'] = $generateButton;
                            }
                        }
                    }

                }
            }
        }
    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'student_id';
        $searchableFields[] = 'openemis_no';
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id');
        //$this->field('institution_class_id', ['visible' => true]);
        $this->field('institution_class', ['visible' => true]);
        $this->field('student_status_id', ['visible' => false]);
        $this->field('next_institution_class_id', ['visible' => false]);
        $this->field('cumulative_gpa', ['visible' => true]);
        $this->field('openemis_no', ['sort' => ['field' => 'Users.openemis_no']]);
        $this->field('student_name');
        $this->setFieldOrder(['academic_period_id', 'institution_class', 'openemis_no', 'student_name', 'cumulative_gpa']);
    }

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {

        $value = '';
        if ($entity->has('user')) {
            $value = $entity->user->openemis_no;
        }
        return $value;
    }

    public function generate(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
//        Log::debug(print_r([$params['student_id'],
//            $params['academic_period_id'],
//            $params['institution_id'],
//            $params['education_grade_id']],true));
        if ($params) {
            self::addGpaReportCards($params['student_id'],
                $params['academic_period_id'],
                $params['institution_id'],
                $params['education_grade_id']); // POCOR-9162
            $this->Alert->success('ReportCardStatuses.gpa');
        } else {
            $url = $this->url('index');
            $this->Alert->warning('ReportCardStatuses.noTemplate');
        }

        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function generateAll(Event $event, ArrayObject $extra)
    {

        $params = $this->getQueryString();
        $institutionId = $this->getInstitutionID();
        $params['academic_period_id'] = $this->request->getQuery('academic_period_id');
        $params['institution_class_id'] = $this->request->getQuery('class_id');
        $params['education_grade_id'] = $this->request->getQuery('education_grade_id');
        $selectedAcademicPeriodId = $params['academic_period_id'];

        if ($params) {
            $fetchAllRecord = $this->find()
            ->select([
                'student_id' => $this->aliasField('student_id'),
                'education_grade_id' => $this->aliasField('education_grade_id'),
            ])
            ->where(['institution_id' => $institutionId ,
                'institution_class_id IS' => $params['institution_class_id'],
                'academic_period_id' => $params['academic_period_id']])->toArray();
            foreach($fetchAllRecord as $value){
                $studentId = $value['student_id'];
                $educationGradeId = $params['education_grade_id'];
                self::addGpaReportCards($studentId,
                    $selectedAcademicPeriodId,
                    $institutionId,
                    $educationGradeId);
            }
            $this->Alert->success('ReportCardStatuses.gpa');
        } else {
            $this->Alert->warning('ReportCardStatuses.noTemplate');
        }

        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    // add Cumulative GPA for student
    private function addGpaReportCardsOld($checkgpaStudent,$selectedAcademicPeriodId, $institutionId,$educationGradeId)
    {
//        $selectedAcademicPeriodId = $selectedAcademicPeriodId;
//        $reportCardId = $reportCardId;
//        $institutionId = $institutionId;
//        $educationGradeId = $educationGradeId;
        $studentId = $checkgpaStudent;
        $this->AcademicPeriods = self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods');
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $gpaTable = self::getDynamicTableInstance('Institution.InstitutionStudentsGpa');
        $recordExist = $gpaTable->find()->select(['id'])->where([$gpaTable->aliasField('institution_id') => $institutionId, $gpaTable->aliasField('student_id') => $studentId,$gpaTable->aliasField('academic_period_id') => $selectedAcademicPeriodId,$gpaTable->aliasField('education_grade_id') => $educationGradeId])->first();
        $loginUserId = $this->Auth->user()['id'];
        $connection = ConnectionManager::get('default');
        if(empty($recordExist)){

            $statement = $connection->prepare("INSERT INTO `institution_students_gpa` (`student_id`,
                `institution_id`,
                `academic_period_id`,
                             `education_grade_id`,
                             `education_grades_gpa_id`
                             , `cumulative_gpa`,
                             `created_user_id`,
                             `created`)
                            SELECT main_q.student_id
                                ,main_q.institution_id
                                ,main_q.academic_period_id
                                ,main_q.education_grade_id
                                ,ind_gpa.education_grades_gpa_id
                                ,IFNULL(cum_gpa.cum_gpa_per_student, 0.00) cumulative_gpa
                                ,$loginUserId AS created_user_id -- TO MAKE IT DYNAMIC BASED ON USER_ID WHO GENERATES THE GPA
                                ,CURRENT_TIMESTAMP() created
                            FROM
                            (
                                SELECT institution_students.student_id
                                    ,institution_students.institution_id
                                    ,institution_students.education_grade_id
                                    ,institution_students.academic_period_id
                                FROM institution_students
                                INNER JOIN academic_periods
                                ON academic_periods.id = institution_students.academic_period_id
                                WHERE institution_students.academic_period_id = $selectedAcademicPeriodId
                                AND institution_students.student_id = $studentId
                                AND institution_students.institution_id = $institutionId
                                AND IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_students.student_status_id = 1, institution_students.student_status_id IN (1, 7, 6, 8))
                            ) main_q
                            INNER JOIN
                            (
                                SELECT  subq.academic_period_id
                                       ,subq.education_grade_id
                                       ,education_grades_gpa.id education_grades_gpa_id
                                       ,subq.assessment_period_start_date
                                       ,subq.assessment_period_end_date
                                       ,subq.institution_id
                                       ,subq.student_id
                                       ,ROUND(AVG(IFNULL(gpa_grading_options.point, 0)), 2) gpa_per_student
                                FROM
                                (
                                    SELECT  institution_subject_students.academic_period_id
                                           ,institution_subject_students.institution_id
                                           ,institution_subject_students.education_grade_id
                                           ,institution_subject_students.education_subject_id
                                           ,institution_subject_students.student_id
                                           ,term_info.academic_term
                                           ,term_info.assessment_period_start_date
                                           ,term_info.assessment_period_end_date
                                           ,IFNULL(subq2.total_mark,0) total_mark
                                    FROM institution_subject_students
                                    INNER JOIN
                                    (
                                        SELECT  assessments.academic_period_id
                                               ,assessments.education_grade_id
                                               ,IFNULL(assessment_periods.academic_term, 1) academic_term
                                               ,MIN(assessment_periods.start_date) assessment_period_start_date
                                               ,MAX(assessment_periods.end_date) assessment_period_end_date
                                        FROM assessment_periods
                                        INNER JOIN assessments
                                        ON assessments.id = assessment_periods.assessment_id
                                        WHERE assessments.academic_period_id = $selectedAcademicPeriodId
                                        GROUP BY  assessments.academic_period_id
                                                 ,assessments.education_grade_id
                                                 ,IFNULL(assessment_periods.academic_term, 1)
                                    ) term_info
                                    ON term_info.academic_period_id = institution_subject_students.academic_period_id
                                    AND term_info.education_grade_id = institution_subject_students.education_grade_id
                                    LEFT JOIN
                                    (
                                        SELECT  assessment_item_results.academic_period_id
                                               ,assessment_item_results.institution_id
                                               ,assessment_item_results.education_grade_id
                                               ,assessment_item_results.education_subject_id
                                               ,assessment_item_results.student_id
                                               ,IFNULL(assessment_periods.academic_term, 1) AS academic_term
                                               ,IFNULL( ROUND( SUM(assessment_item_results.marks * assessment_periods.weight) / SUM(assessment_periods.weight),2 ),'' ) AS total_mark
                                        FROM assessment_item_results
                                        INNER JOIN
                                        (
                                            SELECT  assessment_item_results.academic_period_id
                                                   ,assessment_item_results.institution_id
                                                   ,assessment_item_results.education_grade_id
                                                   ,assessment_item_results.student_id
                                                   ,assessment_item_results.assessment_id
                                                   ,assessment_item_results.education_subject_id
                                                   ,assessment_item_results.assessment_period_id
                                                   ,MAX(assessment_item_results.created) latest_created
                                            FROM assessment_item_results
                                            WHERE assessment_item_results.academic_period_id = $selectedAcademicPeriodId
                                            AND assessment_item_results.student_id = $studentId
                                            GROUP BY  assessment_item_results.academic_period_id
                                                     ,assessment_item_results.education_grade_id
                                                     ,assessment_item_results.student_id
                                                     ,assessment_item_results.assessment_id
                                                     ,assessment_item_results.education_subject_id
                                                     ,assessment_item_results.assessment_period_id
                                        ) latest_grades
                                        ON latest_grades.academic_period_id = assessment_item_results.academic_period_id
                                        AND latest_grades.education_grade_id = assessment_item_results.education_grade_id
                                        AND latest_grades.student_id = assessment_item_results.student_id
                                        AND latest_grades.assessment_id = assessment_item_results.assessment_id
                                        AND latest_grades.education_subject_id = assessment_item_results.education_subject_id
                                        AND latest_grades.assessment_period_id = assessment_item_results.assessment_period_id
                                        AND latest_grades.latest_created = assessment_item_results.created
                                        INNER JOIN assessment_periods
                                        ON assessment_periods.id = assessment_item_results.assessment_period_id
                                        INNER JOIN education_subjects
                                        ON education_subjects.id = assessment_item_results.education_subject_id
                                        LEFT JOIN
                                        (
                                            SELECT assessment_item_student_exemptions.assessment_id
                                                ,assessment_item_student_exemptions.education_subject_id
                                                ,assessment_item_student_exemptions.student_id
                                                ,assessment_item_student_exemptions.institution_class_id
                                                ,assessment_item_student_exemptions.education_grade_id
                                                ,assessment_item_student_exemptions.assessment_period_id
                                            FROM assessment_item_student_exemptions
                                            INNER JOIN assessments
                                            ON assessments.id = assessment_item_student_exemptions.assessment_id
                                            WHERE assessments.academic_period_id = $selectedAcademicPeriodId
                                            AND assessment_item_student_exemptions.student_id = $studentId
                                        ) exemption_details
                                        ON exemption_details.assessment_id = assessment_item_results.assessment_id
                                        AND exemption_details.education_subject_id = assessment_item_results.education_subject_id
                                        AND exemption_details.student_id = assessment_item_results.student_id
                                        AND exemption_details.institution_class_id = assessment_item_results.institution_classes_id
                                        AND exemption_details.education_grade_id = assessment_item_results.education_grade_id
                                        AND exemption_details.assessment_period_id = assessment_item_results.assessment_period_id
                                        WHERE assessment_item_results.academic_period_id = $selectedAcademicPeriodId
                                        AND assessment_item_results.student_id = $studentId
                                        AND exemption_details.assessment_id IS NULL
                                        GROUP BY  assessment_item_results.academic_period_id
                                                 ,assessment_item_results.education_grade_id
                                                 ,assessment_item_results.education_subject_id
                                                 ,assessment_item_results.student_id
                                                 ,assessment_periods.academic_term
                                    ) subq2
                                    ON subq2.academic_period_id = institution_subject_students.academic_period_id
                                    AND subq2.education_grade_id = institution_subject_students.education_grade_id
                                    AND subq2.student_id = institution_subject_students.student_id
                                    AND subq2.education_subject_id = institution_subject_students.education_subject_id
                                    AND subq2.academic_term = term_info.academic_term
                                    WHERE institution_subject_students.academic_period_id = $selectedAcademicPeriodId
                                    AND institution_subject_students.student_id = $studentId
                                    AND institution_subject_students.institution_id = $institutionId
                                    GROUP BY  institution_subject_students.academic_period_id
                                             ,institution_subject_students.education_grade_id
                                             ,institution_subject_students.education_subject_id
                                             ,institution_subject_students.student_id
                                             ,term_info.academic_term
                                ) subq
                                INNER JOIN education_grades_gpa
                                ON subq.assessment_period_end_date BETWEEN education_grades_gpa.start_date AND education_grades_gpa.end_date
                                AND education_grades_gpa.academic_period_id = subq.academic_period_id
                                AND education_grades_gpa.education_grade_id = subq.education_grade_id
                                LEFT JOIN gpa_grading_options
                                ON subq.total_mark >= gpa_grading_options.min
                                AND subq.total_mark <= gpa_grading_options.max
                                AND education_grades_gpa.gpa_grading_type_id = gpa_grading_options.gpa_grading_type_id
                                GROUP BY  subq.academic_period_id
                                         ,subq.institution_id
                                         ,subq.education_grade_id
                                         ,subq.student_id
                                         ,education_grades_gpa.id
                            ) ind_gpa
                            ON ind_gpa.student_id = main_q.student_id
                            AND ind_gpa.institution_id = main_q.institution_id
                            AND ind_gpa.academic_period_id = main_q.academic_period_id
                            AND ind_gpa.education_grade_id = main_q.education_grade_id
                            LEFT JOIN
                            (
                                SELECT students_gpa.student_id
                                    ,students_gpa.institution_id
                                    ,current_academic_period.academic_period_id
                                    ,MAX(student_education_grades.id) education_grade_id
                                    ,ROUND(AVG(IFNULL(students_gpa.gpa, 0)), 2) cum_gpa_per_student
                                FROM
                                (
                                    SELECT institution_students_gpa.institution_id
                                        ,institution_students_gpa.academic_period_id
                                        ,institution_students_gpa.education_grade_id
                                        ,institution_students_gpa.student_id
                                        ,AVG(institution_students_gpa.gpa) gpa
                                    FROM institution_students_gpa
                                    WHERE institution_students_gpa.student_id = $studentId
                                    GROUP BY institution_students_gpa.institution_id
                                        ,institution_students_gpa.academic_period_id
                                        ,institution_students_gpa.education_grade_id
                                ) students_gpa
                                INNER JOIN education_grades student_education_grades
                                ON student_education_grades.id = students_gpa.education_grade_id
                                INNER JOIN
                                (
                                    SELECT academic_periods.id academic_period_id
                                    FROM academic_periods
                                    WHERE academic_periods.id = $selectedAcademicPeriodId
                                ) current_academic_period
                                INNER JOIN
                                (
                                    SELECT education_grades_cumulative_gpa.main_education_grade_id
                                        ,education_grades_gpa.academic_period_id
                                        ,education_grades.code education_grade_code
                                    FROM education_grades_gpa
                                    INNER JOIN education_grades_cumulative_gpa
                                    ON education_grades_cumulative_gpa.main_education_grade_id = education_grades_gpa.education_grade_id
                                    INNER JOIN education_grades
                                    ON education_grades.id = education_grades_cumulative_gpa.education_grade_id
                                    GROUP BY education_grades_cumulative_gpa.main_education_grade_id
                                        ,education_grades_cumulative_gpa.education_grade_id
                                ) last_year_grades
                                ON last_year_grades.academic_period_id = current_academic_period.academic_period_id
                                AND last_year_grades.education_grade_code = student_education_grades.code
                                WHERE students_gpa.student_id = $studentId
                                GROUP BY students_gpa.student_id
                                    ,students_gpa.institution_id
                                    ,current_academic_period.academic_period_id
                                    ,last_year_grades.main_education_grade_id
                            ) cum_gpa
                            ON cum_gpa.academic_period_id = main_q.academic_period_id
                            AND cum_gpa.education_grade_id = main_q.education_grade_id
                            AND cum_gpa.institution_id = main_q.institution_id
                            AND cum_gpa.student_id = main_q.student_id
                            LEFT JOIN institution_students_gpa
                            ON institution_students_gpa.student_id = main_q.student_id
                            AND institution_students_gpa.institution_id = main_q.institution_id
                            AND institution_students_gpa.academic_period_id = main_q.academic_period_id
                            AND institution_students_gpa.education_grade_id = main_q.education_grade_id
                            WHERE institution_students_gpa.institution_id IS NULL
                            GROUP BY main_q.student_id
                                ,main_q.institution_id
                                ,main_q.academic_period_id
                                ,main_q.education_grade_id
                                ,ind_gpa.education_grades_gpa_id;");
                        $statement->execute();
            //echo "<pre>"; print_r($statement); die;
        }else{
          //  die('else');
            $statement = $connection->prepare("UPDATE institution_students_gpa
                            INNER JOIN
                            (
                                SELECT main_q.student_id
                                    ,main_q.institution_id
                                    ,main_q.academic_period_id
                                    ,main_q.education_grade_id
                                    ,ind_gpa.education_grades_gpa_id
                                    ,IFNULL(cum_gpa.cum_gpa_per_student, 0.00) cumulative_gpa
                                    ,$loginUserId AS modified_user_id -- TO MAKE IT DYNAMIC BASED ON USER_ID WHO GENERATES THE GPA
                                    ,CURRENT_TIMESTAMP() created
                                FROM
                                (
                                    SELECT institution_students.student_id
                                        ,institution_students.institution_id
                                        ,institution_students.education_grade_id
                                        ,institution_students.academic_period_id
                                    FROM institution_students
                                    INNER JOIN academic_periods
                                    ON academic_periods.id = institution_students.academic_period_id
                                    WHERE institution_students.academic_period_id = $selectedAcademicPeriodId
                                    AND institution_students.student_id = $studentId
                                    AND institution_students.institution_id = $institutionId
                                    AND IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_students.student_status_id = 1, institution_students.student_status_id IN (1, 7, 6, 8))
                                ) main_q
                                INNER JOIN
                                (
                                    SELECT  subq.academic_period_id
                                        ,subq.education_grade_id
                                        ,education_grades_gpa.id education_grades_gpa_id
                                        ,subq.assessment_period_start_date
                                        ,subq.assessment_period_end_date
                                        ,subq.institution_id
                                        ,subq.student_id
                                        ,ROUND(AVG(IFNULL(gpa_grading_options.point, 0)), 2) gpa_per_student
                                    FROM
                                    (
                                        SELECT  institution_subject_students.academic_period_id
                                            ,institution_subject_students.institution_id
                                            ,institution_subject_students.education_grade_id
                                            ,institution_subject_students.education_subject_id
                                            ,institution_subject_students.student_id
                                            ,term_info.academic_term
                                            ,term_info.assessment_period_start_date
                                            ,term_info.assessment_period_end_date
                                            ,IFNULL(subq2.total_mark,0) total_mark
                                        FROM institution_subject_students
                                        INNER JOIN
                                        (
                                            SELECT  assessments.academic_period_id
                                                ,assessments.education_grade_id
                                                ,IFNULL(assessment_periods.academic_term, 1) academic_term
                                                ,MIN(assessment_periods.start_date) assessment_period_start_date
                                                ,MAX(assessment_periods.end_date) assessment_period_end_date
                                            FROM assessment_periods
                                            INNER JOIN assessments
                                            ON assessments.id = assessment_periods.assessment_id
                                            WHERE assessments.academic_period_id = $selectedAcademicPeriodId
                                            GROUP BY  assessments.academic_period_id
                                                    ,assessments.education_grade_id
                                                    ,IFNULL(assessment_periods.academic_term, 1)
                                        ) term_info
                                        ON term_info.academic_period_id = institution_subject_students.academic_period_id
                                        AND term_info.education_grade_id = institution_subject_students.education_grade_id
                                        LEFT JOIN
                                        (
                                            SELECT  assessment_item_results.academic_period_id
                                                ,assessment_item_results.institution_id
                                                ,assessment_item_results.education_grade_id
                                                ,assessment_item_results.education_subject_id
                                                ,assessment_item_results.student_id
                                                ,IFNULL(assessment_periods.academic_term, 1) AS academic_term
                                                ,IFNULL( ROUND( SUM(assessment_item_results.marks * assessment_periods.weight) / SUM(assessment_periods.weight),2 ),'' ) AS total_mark
                                            FROM assessment_item_results
                                            INNER JOIN
                                            (
                                                SELECT  assessment_item_results.academic_period_id
                                                    ,assessment_item_results.institution_id
                                                    ,assessment_item_results.education_grade_id
                                                    ,assessment_item_results.student_id
                                                    ,assessment_item_results.assessment_id
                                                    ,assessment_item_results.education_subject_id
                                                    ,assessment_item_results.assessment_period_id
                                                    ,MAX(assessment_item_results.created) latest_created
                                                FROM assessment_item_results
                                                WHERE assessment_item_results.academic_period_id = $selectedAcademicPeriodId
                                                AND assessment_item_results.student_id = $studentId
                                                GROUP BY  assessment_item_results.academic_period_id
                                                        ,assessment_item_results.education_grade_id
                                                        ,assessment_item_results.student_id
                                                        ,assessment_item_results.assessment_id
                                                        ,assessment_item_results.education_subject_id
                                                        ,assessment_item_results.assessment_period_id
                                            ) latest_grades
                                            ON latest_grades.academic_period_id = assessment_item_results.academic_period_id
                                            AND latest_grades.education_grade_id = assessment_item_results.education_grade_id
                                            AND latest_grades.student_id = assessment_item_results.student_id
                                            AND latest_grades.assessment_id = assessment_item_results.assessment_id
                                            AND latest_grades.education_subject_id = assessment_item_results.education_subject_id
                                            AND latest_grades.assessment_period_id = assessment_item_results.assessment_period_id
                                            AND latest_grades.latest_created = assessment_item_results.created
                                            INNER JOIN assessment_periods
                                            ON assessment_periods.id = assessment_item_results.assessment_period_id
                                            INNER JOIN education_subjects
                                            ON education_subjects.id = assessment_item_results.education_subject_id
                                            LEFT JOIN
                                            (
                                                SELECT assessment_item_student_exemptions.assessment_id
                                                    ,assessment_item_student_exemptions.education_subject_id
                                                    ,assessment_item_student_exemptions.student_id
                                                    ,assessment_item_student_exemptions.institution_class_id
                                                    ,assessment_item_student_exemptions.education_grade_id
                                                    ,assessment_item_student_exemptions.assessment_period_id
                                                FROM assessment_item_student_exemptions
                                                INNER JOIN assessments
                                                ON assessments.id = assessment_item_student_exemptions.assessment_id
                                                WHERE assessments.academic_period_id = $selectedAcademicPeriodId
                                                AND assessment_item_student_exemptions.student_id = $studentId
                                            ) exemption_details
                                            ON exemption_details.assessment_id = assessment_item_results.assessment_id
                                            AND exemption_details.education_subject_id = assessment_item_results.education_subject_id
                                            AND exemption_details.student_id = assessment_item_results.student_id
                                            AND exemption_details.institution_class_id = assessment_item_results.institution_classes_id
                                            AND exemption_details.education_grade_id = assessment_item_results.education_grade_id
                                            AND exemption_details.assessment_period_id = assessment_item_results.assessment_period_id
                                            WHERE assessment_item_results.academic_period_id = $selectedAcademicPeriodId
                                            AND assessment_item_results.student_id = $studentId
                                            AND exemption_details.assessment_id IS NULL
                                            GROUP BY  assessment_item_results.academic_period_id
                                                    ,assessment_item_results.education_grade_id
                                                    ,assessment_item_results.education_subject_id
                                                    ,assessment_item_results.student_id
                                                    ,assessment_periods.academic_term
                                        ) subq2
                                        ON subq2.academic_period_id = institution_subject_students.academic_period_id
                                        AND subq2.education_grade_id = institution_subject_students.education_grade_id
                                        AND subq2.student_id = institution_subject_students.student_id
                                        AND subq2.education_subject_id = institution_subject_students.education_subject_id
                                        AND subq2.academic_term = term_info.academic_term
                                        WHERE institution_subject_students.academic_period_id = $selectedAcademicPeriodId
                                        AND institution_subject_students.student_id = $studentId
                                        AND institution_subject_students.institution_id = $institutionId
                                        GROUP BY  institution_subject_students.academic_period_id
                                                ,institution_subject_students.education_grade_id
                                                ,institution_subject_students.education_subject_id
                                                ,institution_subject_students.student_id
                                                ,term_info.academic_term
                                    ) subq
                                    INNER JOIN education_grades_gpa
                                    ON subq.assessment_period_end_date BETWEEN education_grades_gpa.start_date AND education_grades_gpa.end_date
                                    AND education_grades_gpa.academic_period_id = subq.academic_period_id
                                    AND education_grades_gpa.education_grade_id = subq.education_grade_id
                                    LEFT JOIN gpa_grading_options
                                    ON subq.total_mark >= gpa_grading_options.min
                                    AND subq.total_mark <= gpa_grading_options.max
                                    AND education_grades_gpa.gpa_grading_type_id = gpa_grading_options.gpa_grading_type_id
                                    GROUP BY  subq.academic_period_id
                                            ,subq.institution_id
                                            ,subq.education_grade_id
                                            ,subq.student_id
                                            ,education_grades_gpa.id
                                ) ind_gpa
                                ON ind_gpa.student_id = main_q.student_id
                                AND ind_gpa.institution_id = main_q.institution_id
                                AND ind_gpa.academic_period_id = main_q.academic_period_id
                                AND ind_gpa.education_grade_id = main_q.education_grade_id
                                LEFT JOIN
                                (
                                    SELECT students_gpa.student_id
                                        ,students_gpa.institution_id
                                        ,current_academic_period.academic_period_id
                                        ,MAX(student_education_grades.id) education_grade_id
                                        ,ROUND(AVG(IFNULL(students_gpa.gpa, 0)), 2) cum_gpa_per_student
                                    FROM
                                    (
                                        SELECT institution_students_gpa.institution_id
                                            ,institution_students_gpa.academic_period_id
                                            ,institution_students_gpa.education_grade_id
                                            ,institution_students_gpa.student_id
                                            ,AVG(institution_students_gpa.gpa) gpa
                                        FROM institution_students_gpa
                                        WHERE institution_students_gpa.student_id = $studentId
                                        GROUP BY institution_students_gpa.institution_id
                                            ,institution_students_gpa.academic_period_id
                                            ,institution_students_gpa.education_grade_id
                                    ) students_gpa
                                    INNER JOIN education_grades student_education_grades
                                    ON student_education_grades.id = students_gpa.education_grade_id
                                    INNER JOIN
                                    (
                                        SELECT academic_periods.id academic_period_id
                                        FROM academic_periods
                                        WHERE academic_periods.id = $selectedAcademicPeriodId
                                    ) current_academic_period
                                    INNER JOIN
                                    (
                                        SELECT education_grades_cumulative_gpa.main_education_grade_id
                                            ,education_grades_gpa.academic_period_id
                                            ,education_grades.code education_grade_code
                                        FROM education_grades_gpa
                                        INNER JOIN education_grades_cumulative_gpa
                                        ON education_grades_cumulative_gpa.main_education_grade_id = education_grades_gpa.education_grade_id
                                        INNER JOIN education_grades
                                        ON education_grades.id = education_grades_cumulative_gpa.education_grade_id
                                        GROUP BY education_grades_cumulative_gpa.main_education_grade_id
                                            ,education_grades_cumulative_gpa.education_grade_id
                                    ) last_year_grades
                                    ON last_year_grades.academic_period_id = current_academic_period.academic_period_id
                                    AND last_year_grades.education_grade_code = student_education_grades.code
                                    WHERE students_gpa.student_id = $studentId
                                    GROUP BY students_gpa.student_id
                                        ,students_gpa.institution_id
                                        ,current_academic_period.academic_period_id
                                        ,last_year_grades.main_education_grade_id
                                ) cum_gpa
                                ON cum_gpa.academic_period_id = main_q.academic_period_id
                                AND cum_gpa.education_grade_id = main_q.education_grade_id
                                AND cum_gpa.institution_id = main_q.institution_id
                                AND cum_gpa.student_id = main_q.student_id
                                GROUP BY main_q.student_id
                                    ,main_q.institution_id
                                    ,main_q.academic_period_id
                                    ,main_q.education_grade_id
                                    ,ind_gpa.education_grades_gpa_id
                            ) subq4
                            ON subq4.student_id = institution_students_gpa.student_id
                            AND subq4.institution_id = institution_students_gpa.institution_id
                            AND subq4.academic_period_id = institution_students_gpa.academic_period_id
                            AND subq4.education_grade_id = institution_students_gpa.education_grade_id
                            AND subq4.education_grades_gpa_id = institution_students_gpa.education_grades_gpa_id
                            SET institution_students_gpa.cumulative_gpa = subq4.cumulative_gpa
                            ,institution_students_gpa.modified_user_id = $loginUserId,institution_students_gpa.modified = CURRENT_TIMESTAMP();");
                            $statement->execute();
            //echo "<pre>"; print_r($statement); die;
        }

    }

    //New add Cumulative GPA for student with $educationGradeId -- POCOR-8962
    public static function addGpaReportCards($checkgpaStudent, // POCOR-9162
                                             $selectedAcademicPeriodId,
                                             $institutionId,
                                             $educationGradeId): array
    {
        // POCOR-9162 start
        $studentId = $checkgpaStudent;
        $academicPeriodId = $selectedAcademicPeriodId;
        $gpaGrades = self::getDynamicTableInstance('Gpa.GpaSystem');
        $nameOption = $gpaGrades->find()
            ->distinct(['id'])
            ->select(['id'])
            ->where([
                $gpaGrades->aliasField('academic_period_id') => $academicPeriodId,
                $gpaGrades->aliasField('education_grade_id') => $educationGradeId
            ])
            ->toArray();
        $gpaIds = array_column($nameOption, 'id');
        $gpaGPAs = [];
        foreach ($gpaIds as $gpaId) { // POCOR-9177 FIRST CALCULATE ALL GPA
//            Log::debug('GPA ID: ' . $gpaId);
            $newGPA = ReportCardGpaTable::insertGpaPerStudentPerGpa( // POCOR-9162
                $institutionId,
                $studentId,
                $academicPeriodId,
                $educationGradeId,
                $gpaId);
            $gpaGPAs[] = $newGPA;
        }
        foreach ($gpaIds as $gpaId) { // POCOR-9177 THEN CALCULATE ALL CUM_GPA
//            Log::debug('GPA ID: ' . $gpaId);
            $newGPA = self::insertCumulativeGpaPerStudentPerGpa( // POCOR-9162
                $institutionId,
                $studentId,
                $academicPeriodId,
                $educationGradeId,
                $gpaId);
            $gpaGPAs[] = $newGPA;
        }
//        Log::debug(print_r($gpaGPAs, true));
        return $gpaGPAs;
        // POCOR-9162 middle
////        $selectedAcademicPeriodId = $selectedAcademicPeriodId;
////
////        $institutionId = $institutionId;
////        $educationGradeId = $educationGradeId;
//        $studentId = $checkgpaStudent;
////        $AcademicPeriods = self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods');
////        $academicPeriodOptions = $AcademicPeriods->getYearList(['isEditable' => true]);
//        $gpaTable = self::getDynamicTableInstance('Institution.InstitutionStudentsGpa');
//        // POCOR-9162 start
//        $recordExist = $gpaTable->find()->select(['id'])->where([
//            $gpaTable->aliasField('institution_id') => $institutionId,
//            $gpaTable->aliasField('student_id') => $studentId,
//            $gpaTable->aliasField('academic_period_id') => $selectedAcademicPeriodId,
//            $gpaTable->aliasField('education_grade_id') => $educationGradeId])
//            ->first();
//        $session = new Session();
//        if (is_null($session->read('Auth.User.id'))) {
//            $loginUserId = 1;    // Super Admin
//        } else {
//            $loginUserId = $session->read('Auth.User.id');
//        }
//        // POCOR-9162 end
//        $connection = ConnectionManager::get('default');
//        if(empty($recordExist)){
//
//        $statement = $connection->prepare("INSERT INTO `institution_students_gpa` (`student_id`, `institution_id`, `academic_period_id`, `education_grade_id`, `education_grades_gpa_id`, `cumulative_gpa`, `created_user_id`, `created`)
//        SELECT main_q.student_id
//            ,main_q.institution_id
//            ,main_q.academic_period_id
//            ,main_q.education_grade_id
//            ,ind_gpa.education_grades_gpa_id
//            ,IFNULL(cum_gpa.cum_gpa_per_student, 0.00) cum_gpa
//            ,$loginUserId AS created_user_id -- TO MAKE IT DYNAMIC BASED ON USER_ID WHO GENERATES THE GPA
//            , CURRENT_TIMESTAMP() created
//        FROM
//        (
//            SELECT institution_students.student_id
//                ,institution_students.institution_id
//                ,institution_students.education_grade_id
//                ,institution_students.academic_period_id
//            FROM institution_students
//            INNER JOIN academic_periods
//            ON academic_periods.id = institution_students.academic_period_id
//            WHERE institution_students.academic_period_id = $selectedAcademicPeriodId
//            AND institution_students.student_id = $studentId
//            AND institution_students.institution_id = $institutionId
//            AND IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_students.student_status_id = 1, institution_students.student_status_id IN (1, 7, 6, 8))
//        ) main_q
//        INNER JOIN
//        (
//            SELECT  subq.academic_period_id
//                ,subq.education_grade_id
//                ,subq.assessment_period_start_date
//                ,subq.assessment_period_end_date
//                ,subq.institution_id
//                ,education_grades_gpa.id education_grades_gpa_id
//                ,subq.student_id
//                ,ROUND(AVG(IFNULL(gpa_grading_options.point, 0)), 2) gpa_per_student
//            FROM
//            (
//                SELECT  institution_subject_students.academic_period_id
//                    ,institution_subject_students.institution_id
//                    ,institution_subject_students.education_grade_id
//                    ,institution_subject_students.education_subject_id
//                    ,institution_subject_students.student_id
//                    ,term_info.academic_term
//                    ,term_info.assessment_period_start_date
//                    ,term_info.assessment_period_end_date
//                    ,IFNULL(subq2.total_mark,0) total_mark
//                FROM institution_subject_students
//                INNER JOIN
//                (
//                    SELECT  assessments.academic_period_id
//                        ,assessments.education_grade_id
//                        ,IFNULL(assessment_periods.academic_term, 1) academic_term
//                        ,MIN(assessment_periods.start_date) assessment_period_start_date
//                        ,MAX(assessment_periods.end_date) assessment_period_end_date
//                    FROM assessment_periods
//                    INNER JOIN assessments
//                    ON assessments.id = assessment_periods.assessment_id
//                    WHERE assessments.academic_period_id = $selectedAcademicPeriodId
//                    GROUP BY  assessments.academic_period_id
//                            ,assessments.education_grade_id
//                            ,IFNULL(assessment_periods.academic_term, 1)
//                ) term_info
//                ON term_info.academic_period_id = institution_subject_students.academic_period_id
//                AND term_info.education_grade_id = institution_subject_students.education_grade_id
//                LEFT JOIN
//                (
//                    SELECT  assessment_item_results.academic_period_id
//                        ,assessment_item_results.institution_id
//                        ,assessment_item_results.education_grade_id
//                        ,assessment_item_results.education_subject_id
//                        ,assessment_item_results.student_id
//                        ,IFNULL(assessment_periods.academic_term, 1) AS academic_term
//                        ,IFNULL( ROUND( SUM(assessment_item_results.marks * assessment_periods.weight) / SUM(assessment_periods.weight),2 ),'' ) AS total_mark
//                    FROM assessment_item_results
//                    INNER JOIN
//                    (
//                        SELECT  assessment_item_results.academic_period_id
//                            ,assessment_item_results.institution_id
//                            ,assessment_item_results.education_grade_id
//                            ,assessment_item_results.student_id
//                            ,assessment_item_results.assessment_id
//                            ,assessment_item_results.education_subject_id
//                            ,assessment_item_results.assessment_period_id
//                            ,MAX(assessment_item_results.created) latest_created
//                        FROM assessment_item_results
//                        WHERE assessment_item_results.academic_period_id = $selectedAcademicPeriodId
//                        AND assessment_item_results.student_id = $studentId
//                        GROUP BY  assessment_item_results.academic_period_id
//                                ,assessment_item_results.education_grade_id
//                                ,assessment_item_results.student_id
//                                ,assessment_item_results.assessment_id
//                                ,assessment_item_results.education_subject_id
//                                ,assessment_item_results.assessment_period_id
//                    ) latest_grades
//                    ON latest_grades.academic_period_id = assessment_item_results.academic_period_id
//                    AND latest_grades.education_grade_id = assessment_item_results.education_grade_id
//                    AND latest_grades.student_id = assessment_item_results.student_id
//                    AND latest_grades.assessment_id = assessment_item_results.assessment_id
//                    AND latest_grades.education_subject_id = assessment_item_results.education_subject_id
//                    AND latest_grades.assessment_period_id = assessment_item_results.assessment_period_id
//                    AND latest_grades.latest_created = assessment_item_results.created
//                    INNER JOIN assessment_periods
//                    ON assessment_periods.id = assessment_item_results.assessment_period_id
//                    INNER JOIN education_subjects
//                    ON education_subjects.id = assessment_item_results.education_subject_id
//                    LEFT JOIN
//                    (
//                        SELECT assessment_item_student_exemptions.assessment_id
//                            ,assessment_item_student_exemptions.education_subject_id
//                            ,assessment_item_student_exemptions.student_id
//                            ,assessment_item_student_exemptions.institution_class_id
//                            ,assessment_item_student_exemptions.education_grade_id
//                            ,assessment_item_student_exemptions.assessment_period_id
//                        FROM assessment_item_student_exemptions
//                        INNER JOIN assessments
//                        ON assessments.id = assessment_item_student_exemptions.assessment_id
//                        WHERE assessments.academic_period_id = $selectedAcademicPeriodId
//                        AND assessment_item_student_exemptions.student_id = $studentId
//                    ) exemption_details
//                    ON exemption_details.assessment_id = assessment_item_results.assessment_id
//                    AND exemption_details.education_subject_id = assessment_item_results.education_subject_id
//                    AND exemption_details.student_id = assessment_item_results.student_id
//                    AND exemption_details.institution_class_id = assessment_item_results.institution_classes_id
//                    AND exemption_details.education_grade_id = assessment_item_results.education_grade_id
//                    AND exemption_details.assessment_period_id = assessment_item_results.assessment_period_id
//                    WHERE assessment_item_results.academic_period_id = $selectedAcademicPeriodId
//                    AND assessment_item_results.student_id = $studentId
//                    AND exemption_details.assessment_id IS NULL
//                    GROUP BY  assessment_item_results.academic_period_id
//                            ,assessment_item_results.education_grade_id
//                            ,assessment_item_results.education_subject_id
//                            ,assessment_item_results.student_id
//                            ,assessment_periods.academic_term
//                ) subq2
//                ON subq2.academic_period_id = institution_subject_students.academic_period_id
//                AND subq2.education_grade_id = institution_subject_students.education_grade_id
//                AND subq2.student_id = institution_subject_students.student_id
//                AND subq2.education_subject_id = institution_subject_students.education_subject_id
//                AND subq2.academic_term = term_info.academic_term
//                LEFT JOIN(
//                        SELECT institution_classes.academic_period_id,
//                            institution_classes.institution_id,
//                            assessment_item_student_exemptions.education_grade_id,
//                            assessment_item_student_exemptions.student_id,
//                            assessment_item_student_exemptions.education_subject_id,
//                            assessment_periods.academic_term
//                        FROM
//                            assessment_item_student_exemptions
//                        INNER JOIN assessment_periods ON assessment_periods.id = assessment_item_student_exemptions.assessment_period_id
//                        INNER JOIN institution_classes ON institution_classes.id = assessment_item_student_exemptions.institution_class_id
//                        WHERE
//                            institution_classes.academic_period_id = $selectedAcademicPeriodId
//                            AND institution_classes.institution_id = $institutionId
//                            AND assessment_item_student_exemptions.student_id = $studentId
//                        GROUP BY
//                            assessment_item_student_exemptions.education_subject_id,
//                            assessment_item_student_exemptions.student_id,
//                            assessment_item_student_exemptions.education_grade_id,
//                            assessment_periods.academic_term
//                    ) exemption_info
//                    ON
//                        exemption_info.academic_period_id = institution_subject_students.academic_period_id AND exemption_info.institution_id = institution_subject_students.institution_id AND exemption_info.education_grade_id = institution_subject_students.education_grade_id AND exemption_info.student_id = institution_subject_students.student_id AND exemption_info.education_subject_id = institution_subject_students.education_subject_id AND exemption_info.academic_term = term_info.academic_term
//                WHERE institution_subject_students.academic_period_id = $selectedAcademicPeriodId
//                AND institution_subject_students.student_id = $studentId
//                AND institution_subject_students.institution_id = $institutionId
//                AND exemption_info.academic_period_id IS NULL
//                GROUP BY  institution_subject_students.academic_period_id
//                        ,institution_subject_students.education_grade_id
//                        ,institution_subject_students.education_subject_id
//                        ,institution_subject_students.student_id
//                        ,term_info.academic_term
//            ) subq
//            INNER JOIN education_grades_gpa
//            ON subq.assessment_period_end_date BETWEEN education_grades_gpa.start_date AND education_grades_gpa.end_date
//            AND education_grades_gpa.academic_period_id = subq.academic_period_id
//            AND education_grades_gpa.education_grade_id = subq.education_grade_id
//            LEFT JOIN gpa_grading_options
//            ON subq.total_mark >= gpa_grading_options.min
//            AND subq.total_mark <= gpa_grading_options.max
//            AND education_grades_gpa.gpa_grading_type_id = gpa_grading_options.gpa_grading_type_id
//            GROUP BY  subq.academic_period_id
//                    ,subq.institution_id
//                    ,subq.education_grade_id
//                    ,subq.student_id
//        ) ind_gpa
//        ON ind_gpa.student_id = main_q.student_id
//        AND ind_gpa.institution_id = main_q.institution_id
//        AND ind_gpa.academic_period_id = main_q.academic_period_id
//        AND ind_gpa.education_grade_id = main_q.education_grade_id
//        LEFT JOIN
//        (
//            SELECT students_gpa.student_id
//                ,students_gpa.institution_id
//                ,current_academic_period.academic_period_id
//                ,MAX(students_gpa.education_grade_id) education_grade_id
//                ,ROUND(AVG(IFNULL(students_gpa.gpa, 0)), 2) cum_gpa_per_student
//            FROM
//            (
//                SELECT institution_students_gpa.institution_id
//                    ,institution_students_gpa.academic_period_id
//                    ,institution_students_gpa.education_grade_id
//                    ,education_grades.code education_grades_code
//                    ,institution_students_gpa.student_id
//                    ,institution_students_gpa.education_grades_gpa_id
//                    ,ROUND(AVG(institution_students_gpa.gpa), 2) gpa
//                FROM institution_students_gpa
//                INNER JOIN education_grades
//                ON education_grades.id = institution_students_gpa.education_grade_id
//                WHERE institution_students_gpa.student_id = $studentId
//                GROUP BY institution_students_gpa.institution_id
//                    ,institution_students_gpa.academic_period_id
//                    ,institution_students_gpa.education_grade_id
//                    ,institution_students_gpa.education_grades_gpa_id
//            ) students_gpa
//            INNER JOIN
//            (
//                SELECT academic_periods.id academic_period_id
//                    ,education_grades.id education_grade_id
//                FROM education_grades
//                INNER JOIN education_programmes
//                ON education_programmes.id = education_grades.education_programme_id
//                INNER JOIN education_cycles
//                ON education_cycles.id = education_programmes.education_cycle_id
//                INNER JOIN education_levels
//                ON education_levels.id = education_cycles.education_level_id
//                INNER JOIN education_systems
//                ON education_systems.id = education_levels.education_system_id
//                INNER JOIN academic_periods
//                ON academic_periods.id = education_systems.academic_period_id
//                WHERE academic_periods.id = $selectedAcademicPeriodId
//                AND education_grades.id = $educationGradeId
//            ) current_academic_period
//            INNER JOIN
//            (
//                SELECT education_grades_cumulative_gpa.main_education_grade_id
//                    ,education_systems.academic_period_id
//                    ,education_grades.code education_grade_code
//                FROM education_grades_cumulative_gpa
//                INNER JOIN education_grades
//                ON education_grades.id = education_grades_cumulative_gpa.education_grade_id
//                INNER JOIN education_programmes
//                ON education_programmes.id = education_grades.education_programme_id
//                INNER JOIN education_cycles
//                ON education_cycles.id = education_programmes.education_cycle_id
//                INNER JOIN education_levels
//                ON education_levels.id = education_cycles.education_level_id
//                INNER JOIN education_systems
//                ON education_systems.id = education_levels.education_system_id
//                WHERE education_systems.academic_period_id = $selectedAcademicPeriodId
//            ) last_year_grades
//            ON last_year_grades.academic_period_id = current_academic_period.academic_period_id
//            AND last_year_grades.main_education_grade_id = current_academic_period.education_grade_id
//            AND last_year_grades.education_grade_code = students_gpa.education_grades_code
//            GROUP BY students_gpa.student_id
//                ,students_gpa.institution_id
//                ,current_academic_period.academic_period_id
//        ) cum_gpa
//        ON cum_gpa.academic_period_id = main_q.academic_period_id
//        AND cum_gpa.education_grade_id = main_q.education_grade_id
//        AND cum_gpa.institution_id = main_q.institution_id
//        AND cum_gpa.student_id = main_q.student_id
//        LEFT JOIN institution_students_gpa
//        ON institution_students_gpa.student_id = main_q.student_id
//        AND institution_students_gpa.institution_id = main_q.institution_id
//        AND institution_students_gpa.academic_period_id = main_q.academic_period_id
//        AND institution_students_gpa.education_grade_id = main_q.education_grade_id
//        AND institution_students_gpa.education_grades_gpa_id = ind_gpa.education_grades_gpa_id
//        WHERE institution_students_gpa.institution_id IS NULL
//        GROUP BY main_q.student_id
//            ,main_q.institution_id
//            ,main_q.academic_period_id
//            ,main_q.education_grade_id
//            ,ind_gpa.education_grades_gpa_id;");
//            $statement->execute();
//        //echo "<pre>"; print_r($statement); die;
//        }else{
//          //  die('else');
//            $statement = $connection->prepare("UPDATE institution_students_gpa
//                INNER JOIN
//                (
//                    SELECT main_q.student_id
//                        ,main_q.institution_id
//                        ,main_q.academic_period_id
//                        ,main_q.education_grade_id
//                        ,ind_gpa.education_grades_gpa_id
//
//                        ,IFNULL(cum_gpa.cum_gpa_per_student, 0.00) cum_gpa
//                        ,$loginUserId AS created_user_id -- TO MAKE IT DYNAMIC BASED ON USER_ID WHO GENERATES THE GPA
//                        ,CURRENT_TIMESTAMP() created
//                    FROM
//                    (
//                        SELECT institution_students.student_id
//                            ,institution_students.institution_id
//                            ,institution_students.education_grade_id
//                            ,institution_students.academic_period_id
//                        FROM institution_students
//                        INNER JOIN academic_periods
//                        ON academic_periods.id = institution_students.academic_period_id
//                        WHERE institution_students.academic_period_id = $selectedAcademicPeriodId
//                        AND institution_students.student_id = $studentId
//                        AND institution_students.institution_id = $institutionId
//                        AND IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_students.student_status_id = 1, institution_students.student_status_id IN (1, 7, 6, 8))
//                    ) main_q
//                    INNER JOIN
//                    (
//                        SELECT  subq.academic_period_id
//                            ,subq.education_grade_id
//                            ,subq.assessment_period_start_date
//                            ,subq.assessment_period_end_date
//                            ,subq.institution_id
//                            ,education_grades_gpa.id education_grades_gpa_id
//                            ,subq.student_id
//                            ,ROUND(AVG(IFNULL(gpa_grading_options.point, 0)), 2) gpa_per_student
//                        FROM
//                        (
//                            SELECT  institution_subject_students.academic_period_id
//                                ,institution_subject_students.institution_id
//                                ,institution_subject_students.education_grade_id
//                                ,institution_subject_students.education_subject_id
//                                ,institution_subject_students.student_id
//                                ,term_info.academic_term
//                                ,term_info.assessment_period_start_date
//                                ,term_info.assessment_period_end_date
//                                ,IFNULL(subq2.total_mark,0) total_mark
//                            FROM institution_subject_students
//                            INNER JOIN
//                            (
//                                SELECT  assessments.academic_period_id
//                                    ,assessments.education_grade_id
//                                    ,IFNULL(assessment_periods.academic_term, 1) academic_term
//                                    ,MIN(assessment_periods.start_date) assessment_period_start_date
//                                    ,MAX(assessment_periods.end_date) assessment_period_end_date
//                                FROM assessment_periods
//                                INNER JOIN assessments
//                                ON assessments.id = assessment_periods.assessment_id
//                                WHERE assessments.academic_period_id = $selectedAcademicPeriodId
//                                GROUP BY  assessments.academic_period_id
//                                        ,assessments.education_grade_id
//                                        ,IFNULL(assessment_periods.academic_term, 1)
//                            ) term_info
//                            ON term_info.academic_period_id = institution_subject_students.academic_period_id
//                            AND term_info.education_grade_id = institution_subject_students.education_grade_id
//                            LEFT JOIN
//                            (
//                                SELECT  assessment_item_results.academic_period_id
//                                    ,assessment_item_results.institution_id
//                                    ,assessment_item_results.education_grade_id
//                                    ,assessment_item_results.education_subject_id
//                                    ,assessment_item_results.student_id
//                                    ,IFNULL(assessment_periods.academic_term, 1) AS academic_term
//                                    ,IFNULL( ROUND( SUM(assessment_item_results.marks * assessment_periods.weight) / SUM(assessment_periods.weight),2 ),'' ) AS total_mark
//                                FROM assessment_item_results
//                                INNER JOIN
//                                (
//                                    SELECT  assessment_item_results.academic_period_id
//                                        ,assessment_item_results.institution_id
//                                        ,assessment_item_results.education_grade_id
//                                        ,assessment_item_results.student_id
//                                        ,assessment_item_results.assessment_id
//                                        ,assessment_item_results.education_subject_id
//                                        ,assessment_item_results.assessment_period_id
//                                        ,MAX(assessment_item_results.created) latest_created
//                                    FROM assessment_item_results
//                                    WHERE assessment_item_results.academic_period_id = $selectedAcademicPeriodId
//                                    AND assessment_item_results.student_id = $studentId
//                                    GROUP BY  assessment_item_results.academic_period_id
//                                            ,assessment_item_results.education_grade_id
//                                            ,assessment_item_results.student_id
//                                            ,assessment_item_results.assessment_id
//                                            ,assessment_item_results.education_subject_id
//                                            ,assessment_item_results.assessment_period_id
//                                ) latest_grades
//                                ON latest_grades.academic_period_id = assessment_item_results.academic_period_id
//                                AND latest_grades.education_grade_id = assessment_item_results.education_grade_id
//                                AND latest_grades.student_id = assessment_item_results.student_id
//                                AND latest_grades.assessment_id = assessment_item_results.assessment_id
//                                AND latest_grades.education_subject_id = assessment_item_results.education_subject_id
//                                AND latest_grades.assessment_period_id = assessment_item_results.assessment_period_id
//                                AND latest_grades.latest_created = assessment_item_results.created
//                                INNER JOIN assessment_periods
//                                ON assessment_periods.id = assessment_item_results.assessment_period_id
//                                INNER JOIN education_subjects
//                                ON education_subjects.id = assessment_item_results.education_subject_id
//                                LEFT JOIN
//                                (
//                                    SELECT assessment_item_student_exemptions.assessment_id
//                                        ,assessment_item_student_exemptions.education_subject_id
//                                        ,assessment_item_student_exemptions.student_id
//                                        ,assessment_item_student_exemptions.institution_class_id
//                                        ,assessment_item_student_exemptions.education_grade_id
//                                        ,assessment_item_student_exemptions.assessment_period_id
//                                    FROM assessment_item_student_exemptions
//                                    INNER JOIN assessments
//                                    ON assessments.id = assessment_item_student_exemptions.assessment_id
//                                    WHERE assessments.academic_period_id = $selectedAcademicPeriodId
//                                    AND assessment_item_student_exemptions.student_id = $studentId
//                                ) exemption_details
//                                ON exemption_details.assessment_id = assessment_item_results.assessment_id
//                                AND exemption_details.education_subject_id = assessment_item_results.education_subject_id
//                                AND exemption_details.student_id = assessment_item_results.student_id
//                                AND exemption_details.institution_class_id = assessment_item_results.institution_classes_id
//                                AND exemption_details.education_grade_id = assessment_item_results.education_grade_id
//                                AND exemption_details.assessment_period_id = assessment_item_results.assessment_period_id
//                                WHERE assessment_item_results.academic_period_id = $selectedAcademicPeriodId
//                                AND assessment_item_results.student_id = $studentId
//                                AND exemption_details.assessment_id IS NULL
//                                GROUP BY  assessment_item_results.academic_period_id
//                                        ,assessment_item_results.education_grade_id
//                                        ,assessment_item_results.education_subject_id
//                                        ,assessment_item_results.student_id
//                                        ,assessment_periods.academic_term
//                            ) subq2
//                            ON subq2.academic_period_id = institution_subject_students.academic_period_id
//                            AND subq2.education_grade_id = institution_subject_students.education_grade_id
//                            AND subq2.student_id = institution_subject_students.student_id
//                            AND subq2.education_subject_id = institution_subject_students.education_subject_id
//                            AND subq2.academic_term = term_info.academic_term
//                            LEFT JOIN(
//                            SELECT institution_classes.academic_period_id,
//                                institution_classes.institution_id,
//                                assessment_item_student_exemptions.education_grade_id,
//                                assessment_item_student_exemptions.student_id,
//                                assessment_item_student_exemptions.education_subject_id,
//                                assessment_periods.academic_term
//                            FROM
//                                assessment_item_student_exemptions
//                            INNER JOIN assessment_periods ON assessment_periods.id = assessment_item_student_exemptions.assessment_period_id
//                            INNER JOIN institution_classes ON institution_classes.id = assessment_item_student_exemptions.institution_class_id
//                            WHERE
//                                institution_classes.academic_period_id = $selectedAcademicPeriodId
//                                AND institution_classes.institution_id = $institutionId
//                                AND assessment_item_student_exemptions.student_id = $studentId
//
//                            GROUP BY
//                                assessment_item_student_exemptions.education_subject_id,
//                                assessment_item_student_exemptions.student_id,
//                                assessment_item_student_exemptions.education_grade_id,
//                                assessment_periods.academic_term
//                        ) exemption_info
//                        ON
//                            exemption_info.academic_period_id = institution_subject_students.academic_period_id AND exemption_info.institution_id = institution_subject_students.institution_id AND exemption_info.education_grade_id = institution_subject_students.education_grade_id AND exemption_info.student_id = institution_subject_students.student_id AND exemption_info.education_subject_id = institution_subject_students.education_subject_id AND exemption_info.academic_term = term_info.academic_term
//                            WHERE institution_subject_students.academic_period_id = $selectedAcademicPeriodId
//                            AND institution_subject_students.student_id = $studentId
//                            AND institution_subject_students.institution_id = $institutionId
//                            AND exemption_info.academic_period_id IS NULL
//                            GROUP BY  institution_subject_students.academic_period_id
//                                    ,institution_subject_students.education_grade_id
//                                    ,institution_subject_students.education_subject_id
//                                    ,institution_subject_students.student_id
//                                    ,term_info.academic_term
//                        ) subq
//                        INNER JOIN education_grades_gpa
//                        ON subq.assessment_period_end_date BETWEEN education_grades_gpa.start_date AND education_grades_gpa.end_date
//                        AND education_grades_gpa.academic_period_id = subq.academic_period_id
//                        AND education_grades_gpa.education_grade_id = subq.education_grade_id
//                        LEFT JOIN gpa_grading_options
//                        ON subq.total_mark >= gpa_grading_options.min
//                        AND subq.total_mark <= gpa_grading_options.max
//                        AND education_grades_gpa.gpa_grading_type_id = gpa_grading_options.gpa_grading_type_id
//                        GROUP BY  subq.academic_period_id
//                                ,subq.institution_id
//                                ,subq.education_grade_id
//                                ,subq.student_id
//                    ) ind_gpa
//                    ON ind_gpa.student_id = main_q.student_id
//                    AND ind_gpa.institution_id = main_q.institution_id
//                    AND ind_gpa.academic_period_id = main_q.academic_period_id
//                    AND ind_gpa.education_grade_id = main_q.education_grade_id
//                    LEFT JOIN
//                    (
//                        SELECT students_gpa.student_id
//                            ,students_gpa.institution_id
//                            ,current_academic_period.academic_period_id
//                            ,MAX(students_gpa.education_grade_id) education_grade_id
//                            ,ROUND(AVG(IFNULL(students_gpa.gpa, 0)), 2) cum_gpa_per_student
//                        FROM
//                        (
//                            SELECT institution_students_gpa.institution_id
//                                ,institution_students_gpa.academic_period_id
//                                ,institution_students_gpa.education_grade_id
//                                ,education_grades.code education_grades_code
//                                ,institution_students_gpa.student_id
//                                ,institution_students_gpa.education_grades_gpa_id
//                                ,ROUND(AVG(institution_students_gpa.gpa), 2) gpa
//                            FROM institution_students_gpa
//                            INNER JOIN education_grades
//                            ON education_grades.id = institution_students_gpa.education_grade_id
//                            WHERE institution_students_gpa.student_id = $studentId
//                            GROUP BY institution_students_gpa.institution_id
//                                ,institution_students_gpa.academic_period_id
//                                ,institution_students_gpa.education_grade_id
//                                ,institution_students_gpa.education_grades_gpa_id
//                        ) students_gpa
//                        INNER JOIN
//                        (
//                            SELECT academic_periods.id academic_period_id
//                                ,education_grades.id education_grade_id
//                            FROM education_grades
//                            INNER JOIN education_programmes
//                            ON education_programmes.id = education_grades.education_programme_id
//                            INNER JOIN education_cycles
//                            ON education_cycles.id = education_programmes.education_cycle_id
//                            INNER JOIN education_levels
//                            ON education_levels.id = education_cycles.education_level_id
//                            INNER JOIN education_systems
//                            ON education_systems.id = education_levels.education_system_id
//                            INNER JOIN academic_periods
//                            ON academic_periods.id = education_systems.academic_period_id
//                            WHERE academic_periods.id = $selectedAcademicPeriodId
//                            AND education_grades.id = $educationGradeId
//                        ) current_academic_period
//                        INNER JOIN
//                        (
//                            SELECT education_grades_cumulative_gpa.main_education_grade_id
//                                ,education_systems.academic_period_id
//                                ,education_grades.code education_grade_code
//                            FROM education_grades_cumulative_gpa
//                            INNER JOIN education_grades
//                            ON education_grades.id = education_grades_cumulative_gpa.education_grade_id
//                            INNER JOIN education_programmes
//                            ON education_programmes.id = education_grades.education_programme_id
//                            INNER JOIN education_cycles
//                            ON education_cycles.id = education_programmes.education_cycle_id
//                            INNER JOIN education_levels
//                            ON education_levels.id = education_cycles.education_level_id
//                            INNER JOIN education_systems
//                            ON education_systems.id = education_levels.education_system_id
//                            WHERE education_systems.academic_period_id = $selectedAcademicPeriodId
//                        ) last_year_grades
//                        ON last_year_grades.academic_period_id = current_academic_period.academic_period_id
//                        AND last_year_grades.main_education_grade_id = current_academic_period.education_grade_id
//                        AND last_year_grades.education_grade_code = students_gpa.education_grades_code
//                        GROUP BY students_gpa.student_id
//                            ,students_gpa.institution_id
//                            ,current_academic_period.academic_period_id
//                    ) cum_gpa
//                    ON cum_gpa.academic_period_id = main_q.academic_period_id
//                    AND cum_gpa.education_grade_id = main_q.education_grade_id
//                    AND cum_gpa.institution_id = main_q.institution_id
//                    AND cum_gpa.student_id = main_q.student_id
//                    GROUP BY main_q.student_id
//                        ,main_q.institution_id
//                        ,main_q.academic_period_id
//                        ,main_q.education_grade_id
//                        ,ind_gpa.education_grades_gpa_id
//                ) subq4
//                ON subq4.student_id = institution_students_gpa.student_id
//                AND subq4.institution_id = institution_students_gpa.institution_id
//                AND subq4.academic_period_id = institution_students_gpa.academic_period_id
//                AND subq4.education_grade_id = institution_students_gpa.education_grade_id
//                AND subq4.education_grades_gpa_id = institution_students_gpa.education_grades_gpa_id
//                SET institution_students_gpa.cumulative_gpa = subq4.cum_gpa;");
//            $statement->execute();
//            //echo "<pre>"; print_r($statement); die;
//        }
//// POCOR-9162 end
    }

    private static function insertCumulativeGpaPerStudentPerGpa(
        $institutionId,
        $studentId,
        $academicPeriodId,
        $educationGradeId,
        $educationGradeGpaId): \Cake\Datasource\EntityInterface
    {
        $gpa = ReportCardGpaTable::getGpaForStudentGpa($institutionId,
            $studentId,
            $academicPeriodId,
            $educationGradeId,
            $educationGradeGpaId);
        $cum_gpa = self::getCumulativeGpaForStudentGpa($institutionId,
            $studentId,
            $academicPeriodId,
            $educationGradeId,
            $educationGradeGpaId);
//        $gpa = 0;
        $session = new Session();
        if (is_null($session->read('Auth.User.id'))) {
            $userId = 1;    // Super Admin
        } else {
            $userId = $session->read('Auth.User.id');
        }

        $gpaTable = self::getDynamicTableInstance('Institution.InstitutionStudentsGpa');
        $existing = $gpaTable->find()
            ->where([
                'student_id' => $studentId,
                'institution_id' => $institutionId,
                'academic_period_id' => $academicPeriodId,
                'education_grade_id' => $educationGradeId,
                'education_grades_gpa_id' => $educationGradeGpaId,
            ])
            ->first();
        // POCOR-9177 start: make it less
        $new = false;
        if ($existing) {
//            Log::debug(print_r([$gpa, $cum_gpa, $existing], true));
            if ($existing->gpa != $gpa || $existing->cumulative_gpa != $cum_gpa) {
                $new = true;
                $existing   = $gpaTable->patchEntity($existing, [
                    'gpa' => $gpa,
                    'cumulative_gpa' => $cum_gpa,
                    'modified_user_id' => $userId,
                    'modified' => FrozenTime::now()
                ]);
            }
        } else {
            $new = true;
                $existing = $gpaTable->newEntity([
                'student_id' => $studentId,
                'institution_id' => $institutionId,
                'academic_period_id' => $academicPeriodId,
                'education_grade_id' => $educationGradeId,
                'education_grades_gpa_id' => $educationGradeGpaId,
                'gpa' => $gpa,
                'cumulative_gpa' => $cum_gpa,
                'created_user_id' => $userId,
                'created' => FrozenTime::now()
            ]);
        }

        if ($new) {
            $conn = $gpaTable->getConnection();
            $conn->begin();
            if ($gpaTable->save($existing)) {
                $conn->commit();
                return $existing; // or whatever
            } else {
                $conn->rollback();
                throw new \Exception("Failed to save GPA record.");
            }
        }else{
            return $existing;
        }
        // POCOR-9177 end
    }

    /**
     * Calculates the GPA for a student based on GPA level (education_grades_gpa_id),
     * respecting exemptions and only using valid assessments.
     *
     * @param int $institutionId
     * @param int $studentId
     * @param int $academicPeriodId
     * @param int $educationGradeId
     * @param int $educationGradeGpaId
     * @return float GPA value (0.00 if no result)
     */
    private static function getCumulativeGpaForStudentGpa(
        int $institutionId,
        int $studentId,
        int $academicPeriodId,
        int $educationGradeId,
        int $educationGradeGpaId
    ): float {
        $connection = ConnectionManager::get('default');

        $sql = "
        SELECT IFNULL(cum_gpa.cum_gpa_per_student, 0.00) cum_gpa
        FROM
        (
            SELECT institution_students.student_id
                ,institution_students.institution_id
                ,institution_students.education_grade_id
                ,institution_students.academic_period_id
            FROM institution_students
            INNER JOIN academic_periods
            ON academic_periods.id = institution_students.academic_period_id
            WHERE institution_students.academic_period_id = $academicPeriodId
            AND institution_students.student_id = $studentId
            AND institution_students.institution_id = $institutionId
            AND IF((CURRENT_DATE >= academic_periods.start_date AND
            CURRENT_DATE <= academic_periods.end_date),
            institution_students.student_status_id = 1,
            institution_students.student_status_id IN (1, 7, 6, 8))
        ) main_q
        INNER JOIN
        (
            SELECT  subq.academic_period_id
                ,subq.education_grade_id
                ,subq.assessment_period_start_date
                ,subq.assessment_period_end_date
                ,subq.institution_id
                ,education_grades_gpa.id education_grades_gpa_id
                ,subq.student_id
                ,ROUND(AVG(IFNULL(gpa_grading_options.point, 0)), 2) gpa_per_student
            FROM
            (
                SELECT  institution_subject_students.academic_period_id
                    ,institution_subject_students.institution_id
                    ,institution_subject_students.education_grade_id
                    ,institution_subject_students.education_subject_id
                    ,institution_subject_students.student_id
                    ,term_info.academic_term
                    ,term_info.assessment_period_start_date
                    ,term_info.assessment_period_end_date
                    ,IFNULL(subq2.total_mark,0) total_mark
                FROM institution_subject_students
                INNER JOIN
                (
                    SELECT  assessments.academic_period_id
                        ,assessments.education_grade_id
                        ,IFNULL(assessment_periods.academic_term, 1) academic_term
                        ,MIN(assessment_periods.start_date) assessment_period_start_date
                        ,MAX(assessment_periods.end_date) assessment_period_end_date
                    FROM assessment_periods
                    INNER JOIN assessments
                    ON assessments.id = assessment_periods.assessment_id
                    WHERE assessments.academic_period_id = $academicPeriodId
                    GROUP BY  assessments.academic_period_id
                            ,assessments.education_grade_id
                            ,IFNULL(assessment_periods.academic_term, 1)
                ) term_info
                ON term_info.academic_period_id = institution_subject_students.academic_period_id
                AND term_info.education_grade_id = institution_subject_students.education_grade_id
                LEFT JOIN
                (
                    SELECT  assessment_item_results.academic_period_id
                        ,assessment_item_results.institution_id
                        ,assessment_item_results.education_grade_id
                        ,assessment_item_results.education_subject_id
                        ,assessment_item_results.student_id
                        ,IFNULL(assessment_periods.academic_term, 1) AS academic_term
                        ,IFNULL( ROUND( SUM(assessment_item_results.marks * assessment_periods.weight) / SUM(assessment_periods.weight),2 ),'' ) AS total_mark
                    FROM assessment_item_results
                    INNER JOIN
                    (
                        SELECT  assessment_item_results.academic_period_id
                            ,assessment_item_results.institution_id
                            ,assessment_item_results.education_grade_id
                            ,assessment_item_results.student_id
                            ,assessment_item_results.assessment_id
                            ,assessment_item_results.education_subject_id
                            ,assessment_item_results.assessment_period_id
                            ,MAX(assessment_item_results.created) latest_created
                        FROM assessment_item_results
                        WHERE assessment_item_results.academic_period_id = $academicPeriodId
                        AND assessment_item_results.student_id = $studentId
                        GROUP BY  assessment_item_results.academic_period_id
                                ,assessment_item_results.education_grade_id
                                ,assessment_item_results.student_id
                                ,assessment_item_results.assessment_id
                                ,assessment_item_results.education_subject_id
                                ,assessment_item_results.assessment_period_id
                    ) latest_grades
                    ON latest_grades.academic_period_id = assessment_item_results.academic_period_id
                    AND latest_grades.education_grade_id = assessment_item_results.education_grade_id
                    AND latest_grades.student_id = assessment_item_results.student_id
                    AND latest_grades.assessment_id = assessment_item_results.assessment_id
                    AND latest_grades.education_subject_id = assessment_item_results.education_subject_id
                    AND latest_grades.assessment_period_id = assessment_item_results.assessment_period_id
                    AND latest_grades.latest_created = assessment_item_results.created
                    INNER JOIN assessment_periods
                    ON assessment_periods.id = assessment_item_results.assessment_period_id
                    INNER JOIN education_subjects
                    ON education_subjects.id = assessment_item_results.education_subject_id
                    LEFT JOIN
                    (
                        SELECT assessment_item_student_exemptions.assessment_id
                            ,assessment_item_student_exemptions.education_subject_id
                            ,assessment_item_student_exemptions.student_id
                            ,assessment_item_student_exemptions.institution_class_id
                            ,assessment_item_student_exemptions.education_grade_id
                            ,assessment_item_student_exemptions.assessment_period_id
                        FROM assessment_item_student_exemptions
                        INNER JOIN assessments
                        ON assessments.id = assessment_item_student_exemptions.assessment_id
                        WHERE assessments.academic_period_id = $academicPeriodId
                        AND assessment_item_student_exemptions.student_id = $studentId
                    ) exemption_details
                    ON exemption_details.assessment_id = assessment_item_results.assessment_id
                    AND exemption_details.education_subject_id = assessment_item_results.education_subject_id
                    AND exemption_details.student_id = assessment_item_results.student_id
                    AND exemption_details.institution_class_id = assessment_item_results.institution_classes_id
                    AND exemption_details.education_grade_id = assessment_item_results.education_grade_id
                    AND exemption_details.assessment_period_id = assessment_item_results.assessment_period_id
                    WHERE assessment_item_results.academic_period_id = $academicPeriodId
                    AND assessment_item_results.student_id = $studentId
                    AND exemption_details.assessment_id IS NULL
                    GROUP BY  assessment_item_results.academic_period_id
                            ,assessment_item_results.education_grade_id
                            ,assessment_item_results.education_subject_id
                            ,assessment_item_results.student_id
                            ,assessment_periods.academic_term
                ) subq2
                ON subq2.academic_period_id = institution_subject_students.academic_period_id
                AND subq2.education_grade_id = institution_subject_students.education_grade_id
                AND subq2.student_id = institution_subject_students.student_id
                AND subq2.education_subject_id = institution_subject_students.education_subject_id
                AND subq2.academic_term = term_info.academic_term
                LEFT JOIN(
                        SELECT institution_classes.academic_period_id,
                            institution_classes.institution_id,
                            assessment_item_student_exemptions.education_grade_id,
                            assessment_item_student_exemptions.student_id,
                            assessment_item_student_exemptions.education_subject_id,
                            assessment_periods.academic_term
                        FROM
                            assessment_item_student_exemptions
                        INNER JOIN assessment_periods ON assessment_periods.id = assessment_item_student_exemptions.assessment_period_id
                        INNER JOIN institution_classes ON institution_classes.id = assessment_item_student_exemptions.institution_class_id
                        WHERE
                            institution_classes.academic_period_id = $academicPeriodId
                            AND institution_classes.institution_id = $institutionId
                            AND assessment_item_student_exemptions.student_id = $studentId
                        GROUP BY
                            assessment_item_student_exemptions.education_subject_id,
                            assessment_item_student_exemptions.student_id,
                            assessment_item_student_exemptions.education_grade_id,
                            assessment_periods.academic_term
                    ) exemption_info
                    ON
                        exemption_info.academic_period_id = institution_subject_students.academic_period_id AND exemption_info.institution_id = institution_subject_students.institution_id AND exemption_info.education_grade_id = institution_subject_students.education_grade_id AND exemption_info.student_id = institution_subject_students.student_id AND exemption_info.education_subject_id = institution_subject_students.education_subject_id AND exemption_info.academic_term = term_info.academic_term
                WHERE institution_subject_students.academic_period_id = $academicPeriodId
                AND institution_subject_students.student_id = $studentId
                AND institution_subject_students.institution_id = $institutionId
                AND exemption_info.academic_period_id IS NULL
                GROUP BY  institution_subject_students.academic_period_id
                        ,institution_subject_students.education_grade_id
                        ,institution_subject_students.education_subject_id
                        ,institution_subject_students.student_id
                        ,term_info.academic_term
            ) subq
            INNER JOIN education_grades_gpa
            ON subq.assessment_period_end_date BETWEEN education_grades_gpa.start_date AND education_grades_gpa.end_date
            AND education_grades_gpa.academic_period_id = subq.academic_period_id
            AND education_grades_gpa.education_grade_id = subq.education_grade_id
            LEFT JOIN gpa_grading_options
            ON subq.total_mark >= gpa_grading_options.min
            AND subq.total_mark <= gpa_grading_options.max
            AND education_grades_gpa.gpa_grading_type_id = gpa_grading_options.gpa_grading_type_id
            GROUP BY  subq.academic_period_id
                    ,subq.institution_id
                    ,subq.education_grade_id
                    ,subq.student_id
        ) ind_gpa
        ON ind_gpa.student_id = main_q.student_id
        AND ind_gpa.institution_id = main_q.institution_id
        AND ind_gpa.academic_period_id = main_q.academic_period_id
        AND ind_gpa.education_grade_id = main_q.education_grade_id
        LEFT JOIN
        (
            SELECT students_gpa.student_id
                ,students_gpa.institution_id
                ,current_academic_period.academic_period_id
                ,MAX(students_gpa.education_grade_id) education_grade_id
                ,ROUND(AVG(IFNULL(students_gpa.gpa, 0)), 2) cum_gpa_per_student
            FROM
            (
                SELECT institution_students_gpa.institution_id
                    ,institution_students_gpa.academic_period_id
                    ,institution_students_gpa.education_grade_id
                    ,education_grades.code education_grades_code
                    ,institution_students_gpa.student_id
                    ,institution_students_gpa.education_grades_gpa_id
                    ,ROUND(AVG(institution_students_gpa.gpa), 2) gpa
                FROM institution_students_gpa
                INNER JOIN education_grades
                ON education_grades.id = institution_students_gpa.education_grade_id
                WHERE institution_students_gpa.student_id = $studentId
                GROUP BY institution_students_gpa.institution_id
                    ,institution_students_gpa.academic_period_id
                    ,institution_students_gpa.education_grade_id
                    ,institution_students_gpa.education_grades_gpa_id
            ) students_gpa
            INNER JOIN
            (
                SELECT academic_periods.id academic_period_id
                    ,education_grades.id education_grade_id
                FROM education_grades
                INNER JOIN education_programmes
                ON education_programmes.id = education_grades.education_programme_id
                INNER JOIN education_cycles
                ON education_cycles.id = education_programmes.education_cycle_id
                INNER JOIN education_levels
                ON education_levels.id = education_cycles.education_level_id
                INNER JOIN education_systems
                ON education_systems.id = education_levels.education_system_id
                INNER JOIN academic_periods
                ON academic_periods.id = education_systems.academic_period_id
                WHERE academic_periods.id = $academicPeriodId
                AND education_grades.id = $educationGradeId
            ) current_academic_period
            INNER JOIN
            (
                SELECT education_grades_cumulative_gpa.main_education_grade_id
                    ,education_systems.academic_period_id
                    ,education_grades.code education_grade_code
                FROM education_grades_cumulative_gpa
                INNER JOIN education_grades
                ON education_grades.id = education_grades_cumulative_gpa.education_grade_id
                INNER JOIN education_programmes
                ON education_programmes.id = education_grades.education_programme_id
                INNER JOIN education_cycles
                ON education_cycles.id = education_programmes.education_cycle_id
                INNER JOIN education_levels
                ON education_levels.id = education_cycles.education_level_id
                INNER JOIN education_systems
                ON education_systems.id = education_levels.education_system_id
                WHERE education_systems.academic_period_id = $academicPeriodId
            ) last_year_grades
            ON last_year_grades.academic_period_id = current_academic_period.academic_period_id
            AND last_year_grades.main_education_grade_id = current_academic_period.education_grade_id
            AND last_year_grades.education_grade_code = students_gpa.education_grades_code
            GROUP BY students_gpa.student_id
                ,students_gpa.institution_id
                ,current_academic_period.academic_period_id
        ) cum_gpa
        ON cum_gpa.academic_period_id = main_q.academic_period_id
        AND cum_gpa.education_grade_id = main_q.education_grade_id
        AND cum_gpa.institution_id = main_q.institution_id
        AND cum_gpa.student_id = main_q.student_id
        LEFT JOIN institution_students_gpa
        ON institution_students_gpa.student_id = main_q.student_id
        AND institution_students_gpa.institution_id = main_q.institution_id
        AND institution_students_gpa.academic_period_id = main_q.academic_period_id
        AND institution_students_gpa.education_grade_id = main_q.education_grade_id
        AND institution_students_gpa.education_grades_gpa_id = ind_gpa.education_grades_gpa_id
        GROUP BY main_q.student_id
            ,main_q.institution_id
            ,main_q.academic_period_id
            ,main_q.education_grade_id
            ,ind_gpa.education_grades_gpa_id;
        ";


        $result = $connection->execute($sql)->fetch('assoc');
//        Log::debug('GPA SQL: ' . $sql);
//        Log::debug('GPA Result: ' . print_r($result,true));
//        Log::debug('GPA: ' . $result['gpa'] ?? 0.00);
//        Log::debug('GPA ID: ' . $educationGradeGpaId);
//        Log::debug('Student ID: ' . $studentId);
//        Log::debug('Institution ID: ' . $institutionId);
//        Log::debug('Academic Period ID: ' . $academicPeriodId);
//        Log::debug('Education Grade ID: ' . $educationGradeId);
        return $result['cum_gpa'] ?? 0.00;
    }
    /**
     * @param array $buttons
     * @param $params
     * @return array
    */
    private function addGenerateButton(array $buttons, $params)
    {
        $params['institution_id'] = $this->getInstitutionID();
        $indexAttr = ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false];
        $educationGradeId = $this->request->getQuery('education_grade_id');
        $isAdmin = $this->AccessControl->isAdmin();
        if (!$isAdmin) {
            $security_role_ids = $this->getUserSecurityRoles();
            $SecurityRoleFunctions = self::getDynamicTableInstance('Security.SecurityRoleFunctions');
            $SecurityFunctions = self::getDynamicTableInstance('Security.SecurityFunctions');
            $where = [$SecurityRoleFunctions->aliasField('security_role_id IN') => $security_role_ids];
        }
        $canGenerate = $this->AccessControl->check(['Institutions', 'ReportCardCumulativeGpa', 'generate']);
        if (!($isAdmin) && $canGenerate == 1 || $canGenerate == 0) {
            $canGenerateData = $SecurityFunctions
                ->find()
                ->where([
                    $SecurityFunctions->aliasField('name') => 'CumulativeGpaGenerate'
                ])
                ->first();

            if ($canGenerateData) {
                $canUserGenerateData = $SecurityRoleFunctions
                    ->find()
                    ->where([
                        $SecurityRoleFunctions->aliasField('security_function_id') => $canGenerateData->id,
                        $SecurityRoleFunctions->aliasField('_execute') => 1,
                        $SecurityRoleFunctions->aliasField('security_role_id IN') => $security_role_ids
                    ])
                    ->first();
                if (!empty($canUserGenerateData)) {
                    $canGenerate = 1;
                }else{
                    $canGenerate = 0;
                }
            }
        }

        if ($canGenerate) {
            $generateUrl = $this->setQueryString($this->url('generate'), $params);
            $canGenerateAnyDate = false;
            if ($isAdmin) {
                $canGenerateAnyDate = true;
            }
            if (!$canGenerateAnyDate) {
                $canGenerateAnyDate = $this->canGenerateAnyDate($educationGradeId);
            }
            if ($canGenerateAnyDate) {
                $buttons['generate'] = [
                    'label' => '<i class="fa fa-refresh"></i>' . __('Generate'),
                    'attr' => $indexAttr,
                    'url' => $generateUrl,
                ];
            }

            if (!$canGenerateAnyDate) {
                $reportCard = $this->ReportCards
                    ->find()
                    ->where([
                        $this->ReportCards->aliasField('education_grade_id') => $educationGradeId])
                    ->first();

                if (!empty($reportCard->generate_start_date)) {
                    $generateStartDate = $reportCard->generate_start_date->format('Y-m-d');
                }

                if (!empty($reportCard->generate_end_date)) {
                    $generateEndDate = $reportCard->generate_end_date->format('Y-m-d');
                }
                $date = Time::now()->format('Y-m-d');
                $canGenerateData = $SecurityFunctions
                    ->find()
                    ->where([
                        $SecurityFunctions->aliasField('name') => 'CumulativeGpaGenerate'])
                    ->first();

                $canUserGenerateData = $SecurityRoleFunctions
                    ->find()
                    ->where([
                        $SecurityRoleFunctions->aliasField('security_function_id') => $canGenerateData->id,
                        $where
                    ])
                    ->first();

                if ($canUserGenerateData) {
                    if ((!empty($generateStartDate)
                            && !empty($generateEndDate))
                        && ($date >= $generateStartDate && $date <= $generateEndDate)) {
                        $buttons['generate'] = [
                            'label' => '<i class="fa fa-refresh"></i>' . __('Generate'),
                            'attr' => $indexAttr,
                            'url' => $generateUrl
                        ];
                    } else {
                        $indexAttr['title'] = $this->getMessage('ReportCardStatuses.date_closed');
                        $buttons['generate'] = [
                            'label' => '<i class="fa fa-refresh"></i>' . __('Generate'),
                            'attr' => $indexAttr,
                            'url' => 'javascript:void(0)'
                        ];
                    }
                }
            }
        }
        return $buttons;
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getInstitutionGpaTab();
    }

    public function onGetCumulativeGpa(Event $event, Entity $entity)
    {
        $findCumulativeGpa =  '';
        $studentsGpa = self::getDynamicTableInstance('Institution.InstitutionStudentsGpa');
        $institutionId = $entity['institution_id']; //POCOR-8699
        $findGpa = $studentsGpa->find()->where(['student_id'=>$entity->student_id,
                        'education_grade_id'=>$entity->education_grade_id,'institution_id'=>$institutionId,'academic_period_id'=>$entity->academic_period_id])->first();
        if ($findGpa !== null && $findGpa->cumulative_gpa !== null) {
        // Return the GPA formatted to 2 decimal places
        return number_format((float)$findGpa->cumulative_gpa, 2);
    }
        return $findCumulativeGpa;
    }
    public function onGetCreated(Event $event, Entity $entity)
    {
        if($this->action == 'index' && !empty($entity->cumulative_gpa)){
            $studentsGpa = self::getDynamicTableInstance('Institution.InstitutionStudentsGpa');
            $institutionId = $entity['institution_id']; //POCOR-8699
            $record = $studentsGpa->find()
                ->where([
                    'student_id' => $entity->student_id,
                    'education_grade_id' => $entity->education_grade_id,
                    'institution_id' => $institutionId,
                    'academic_period_id' => $entity->academic_period_id
                ])
                ->first();
            if ($record) {
                // Return the modified date if it's not null, otherwise return the created date
                return !empty($record->modified) ? $record->modified : $record->created;
            }
            return null;
        }
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'cumulative_gpa') {
            return 'Cumulative GPA';
        } elseif (($field == 'created' || $field == 'modified') && $this->action === 'index') {
            return 'Updated';
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }

    }

    public function onGetStudentName(Event $event, Entity $entity)
    {
        return $entity->user->name;
    }

    public function onGetInstitutionClass(Event $event, Entity $entity)
    {
        $InstitutionClasses = self::getDynamicTableInstance('Institution.InstitutionClasses');
        $getName = $InstitutionClasses->find()
                    ->where([$InstitutionClasses->aliasField('id') => $entity->institution_class_id])
                    ->first()
                    ->name;
        return $getName ;
    }

    private function getUserSecurityRoles()
    {
        $SecurityGroupUsers = self::getDynamicTableInstance('Security.SecurityGroupUsers');
        $current_user = $this->Auth->user('id');
        $SecurityGroupUsersData = $SecurityGroupUsers
            ->find()
            ->select(['security_role_id'])
            ->distinct(['security_role_id'])
            ->where([
                $SecurityGroupUsers->aliasField('security_user_id') => $current_user
            ])
            ->group([$SecurityGroupUsers->aliasField('security_role_id')])
            ->toArray();
        $security_role_ids = array_column($SecurityGroupUsersData, 'security_role_id');
        if (empty($security_role_ids)) {
            $security_role_ids = [0];
        }
        return $security_role_ids;
    }

    public function canGenerateAnyDate()
    {
        $security_role_ids = $this->getUserSecurityRoles();
        $ExcludedSecurityRoleCount = -1;
        if (!empty($security_role_ids)) {
            $ExcludedSecurityRoleTable = self::getDynamicTableInstance('ReportCard.ReportCardExcludedSecurityRoles');
            $ExcludedSecurityRoleCount = $ExcludedSecurityRoleTable->find('all')
                ->where([
                    'security_role_id IN' => $security_role_ids,
                    //'report_card_id' => $report_card_id
                ])->count();
        }

        if (($ExcludedSecurityRoleCount > 0)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * POCOR-8391 added
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName
     * @return \Cake\ORM\Table
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        $locator = TableRegistry::getTableLocator();
        try {
            return $locator->get($tableName);
        } catch (\Exception $exception) {

        }
        $parts = explode('.', $tableName);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($tableName);
        $tableAlias = Inflector::camelize($table);

        // Create the fully qualified class name if a plugin is specified
        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $tableAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $tableAlias . 'Table';
        }
        // Check if the table instance already exists
        if (!$locator->exists($tableFullAlias)) {
            // Check if the specific table class exists
            if (!class_exists($className)) {
                $className = Table::class; // Fallback to generic Table class
            }

            // Configure a new table instance
            $locator->setConfig($tableAlias, [
                'className' => $className,
                'table' => $table,
                'alias' => $tableAlias,
            ]);
        }

        // Return the table instance
        return $locator->get($tableFullAlias);
    }

}
