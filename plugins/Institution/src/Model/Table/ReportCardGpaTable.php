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
use Cake\I18n\FrozenTime;
use Cake\Datasource\ConnectionManager; 
use App\Model\Table\ControllerActionTable;

class ReportCardGpaTable extends ControllerActionTable
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
        $this->ReportCards = TableRegistry::getTableLocator()->get('ReportCard.ReportCards');
        $this->ReportCardProcesses = TableRegistry::getTableLocator()->get('ReportCard.ReportCardProcesses');
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['ReportCardStatuses' =>['student_id','institution_class_id','class_id','education_grade_id','academic_period_id']
            ]
        ]);
        $this->addBehavior('User.AdvancedNameSearch');
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
        $reportCardId = $this->request->getQuery('report_card_id');
        if (is_null($reportCardId)) {
            return $buttons;
        }
        
        $queryString = $this->getQueryString();
       // echo "<pre>"; print_r($entity->student_id);
        if (isset($buttons['view'])) {
            $institutionId = $entity->institution_class->institution_id;
            $url = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'ReportCardGpa',
                0 =>  'view',
                1 => $this->paramsEncode(['id' => $entity->id,'institution_id' => $queryString['institution_id'],'student_id'=> $entity->student_id]),
            ];
        }
        
        $reportExists = $this->ReportCards->exists([$this->ReportCards->getPrimaryKey() => $reportCardId]);
        if (!$reportExists) {
            return $buttons;
        }
        $params = [
            'report_card_id' => $reportCardId,
            'student_id' => $entity->student_id,
            // 'institution_id' => $entity->institution_id, V4
            'institution_id' => $entity['institution']['id'],
            'academic_period_id' => $entity->academic_period_id,
            'education_grade_id' => $entity->education_grade_id,
        ];
        
        $params['institution_class_id'] = $entity->institution_class_id;
        $buttons['view']['url'] = $url;
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
        $this->field('gpa');
        $this->field('created',['visible' => true, 'sort' => false,'label' => 'Updated']);

        $this->fields['academic_period_id']['visible'] = false;
        
    }

    
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $institutionId = $this->getInstitutionID();
        $Classes = TableRegistry::get('Institution.InstitutionClasses');
        $InstitutionGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');

        // Academic Periods filter
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->getQuery('academic_period_id')) ? $this->request->getQuery('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        //End

        $availableGrades = $InstitutionGrades->find()
            ->where([$InstitutionGrades->aliasField('institution_id') => $institutionId])
            ->extract('education_grade_id')
            ->toArray();
        // Report Cards filter
        $reportCardOptions = [];
        if (!empty($availableGrades)) {
            $reportCardOptions = $this->ReportCards->find('list')
                ->where([
                    $this->ReportCards->aliasField('academic_period_id') => $selectedAcademicPeriod,
                    $this->ReportCards->aliasField('education_grade_id IN ') => $availableGrades
                ])
                ->toArray();
        } else {
            $this->Alert->warning('ReportCardStatuses.noProgrammes');
        }
        $reportCardOptions = ['-1' => '-- '.__('Select Report Card').' --'] + $reportCardOptions;
        $selectedReportCard = !is_null($this->request->getQuery('report_card_id')) ? $this->request->getQuery('report_card_id') : -1;
        $this->controller->set(compact('reportCardOptions', 'selectedReportCard'));
        //End

        // Class filter
        $classOptions = [];
        $selectedClass = !is_null($this->request->getQuery('class_id')) ? $this->request->getQuery('class_id') : -1;
        $educationGradeByReportCardId = '';
        if ($selectedReportCard != -1) {
            $reportCardEntity = $this->ReportCards->find()->where(['id' => $selectedReportCard])->first();
            if (!empty($reportCardEntity)) {
                $classOptions = $Classes->find('list')
                    ->matching('ClassGrades')
                    ->where([
                        $Classes->aliasField('academic_period_id') => $selectedAcademicPeriod,
                        $Classes->aliasField('institution_id') => $institutionId,
                        'ClassGrades.education_grade_id' => $reportCardEntity->education_grade_id
                    ])
                    ->order([$Classes->aliasField('name')])
                    ->toArray();
                $educationGradeByReportCardId = $reportCardEntity->education_grade_id;
            } else {
                
                $selectedClass = -1;
            }
        }

        if (!empty($classOptions)) {
            $classOptions['all'] = "All Classes";
        }

        $classOptions = ['-1' => '-- ' . __('Select Class') . ' --'] + $classOptions;
        $this->controller->set(compact('classOptions', 'selectedClass'));
        $where[$this->aliasField('institution_class_id')] = $selectedClass;
        $where[$this->aliasField('institution_id')] = $institutionId; 
        $where[$this->aliasField('student_status_id NOT IN')] = 3; 
        if (!empty($educationGradeByReportCardId)) {
            $where[$this->aliasField('education_grade_id')] = $educationGradeByReportCardId;
        }
        //End
        $UsersTable = TableRegistry::get('Security.Users');
        $query
            ->select([
                'id' => $this->aliasField('id'),
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
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);

        $extra['elements']['controls'] = ['name' => 'Institution.ReportCards/controls', 'data' => ['encodedQueryString' => $encodedQueryString], 'options' => [], 'order' => 1];

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
        $reportCardId = $this->request->getQuery('report_card_id');
        $classId = $this->request->getQuery('class_id');
       $this->InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $loginUserIdUser = $this->Auth->User('id');
        $securityRoles = $this->AccessControl->getRolesByUser($loginUserIdUser)->toArray();
        $securityRoleIds = [];
        foreach ($securityRoles as $key => $value) {
            $securityRoleIds[] = $value->security_role_id;
        }
        //$userSuperAddmin = $this->Session->read('Auth.User.super_admin'); 
        $userSuperAddmin = 1; 
        if ($userSuperAddmin == 1) {
            if (!is_null($reportCardId) && !is_null($classId)) {
                $existingReportCard = $this->ReportCards->exists([$this->ReportCards->getPrimaryKey() => $reportCardId]);
                $existingClass = $this->InstitutionClasses->exists([$this->InstitutionClasses->getPrimaryKey() => $classId]);
                // only show toolbar buttons if request for report card and class is valid
                if ($existingReportCard && $existingClass) {
                    $generatedCount = 0;
                    $publishedCount = 0;
                    $dataCount = count($data);
                    foreach ($data as $student) {
                        if ($student->has('report_card_status')) {
                            if ($student->report_card_status == self::GENERATED) {
                                $generatedCount += 1;
                            }
                        }
                    }

                    $toolbarAttr = [
                        'class' => 'btn btn-xs btn-default',
                        'data-toggle' => 'tooltip',
                        'data-placement' => 'bottom',
                        'escape' => false
                    ];

                    $params = [
                        'institution_id' => $this->getInstitutionID(),
                        'institution_class_id' => $classId,
                        'report_card_id' => $reportCardId
                    ];
                    
                    $SecurityFunctions = TableRegistry::get('Security.SecurityFunctions');
                    $SecurityFunctionsGenerateAllData = $SecurityFunctions
                        ->find()
                        ->where([
                            $SecurityFunctions->aliasField('name') => 'Generate All'])
                        ->first();
                    
                    // Generate all button
                    $generateButton['url'] = $this->setQueryString($this->url('generateAll'), $params);
                    $generateButton['type'] = 'button';
                    $generateButton['label'] = '<i class="fa fa-refresh"></i>';
                    $generateButton['attr'] = $toolbarAttr;
                    $generateButton['attr']['title'] = __('Generate All');
                    //$ReportCards = TableRegistry::getTableLocator()->get('ReportCard.ReportCards');
                    if (!is_null($this->request->getQuery('report_card_id'))) {
                        $reportCardId = $this->request->getQuery('report_card_id');
                    }


                    if ($this->AccessControl->isAdmin()) {
                        
                        $extra['toolbarButtons']['generateAll'] = $generateButton;
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
        $this->field('gpa', ['visible' => true]);
        $this->field('openemis_no', ['sort' => ['field' => 'Users.openemis_no']]);
        $this->field('student_name');
        $this->setFieldOrder(['academic_period_id', 'institution_class', 'openemis_no', 'student_name', 'gpa']);
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
        $hasTemplate = $this->ReportCards->checkIfHasTemplate($params['report_card_id']);

        if ($hasTemplate) {
            $this->addGpaReportCards($params['student_id'], $params['report_card_id'], $params['academic_period_id'],$params['institution_id'],$params['education_grade_id']);
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
        $hasTemplate = $this->ReportCards->checkIfHasTemplate($params['report_card_id']);
        $institutionId = $this->getInstitutionID();
        $params['academic_period_id'] = $this->request->getQuery('academic_period_id');
        $params['institution_class_id'] = $this->request->getQuery('class_id');
        $params['report_card_id'] = $this->request->getQuery('report_card_id');
        $reportCardId = $params['report_card_id'];
        $selectedAcademicPeriodId = $params['academic_period_id'];

        if ($hasTemplate) {
            $fetchAllRecord = $this->find()
            ->select([
                'student_id' => $this->aliasField('student_id'),
                'education_grade_id' => $this->aliasField('education_grade_id'),
            ])
            ->where(['institution_id' => $institutionId , 'institution_class_id IS' => $params['institution_class_id'], 'academic_period_id' => $params['academic_period_id']])->toArray();
            foreach($fetchAllRecord as $value){
                $studentId = $value['student_id'];
                $educationGradeId = $value['education_grade_id'];
                $this->addGpaReportCards($studentId, $reportCardId,$selectedAcademicPeriodId, $institutionId,$educationGradeId);
            }
        } else {
            $this->Alert->warning('ReportCardStatuses.noTemplate');
        }

        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }
    
    private function addGpaReportCards($checkgpaStudent, $reportCardId,$selectedAcademicPeriodId, $institutionId,$educationGradeId)
    {
        $selectedAcademicPeriodId = $selectedAcademicPeriodId;
        $reportCardId = $reportCardId;
        $institutionId = $institutionId;
        $educationGradeId = $educationGradeId;
        $studentId = $checkgpaStudent;
        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $gpa = 0.00;
        $connection = ConnectionManager::get('default');
        $statement = $connection->prepare("SELECT report_cards.id report_card_id
        ,subq3.student_id
        ,subq3.education_grade_id
        ,subq3.academic_period_id
        ,MAX(ROUND(subq3.gpa_per_student, 2)) gpa_per_student
    FROM
    (
        SELECT subq.academic_period_id
            ,subq.education_grade_id
            ,subq.assessment_period_start_date
            ,subq.assessment_period_end_date
            ,subq.institution_id
            ,subq.student_id
            ,ROUND(AVG(IFNULL(assessment_grading_options.point, 0)), 2) gpa_per_student
        FROM
        (
            SELECT institution_subject_students.academic_period_id
                ,institution_subject_students.institution_id
                ,institution_subject_students.education_grade_id
                ,institution_subject_students.education_subject_id
                ,institution_subject_students.student_id
                ,term_info.academic_term
                ,term_info.assessment_period_start_date
                ,term_info.assessment_period_end_date
               
                ,IFNULL(subq2.total_mark, 0) total_mark
            FROM institution_subject_students
            INNER JOIN
            (
                SELECT assessments.academic_period_id
                    ,assessments.education_grade_id
                    ,IFNULL(assessment_periods.academic_term, 1) academic_term
                    ,MIN(assessment_periods.start_date) assessment_period_start_date
                    ,MAX(assessment_periods.end_date) assessment_period_end_date
                FROM assessment_periods
                INNER JOIN assessments
                ON assessments.id = assessment_periods.assessment_id
                WHERE assessments.academic_period_id = $selectedAcademicPeriodId
                GROUP BY assessments.academic_period_id
                    ,assessments.education_grade_id
                    ,IFNULL(assessment_periods.academic_term, 1)
            ) term_info
            ON term_info.academic_period_id = institution_subject_students.academic_period_id
            AND term_info.education_grade_id = institution_subject_students.education_grade_id
            LEFT JOIN
            (
                SELECT 
                    assessment_item_results.academic_period_id,
                    assessment_item_results.institution_id,
                    assessment_item_results.education_grade_id,
                    assessment_item_results.education_subject_id,
                    assessment_item_results.student_id,
                    IFNULL(assessment_periods.academic_term, 1) AS academic_term,  -- Add the missing comma here
                    IFNULL(
                        ROUND(
                            SUM(assessment_item_results.marks * assessment_periods.weight) / 
                            IFNULL(CEILING(MAX(assessment_item_results.marks) / 10) * 10, 1) * 100, 
                            2
                        ), 
                        ''
                    ) AS total_mark
                FROM assessment_item_results
                    INNER JOIN
                    (
                        SELECT assessment_item_results.academic_period_id
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
                        GROUP BY assessment_item_results.academic_period_id
                            ,assessment_item_results.institution_id
                            ,assessment_item_results.education_grade_id
                            ,assessment_item_results.student_id
                            ,assessment_item_results.assessment_id
                            ,assessment_item_results.education_subject_id
                            ,assessment_item_results.assessment_period_id
                    ) latest_grades
                    ON latest_grades.academic_period_id = assessment_item_results.academic_period_id
                    AND latest_grades.institution_id = assessment_item_results.institution_id
                    AND latest_grades.education_grade_id = assessment_item_results.education_grade_id
                    AND latest_grades.student_id = assessment_item_results.student_id
                    AND latest_grades.assessment_id = assessment_item_results.assessment_id
                    AND latest_grades.education_subject_id = assessment_item_results.education_subject_id
                    AND latest_grades.assessment_period_id = assessment_item_results.assessment_period_id
                    AND latest_grades.latest_created = assessment_item_results.created
                    LEFT JOIN assessment_grading_options
                    ON assessment_grading_options.id = assessment_item_results.assessment_grading_option_id
                    INNER JOIN assessment_periods
                    ON assessment_periods.id = assessment_item_results.assessment_period_id
                    INNER JOIN education_subjects
                    ON education_subjects.id = assessment_item_results.education_subject_id
                    WHERE assessment_item_results.academic_period_id = $selectedAcademicPeriodId
                    AND assessment_item_results.student_id =$studentId
                    GROUP BY assessment_item_results.academic_period_id
                        ,assessment_item_results.institution_id
                        ,assessment_item_results.education_grade_id
                        ,assessment_item_results.education_subject_id
                        ,assessment_item_results.student_id
                        ,assessment_periods.academic_term
            ) subq2
            ON subq2.academic_period_id = institution_subject_students.academic_period_id
            AND subq2.institution_id = institution_subject_students.institution_id
            AND subq2.education_grade_id = institution_subject_students.education_grade_id
            AND subq2.student_id = institution_subject_students.student_id
            AND subq2.education_subject_id = institution_subject_students.education_subject_id
            AND subq2.academic_term = term_info.academic_term
            WHERE institution_subject_students.academic_period_id = $selectedAcademicPeriodId
            AND institution_subject_students.student_id = $studentId
            GROUP BY institution_subject_students.academic_period_id
                ,institution_subject_students.institution_id
                ,institution_subject_students.education_grade_id
                ,institution_subject_students.education_subject_id
                ,institution_subject_students.student_id
                ,term_info.academic_term
        ) subq
        LEFT JOIN assessment_grading_options
        ON subq.total_mark >= assessment_grading_options.min
        AND subq.total_mark <= assessment_grading_options.max
        GROUP BY subq.academic_period_id
            ,subq.institution_id
            ,subq.education_grade_id
            ,subq.student_id
            ,subq.academic_term
    ) subq3
    INNER JOIN report_cards
    ON report_cards.academic_period_id = subq3.academic_period_id
    AND report_cards.education_grade_id = subq3.education_grade_id
    AND subq3.assessment_period_end_date BETWEEN report_cards.start_date AND report_cards.end_date
    WHERE report_cards.id=$reportCardId
    GROUP BY report_cards.id
        ,subq3.student_id
        ,subq3.academic_period_id
        ,subq3.education_grade_id");
        $statement->execute();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
//echo "<pre>"; print_r($result); die;
        if (!empty($result)) {
            foreach ($result as $val) {
                $gpa = $val['gpa_per_student'];

            }
        }

        $this->saveGpaForStudent($gpa, $studentId, $selectedAcademicPeriodId, $educationGradeId, $institutionId);
        return true;
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
        $reportCardId = $this->request->getQuery('report_card_id');
        $isAdmin = $this->AccessControl->isAdmin();
        if (!$isAdmin) {
            $security_role_ids = $this->getUserSecurityRoles();
            $SecurityRoleFunctions = TableRegistry::get('Security.SecurityRoleFunctions');
            $SecurityFunctions = TableRegistry::get('Security.SecurityFunctions');
            $where = [$SecurityRoleFunctions->aliasField('security_role_id IN') => $security_role_ids];
        }
        $canGenerate = $this->AccessControl->check(['Institutions', 'ReportCardStatuses', 'generate']);
        if(!empty($reportCardId)){
            $generateUrl = $this->setQueryString($this->url('generate'), $params);
            
            $buttons['generate'] = [
                'label' => '<i class="fa fa-refresh"></i>' . __('Generate'),
                'attr' => $indexAttr,
                'url' => $generateUrl,
            ];
        }
                    
        return $buttons;
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getInstitutionGpaTab(); 
    }

    private function saveGpaForStudent($gpa, $studentId, $selectedAcademicPeriodId, $educationGradeId, $institutionId)
    {
        $InstitutionStudentsGpa = TableRegistry::get('Institution.InstitutionStudentsGpa');

        $checkGpa = $InstitutionStudentsGpa->find()
            ->where([
                'academic_period_id' => $selectedAcademicPeriodId,
                'student_id' => $studentId,
                'education_grade_id' => $educationGradeId,
                'institution_id' => $institutionId
            ])
            ->first();

//echo "<pre>"; print_r([$selectedAcademicPeriodId,$studentId, $educationGradeId,$institutionId]); die;
        if (empty($checkGpa)) {
            $data = [
                'student_id' => $studentId,
                'academic_period_id' => $selectedAcademicPeriodId,
                'education_grade_id' => $educationGradeId,
                'gpa' => $gpa,
                'institution_id' => $institutionId,
                'created_user_id' => 2,
                'created' => FrozenTime::now(),
            ];
            $gradingOptionEntity = $InstitutionStudentsGpa->newEntity($data);

            if ($InstitutionStudentsGpa->save($gradingOptionEntity)) {
                return true;
            } else {
                // Handle validation errors or other issues
                return false;
            }
        } else {
            $updateResult = $InstitutionStudentsGpa->updateAll(
                [
                    'gpa' => $gpa,
                    'modified_user_id' => 2,
                    'modified' => FrozenTime::now(),
                ],
                [
                    'student_id' => $studentId,
                    'academic_period_id' => $selectedAcademicPeriodId,
                    'education_grade_id' => $educationGradeId,
                    'institution_id' => $institutionId,
                ]);

            if ($updateResult > 0) {
                debug('Update successful');
            } else {
                debug('No rows updated');
            }

        }
    }

    public function onGetGpa(Event $event, Entity $entity)
    {
        $findGpa =  0.00;
        $studentsGpa = TableRegistry::get('Institution.InstitutionStudentsGpa');
        $institutionId = $entity['institution']['id'];
        $findGpa = $studentsGpa->find()->where(['student_id'=>$entity->student_id,
                        'education_grade_id'=>$entity->education_grade_id,'institution_id'=>$institutionId,'academic_period_id'=>$entity->academic_period_id])->first();
        if($findGpa != null){
            return $findGpa->gpa;
        }
        return $findGpa;
    }
    public function onGetCreated(Event $event, Entity $entity)
    {
        $studentsGpa = TableRegistry::get('Institution.InstitutionStudentsGpa');
        $institutionId = $entity['institution']['id'];
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


    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'created' || $field == 'modified') {
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
        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $getName = $InstitutionClasses->find()
                    ->where([$InstitutionClasses->aliasField('id') => $entity->institution_class_id])
                    ->first()
                    ->name;
        return $getName ;
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->where([$this->aliasField('student_id IS') => $entity->student_id]);
    }

}
