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
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\I18n\FrozenTime;

/**
 * ReportCardGpaTable class. Generate GPA for student
 * POCOR-8222
 * This class handles operations related to the GPA data for students' report cards within the application.
 * It extends from the `ControllerActionTable` class and is responsible for interacting with the database
 * to manage the GPA data, as well as any logic needed for generating or processing report card-related information.
 */
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
        $this->ReportCards =self::getDynamicTableInstance('ReportCard.ReportCards');
        $this->ReportCardProcesses =self::getDynamicTableInstance('ReportCard.ReportCardProcesses');
        $this->addBehavior('User.AdvancedNameSearch');
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['ReportCardGpa' =>['id','student_id','academic_period_id','education_grade_id','institution_class_id']
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
        $institutionClassId = $this->request->getQuery('class_id');

       if (isset($buttons['view'])) {
            $url = [
                    'plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'ReportCardGpa',
                    0 => 'view',
                    1 => $this->paramsEncode([
                        'id' => $entity->id,
                        'institution_id' => $entity->institution_id,
                        'student_id' => $entity->student_id,
                        'institution_class_id' => $institutionClassId,
                    ]),
                ];

           // $buttons['view']['url'] = $url;
        }
        $params = [
            'student_id' => $entity->student_id,
            'institution_id' => $entity->institution_id,
            'academic_period_id' => $entity->academic_period_id,
            'education_grade_id' => $entity->education_grade_id,
            'institution_class_id' => $entity->institution_class_id,
        ];
       $encodedQueryString = $this->paramsEncode($params);

        // Generate button, all statuses
        $buttons = $this->addGenerateButton($buttons, $encodedQueryString);

        return $buttons;

    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('openemis_no', ['sort' => ['field' => 'Users.openemis_no']]);
        $this->field('student_name', ['type' => 'integer','sort' => ['field' => 'Users.first_name']]);
        $this->field('student_id', ['type' => 'hidden']);
        $this->field('next_institution_class_id', ['type' => 'hidden']);
        $this->field('institution_class_id', ['type' => 'hidden']);
        $this->field('student_status_id', ['type' => 'hidden']);
        $this->field('gpa_name');
        $this->field('education_grades_gpa_id');
        $this->field('gpa');
        $this->field('created',['visible' => true, 'sort' => false,'label' => 'Updated']);

        $this->fields['academic_period_id']['visible'] = false;

    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {

        $institutionId = $this->getInstitutionID();
        $params = $this->request->getQuery();
        $gpa_id = intval($params['gpa_name'] ?? -1);
        $academic_period_id = intval($params['academic_period_id'] ?? $this->AcademicPeriods->getCurrent());
        $education_grade_id = intval($params['education_grade_id'] ?? -1);
        $institution_class_id = intval($params['class_id'] ?? -1);
        $Classes =self::getDynamicTableInstance('Institution.InstitutionClasses');
        $gpaGrades =self::getDynamicTableInstance('Gpa.GpaSystem');
        $institutionGrade =self::getDynamicTableInstance('Institution.InstitutionGrades');
        // Academic Periods filter
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $this->controller->set(compact('academicPeriodOptions', 'academic_period_id'));
        $where[$this->aliasField('academic_period_id')] = $academic_period_id;
        $educationGradeOptions = [];
        $availableGrades = $institutionGrade->find()
                        ->where([
                            $institutionGrade->aliasField('academic_period_id') => $academic_period_id,
                            $institutionGrade->aliasField('institution_id') => $institutionId,
                        ])
                        ->extract('education_grade_id')
                        ->toArray();
        if (!empty($availableGrades)) {
            $educationGradeOptions = $this->EducationGrades->find('list')
                ->where([
                    $this->EducationGrades->aliasField('id IN') => $availableGrades
                ])
                ->toArray();

        } else {
            $this->Alert->warning('ReportCardStatuses.noProgrammes');
        }

        $educationGradeOptions = ['-1' => '-- '.__('Select Education Grade').' --'] + $educationGradeOptions;
        $this->controller->set(compact('educationGradeOptions', 'education_grade_id'));
        //End

        // Class filter
        $classOptions = [];

      //  $educationGradeByReportCardId = '';
            if (!empty($this->request->getQuery('education_grade_id'))) {
                $classOptions = $Classes->find('list')
                    ->matching('ClassGrades')
                    ->where([
                        $Classes->aliasField('academic_period_id') => $academic_period_id,
                        $Classes->aliasField('institution_id') => $institutionId,
                        'ClassGrades.education_grade_id' => $this->request->getQuery('education_grade_id')
                    ])
                    ->order([$Classes->aliasField('name')])
                    ->toArray();
               // $educationGradeByReportCardId = $reportCardEntity->education_grade_id;
            } else {

                $institution_class_id = -1;
            }


        if (!empty($classOptions)) {
            $classOptions['all'] = "All Classes";
        }

        $classOptions = ['-1' => '-- ' . __('Select Class') . ' --'] + $classOptions;
        $this->controller->set(compact('classOptions', 'institution_class_id'));
        if($institution_class_id != 'all'){
            $where[$this->aliasField('institution_class_id IS')] = $institution_class_id;
        }

        $where[$this->aliasField('institution_id')] = $institutionId;
        $where[$this->aliasField('student_status_id NOT IN')] = 3;
        $where[$this->aliasField('education_grade_id')] = $education_grade_id;

        //End

        // Gpa name filter
        $nameOption = $gpaGrades->find('list')
                        ->where([
                            $gpaGrades->aliasField('academic_period_id') => $academic_period_id,
                            $gpaGrades->aliasField('education_grade_id') => $education_grade_id
                        ])
                        ->toArray();
        $nameOption = array_filter($nameOption, function($value) {
            return !empty($value);
        });

        if (empty($nameOption)) {
            $nameOption = ['-1' => '-- '.__('Select GPA Name').' --'];
        } else {
            $nameOption = ['-1' => '-- '.__('Select GPA Name').' --'] + $nameOption;
        }
        $this->controller->set(compact('nameOption', 'gpa_id'));
        // End

        $UsersTable =self::getDynamicTableInstance('Security.Users');
        $gradeGpa =self::getDynamicTableInstance('Institution.InstitutionStudentsGpa');
        $query
            ->select([
                'id' => $this->aliasField('id'),
                'institution_id' => $this->aliasField('institution_id'), //POCOR-8699
                'institution_class_id' => $this->aliasField('institution_class_id'),
                'education_grade_id' => $this->aliasField('education_grade_id'),
                'academic_period_id' => $this->aliasField('academic_period_id'),
                'student_id' => $this->aliasField('student_id'),
                'education_grades_gpa_id' => $gradeGpa->aliasField('education_grades_gpa_id'),
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
            ->leftJoin(
                [$gradeGpa->getAlias() => $gradeGpa->getTable()],
                [$gradeGpa->aliasField('student_id') . ' = ' . $this->aliasField('student_id'),
                    $gradeGpa->aliasField('education_grades_gpa_id') . ' = ' . $gpa_id
                ]
            )
            ->where($where)
            ->group([$this->aliasField('student_id')]);

        if (is_null($this->request->getQuery('sort'))) {
            $query
                ->contain('Users')
                ->order(['Users.first_name', 'Users.last_name']);
        }
        $encodedQueryString = $this->request->getParam('pass')[1];

        $extra['elements']['controls'] = ['name' => 'Institution.Gpa/controls', 'data' => ['encodedQueryString' => $encodedQueryString], 'options' => [], 'order' => 1];

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
        $gradeId = $this->request->getQuery('education_grade_id');
        $classId = $this->request->getQuery('class_id');
        $gpaName = $this->request->getQuery('gpa_name'); //POCOR-9038


        $isUserSuperAdmin = $this->Auth->user('super_admin');
        if (!$this->canGenerateGpa($gradeId, $classId)) {
            return;
        }

        $institutionClassExists = $this->InstitutionClasses->exists([
            $this->InstitutionClasses->getPrimaryKey() => $classId
        ]);

        if (!$institutionClassExists) {
            return;
        }

        $toolbarAttributes = $this->getToolbarAttributes();
        $params = $this->buildParams($gradeId, $classId);

        $canGenerateAll = $this->hasGenerateAllPermission($isUserSuperAdmin);

        if ($canGenerateAll && isset($gpaName)) {
            $generateButton = $this->buildGenerateButton($params, $toolbarAttributes);

            $gradeId = $this->request->getQuery('education_grade_id') ?? $gradeId;
            $reportCardData = $this->getReportCardData($gradeId);

            $hasValidDates = $this->hasValidGenerateDates($reportCardData);
            $canIgnoreDates = !$this->AccessControl->isAdmin()
                && $this->canGenerateAnyDate($gradeId)
                && $canGenerateAll;

            if ($hasValidDates || $canIgnoreDates) {
                $extra['toolbarButtons']['generateAll'] = $generateButton;
            } else {
                $generateButton['attr']['data-html'] = true;
                $extra['toolbarButtons']['generateAll'] = $generateButton;
            }
        }

    }

    private function canGenerateGpa($gradeId, $classId): bool
    {
        return !is_null($gradeId) && !is_null($classId);
    }

    private function getToolbarAttributes(): array
    {
        return [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
    }

    private function buildParams($gradeId, $classId): array
    {
        return [
            'institution_id' => $this->getInstitutionID(),
            'institution_class_id' => $classId,
            'education_grade_id' => $gradeId
        ];
    }

    private function hasGenerateAllPermission(bool $isSuperAdmin): bool
    {
        if($isSuperAdmin){
            return true;
        }
        $loginUserIdUser = $this->Auth->User('id');
        $securityRoles = $this->AccessControl->getRolesByUser($loginUserIdUser)->toArray();
        $securityRoleIds = [];
        foreach ($securityRoles as $key => $value) {
            $securityRoleIds[] = $value->security_role_id;
        }
        $SecurityFunctions =self::getDynamicTableInstance('Security.SecurityFunctions');
        $generateAllFunction = $SecurityFunctions
            ->find()
            ->where([$SecurityFunctions->aliasField('name') => 'Gpa Generate All'])
            ->first();

        if (empty($generateAllFunction)) {
            return false;
        }

        $SecurityRoleFunctions =self::getDynamicTableInstance('Security.SecurityRoleFunctions');
        $conditions = [
            $SecurityRoleFunctions->aliasField('security_function_id') => $generateAllFunction->id
        ];

        if (!$isSuperAdmin) {
            $conditions += [
                $SecurityRoleFunctions->aliasField('_execute') => 1,
                $SecurityRoleFunctions->aliasField('security_role_id IN') => $securityRoleIds
            ];
        }

        return $SecurityRoleFunctions->find()->where($conditions)->count() > 0;
    }

    private function buildGenerateButton(array $params, array $attributes): array
    {
        $url = $this->url('generateAll');
        $decodedParams = $this->paramsDecode($url['1']);
        $combinedParams = array_merge($url['?'] ?? [], $decodedParams, $params);
        $url['1'] = $this->paramsEncode($combinedParams);
        unset($url['?']);

        unset($url['gpa_name']);
        unset($url['academic_period_id']);
        unset($url['education_grade_id']);
        unset($url['class_id']);
//        dd($url);
        return [
            'url' => $url,
            'type' => 'button',
            'label' => '<i class="fa fa-refresh"></i>',
            'attr' => array_merge($attributes, ['title' => __('Generate All')])
        ];
    }

    private function getReportCardData($gradeId)
    {
        return $this->ReportCards
            ->find()
            ->where([$this->ReportCards->aliasField('education_grade_id') => $gradeId])
            ->first();
    }

    private function hasValidGenerateDates($reportCardData): bool
    {
        if (empty($reportCardData)) {
            return false;
        }

        $startDate = $reportCardData->generate_start_date?->format('Y-m-d');
        $endDate = $reportCardData->generate_end_date?->format('Y-m-d');
        $today = Time::now()->format('Y-m-d');

        return !empty($startDate) && !empty($endDate) && $today >= $startDate && $today <= $endDate;
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
        $this->field('institution_class_id', ['visible' => false]);
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
        if ($params) {
            $this->addGpaReportCards(
                $params['student_id'],
                $params['academic_period_id'],
                $params['institution_id'],
                $params['education_grade_id']);
            $this->Alert->success('ReportCardStatuses.gpa');
        } else {
            $url = $this->url('index');
            $this->Alert->warning('ReportCardStatuses.noTemplate');
        }

        $event->stopPropagation();
        $url = $this->url('index');
        unset($params['student_id']);
        $url['1'] = $this->paramsEncode($params);
        return $this->controller->redirect($this->url('index'));
    }

    public function generateAll(Event $event, ArrayObject $extra)
    {

        $params = $this->getQueryString();
        $institutionId = $this->getInstitutionID();
//        dd($params);
        $selectedAcademicPeriodId = $params['academic_period_id'];

        if ($params) {
            $fetchAllRecord = $this->find()
            ->select([
                'student_id' => $this->aliasField('student_id'),
                'education_grade_id' => $this->aliasField('education_grade_id'),
            ])
            ->where(['institution_id' => $institutionId ,
                'institution_class_id IS' => $params['institution_class_id'],
                'academic_period_id' =>
                    $params['academic_period_id']
            ])->toArray();
            foreach($fetchAllRecord as $value){
                $studentId = $value['student_id'];
                $educationGradeId = $params['education_grade_id'];
                $this->addGpaReportCards(
                    $studentId,
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

    private function addGpaReportCards($studentId,
                                       $academicPeriodId,
                                       $institutionId,
                                       $educationGradeId)
    {

        $this->AcademicPeriods =self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods');
        // first get education grade gpas for this student
        $educationGradeGpaId = 1;
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
        foreach ($gpaIds as $gpaId) {
            $this->insertGpaPerStudentPerGpa($institutionId,
                $studentId,
                $academicPeriodId,
                $educationGradeId,
                $gpaId);
        }

    }

    /**
     * @param array $buttons
     * @param $encodedQueryParams
     * @return array
    */
    private function addGenerateButton(array $buttons, $encodedQueryParams)
    {

        $indexAttr = ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false];
        $educationGradeId = $this->request->getQuery('education_grade_id');
        $isAdmin = $this->AccessControl->isAdmin();
        if (!$isAdmin) {
            $security_role_ids = $this->getUserSecurityRoles();
            $SecurityRoleFunctions =self::getDynamicTableInstance('Security.SecurityRoleFunctions');
            $SecurityFunctions =self::getDynamicTableInstance('Security.SecurityFunctions');
            $where = [$SecurityRoleFunctions->aliasField('security_role_id IN') => $security_role_ids];
        }

        $canGenerate = $this->AccessControl->check(['Institutions', 'ReportCardGpa', 'generate']);
        if (!($isAdmin) && $canGenerate == 1 || $canGenerate == 0) {
            $canGenerateData = $SecurityFunctions
                ->find()
                ->where([
                    $SecurityFunctions->aliasField('name') => 'GpaGenerate'
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
            $url = $this->url('generate');
            $url['1'] = $encodedQueryParams;
            unset($url['?']);
            unset($url['gpa_name']);
            unset($url['academic_period_id']);
            unset($url['education_grade_id']);
            unset($url['class_id']);

            $canGenerateAnyDate = false;
            if ($isAdmin) {
                $canGenerateAnyDate = true;
            }
            if (!$canGenerateAnyDate) {
                $canGenerateAnyDate = $this->canGenerateAnyDate();
            }
            if ($canGenerateAnyDate) {
                $buttons['generate'] = [
                    'label' => '<i class="fa fa-refresh"></i>' . __('Generate'),
                    'attr' => $indexAttr,
                    'url' => $url
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
                $date = FrozenTime::now()->format('Y-m-d');

                $canGenerateData = $SecurityFunctions
                    ->find()
                    ->where([
                        $SecurityFunctions->aliasField('name') => 'GpaGenerate'])
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
                            'url' => $url
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

   public function onGetGpa(Event $event, Entity $entity)
    {
        $studentsGpa =self::getDynamicTableInstance('Institution.InstitutionStudentsGpa');
        $institutionId = !empty($entity['institution_id']) ? $entity['institution_id'] : $entity['institution_class']['institution_id']; //POCOR-8699
        $query = $studentsGpa->find()->where([
            'student_id' => $entity->student_id,
            'education_grade_id' => $entity->education_grade_id,
            'institution_id' => $institutionId,
            'academic_period_id' => $entity->academic_period_id
        ]);

        if(!empty($this->request->getQuery('gpa_name')) &&  $this->request->getQuery('gpa_name') != -1) {
            $query = $query->where([$studentsGpa->aliasField('education_grades_gpa_id') => $this->request->getQuery('gpa_name')]);
        } else {
            $query = $query->where([$studentsGpa->aliasField('education_grades_gpa_id') . ' IS NOT' => null]);

        } //POCOR-9038
        $findGpa = $query->first();
        if ($findGpa !== null) {
            return number_format((float)$findGpa->gpa, 2);
        }
        return '';
    }

    public function onGetCreated(Event $event, Entity $entity)
    {
        if($this->action == 'index' && !empty($entity->gpa)){
            $studentsGpa =self::getDynamicTableInstance('Institution.InstitutionStudentsGpa');
            $institutionId = !empty($entity['institution_id']) ? $entity['institution_id'] : $entity['institution_class']['institution_id']; //POCOR-8699
            $query = $studentsGpa->find()
                ->where([
                    'student_id' => $entity->student_id,
                    'education_grade_id' => $entity->education_grade_id,
                    'institution_id' => $institutionId,
                    'academic_period_id' => $entity->academic_period_id
                ]);

            //POCOR-8699
            if(!empty($this->request->getQuery('gpa_name')) &&  $this->request->getQuery('gpa_name') != -1) {
                $query = $query->where([$studentsGpa->aliasField('education_grades_gpa_id') => $this->request->getQuery('gpa_name')]);
            }
            $record = $query->first();
            if ($record) {
                // Return the modified date if it's not null, otherwise return the created date
                return !empty($record->modified) ? $record->modified : $record->created;
            }

        }
        return null;
    }


    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        if (($field == 'created' || $field == 'modified') && $this->action == 'index') {
            return 'Updated';
        }elseif($field == 'gpa') {
            return 'GPA';
        }else if ($field == 'gpa_name') {
            return  __('GPA Name');
        }else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetStudentName(Event $event, Entity $entity)
    {
        return $entity->user->name;
    }

    public function onGetInstitutionClass(Event $event, Entity $entity)
    {
        $InstitutionClasses =self::getDynamicTableInstance('Institution.InstitutionClasses');
        $getName = $InstitutionClasses->find()
                    ->where([$InstitutionClasses->aliasField('id IS') => $entity->institution_class_id])
                    ->first()
                    ->name;
        return $getName ;
    }

    private function getUserSecurityRoles()
    {
        $SecurityGroupUsers =self::getDynamicTableInstance('Security.SecurityGroupUsers');
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
            $ExcludedSecurityRoleTable =self::getDynamicTableInstance('ReportCard.ReportCardExcludedSecurityRoles');
            $ExcludedSecurityRoleCount = $ExcludedSecurityRoleTable->find('all')
                ->where([
                    'security_role_id IN' => $security_role_ids,
                   // 'report_card_id' => $report_card_id
                ])->count();
        }

        if (($ExcludedSecurityRoleCount > 0)) {
            return true;
        } else {
            return false;
        }
    }

    public function onGetGpaName(Event $event, Entity $entity)
    {
        $studentGpa =self::getDynamicTableInstance('Institution.InstitutionStudentsGpa');
        $gpaTable =self::getDynamicTableInstance('Gpa.GpaSystem');
        $institutionId = !empty($entity['institution_id']) ? $entity['institution_id'] : $entity['institution_class']['institution_id']; //POCOR-8699
        $query = $studentGpa->find()
                        ->select(['name' => $gpaTable->aliasField('name')])
                        ->leftJoin(
                            [$gpaTable->getAlias() => $gpaTable->getTable()],
                            $gpaTable->aliasField('id') . ' = ' . $studentGpa->aliasField('education_grades_gpa_id')
                        )
                        ->where([
                            $studentGpa->aliasField('academic_period_id') => $entity->academic_period_id,
                            $studentGpa->aliasField('student_id') => $entity->student_id,
                            $studentGpa->aliasField('institution_id') => $institutionId,
                            $studentGpa->aliasField('education_grade_id') => $entity->education_grade_id
                        ]);
        if(!empty($this->request->getQuery('gpa_name'))  &&  $this->request->getQuery('gpa_name') != -1) {
           $query = $query->where([$studentGpa->aliasField('education_grades_gpa_id') => $this->request->getQuery('gpa_name')]);
        }
        $gpaRecord = $query->first();
            if(!empty($gpaRecord)){
                return $gpaRecord->name ;
            }

        return '';
    }


    /*public function viewBeforeQuery(Event $event, Query $query, Entity $entity)
    {

        $query->where([$this->aliasField('institution_class_id IS') => $entity-]);

    }*/

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

    /**
     * @param $institutionId
     * @param $studentId
     * @param $academicPeriodId
     * @param $educationGradeId
     * @param $educationGradeGpaId
     * @return void
     */
    private function insertGpaPerStudentPerGpa($institutionId,
                                               $studentId,
                                               $academicPeriodId,
                                               $educationGradeId,
                                               $educationGradeGpaId): void
    {
        $gpaTable = self::getDynamicTableInstance('Institution.InstitutionStudentsGpa');
        $recordExist = $gpaTable->find()->select(['id'])->where([
            $gpaTable->aliasField('institution_id') => $institutionId,
            $gpaTable->aliasField('student_id') => $studentId,
            $gpaTable->aliasField('academic_period_id') => $academicPeriodId,
            $gpaTable->aliasField('education_grade_id') => $educationGradeId,
            $gpaTable->aliasField('education_grades_gpa_id') => $educationGradeGpaId
        ])->first();

        $loginUserId = $this->Auth->user()['id'];
        $createdUserId = $this->Auth->user()['id'];
        $connection = ConnectionManager::get('default');
        if (empty($recordExist)) {
            $sql = "INSERT INTO `institution_students_gpa` (`student_id`, `institution_id`, `academic_period_id`, `education_grade_id`, `education_grades_gpa_id`, `gpa`, `created_user_id`, `created`)
            SELECT main_q.student_id
                ,main_q.institution_id
                ,main_q.academic_period_id
                ,main_q.education_grade_id
                ,ind_gpa.education_grades_gpa_id
                ,IFNULL(ind_gpa.gpa_per_student, 0.00) gpa
                ,$createdUserId AS created_user_id -- TO MAKE IT DYNAMIC BASED ON USER_ID WHO GENERATES THE GPA
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
                WHERE institution_students.academic_period_id = :selectedAcademicPeriodId
                AND institution_students.student_id = :studentId
                AND institution_students.institution_id = :institutionId
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
                        WHERE assessments.academic_period_id = :selectedAcademicPeriodId
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
                            WHERE assessment_item_results.academic_period_id = :selectedAcademicPeriodId
                            AND assessment_item_results.student_id = :studentId
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
                            WHERE assessments.academic_period_id = :selectedAcademicPeriodId
                            AND assessment_item_student_exemptions.student_id = :studentId
                        ) exemption_details
                        ON exemption_details.assessment_id = assessment_item_results.assessment_id
                        AND exemption_details.education_subject_id = assessment_item_results.education_subject_id
                        AND exemption_details.student_id = assessment_item_results.student_id
                        AND exemption_details.institution_class_id = assessment_item_results.institution_classes_id
                        AND exemption_details.education_grade_id = assessment_item_results.education_grade_id
                        AND exemption_details.assessment_period_id = assessment_item_results.assessment_period_id
                        WHERE assessment_item_results.academic_period_id = :selectedAcademicPeriodId
                        AND assessment_item_results.student_id = :studentId
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
                    WHERE institution_subject_students.academic_period_id = :selectedAcademicPeriodId
                    AND institution_subject_students.student_id = :studentId
                    AND institution_subject_students.institution_id = :institutionId
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
                AND education_grades_gpa.id = :educationGradeGpaId
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
                    WHERE institution_students_gpa.student_id = :studentId
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
                    WHERE academic_periods.id = :selectedAcademicPeriodId
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
                WHERE students_gpa.student_id = :studentId
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
                ,ind_gpa.education_grades_gpa_id;
            ";
            $params = [
                'selectedAcademicPeriodId' => $academicPeriodId,
                'studentId' => $studentId,
                'institutionId' => $institutionId,
                'educationGradeGpaId' => $educationGradeGpaId,
                'createdUserId' => $createdUserId, // This would be dynamically set based on the logged-in user
            ];
            $connection->execute($sql, $params);
        } else {
            $statement = $connection->prepare("UPDATE institution_students_gpa
                INNER JOIN
                (
                    SELECT main_q.student_id
                        ,main_q.institution_id
                        ,main_q.academic_period_id
                        ,main_q.education_grade_id
                        ,ind_gpa.education_grades_gpa_id
                        ,IFNULL(ind_gpa.gpa_per_student, 0.00) gpa
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
                        WHERE institution_students.academic_period_id = $academicPeriodId
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
                            WHERE institution_subject_students.academic_period_id = $academicPeriodId
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
                        AND education_grades_gpa.id = $educationGradeGpaId
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
                            WHERE academic_periods.id = $academicPeriodId
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
                SET institution_students_gpa.gpa = subq4.gpa
                ,institution_students_gpa.modified_user_id = $loginUserId,
                institution_students_gpa.modified = CURRENT_TIMESTAMP();");
            $statement->execute();
        }
    }

}
