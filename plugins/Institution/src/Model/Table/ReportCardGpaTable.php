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
use Cake\Http\Session; // POCOR-9162

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
        $gpa_id = intval($this->request->getQuery('gpa_name'));
        if($gpa_id < 1){
            $gpa_id = $this->getQueryString('gpa_id');
        }
        $params = [
            'student_id' => $entity->student_id,
            'institution_id' => $entity->institution_id,
            'academic_period_id' => $entity->academic_period_id,
            'education_grade_id' => $entity->education_grade_id,
            'institution_class_id' => $entity->institution_class_id,
            'gpa_id' => $gpa_id,
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
        $this->field('education_grades_gpa_id', ['type' => 'hidden']);
        $this->field('gpa');
        $this->field('created',['visible' => true, 'sort' => false,'label' => 'Updated']);

        $this->fields['academic_period_id']['visible'] = false;

    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $institutionId = $this->getInstitutionID();
        $params = $this->request->getQuery();
        $encodedParams = $this->getQueryString();

        // Handle GPA ID resolution
        $gpaId = intval(
            $params['gpa_id'])
            ?? $encodedParams['gpa_id']
            ?? -1;
        $gpa_id = $gpaId;
        // Academic Period ID
        $academicPeriodId = intval(
            $params['academic_period_id']
            ?? $encodedParams['academic_period_id']
            ?? $this->AcademicPeriods->getCurrent()
        );

        // Education Grade ID
        $educationGradeId = intval(
            $params['education_grade_id']
            ?? $encodedParams['education_grade_id']
            ?? -1
        );
        $education_grade_id = $educationGradeId;
        // Class ID
        $institutionClassId = intval($params['institution_class_id'])
            ?? $encodedParams['institution_class_id']
            ?? -1;

        $institutionClassId = ($educationGradeId > 0) ? intval($institutionClassId) : -1;
        $institution_class_id = $institutionClassId;

        // Load tables dynamically
        $Classes = self::getDynamicTableInstance('Institution.InstitutionClasses');
        $GpaSystems = self::getDynamicTableInstance('Gpa.GpaSystem');
        $InstitutionGrades = self::getDynamicTableInstance('Institution.InstitutionGrades');
        $UsersTable = self::getDynamicTableInstance('Security.Users');
        $GpaTable = self::getDynamicTableInstance('Institution.InstitutionStudentsGpa');

        // Academic Period filter dropdown
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $this->controller->set(compact('academicPeriodOptions', 'academicPeriodId'));

        // Set selected academic period - POCOR-9185
        $selectedAcademicPeriod = !is_null($this->request->getQuery('academic_period_id')) ? $this->request->getQuery('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('selectedAcademicPeriod'));

        // Education Grade options
        $availableGrades = $InstitutionGrades->find()
            ->where([
                $InstitutionGrades->aliasField('academic_period_id') => $academicPeriodId,
                $InstitutionGrades->aliasField('institution_id') => $institutionId,
            ])
            ->extract('education_grade_id')
            ->toArray();

        $educationGradeOptions = !empty($availableGrades)
            ? ['-1' => '-- ' . __('Select Education Grade') . ' --'] + $this->EducationGrades->find('list')
                ->where([$this->EducationGrades->aliasField('id IN') => $availableGrades])
                ->toArray()
            : ['-1' => '-- ' . __('Select Education Grade') . ' --'];

        if (empty($availableGrades)) {
            $this->Alert->warning('ReportCardStatuses.noProgrammes');
        }

        $this->controller->set(compact('educationGradeOptions', 'education_grade_id'));

        // Class options
        $classOptions = ['-1' => '-- ' . __('Select Class') . ' --'];
        if ($educationGradeId > 0) {
            $classes = $Classes->find('list')
                ->matching('ClassGrades')
                ->where([
                    $Classes->aliasField('academic_period_id') => $academicPeriodId,
                    $Classes->aliasField('institution_id') => $institutionId,
                    'ClassGrades.education_grade_id' => $educationGradeId
                ])
                ->order([$Classes->aliasField('name')])
                ->toArray();

            if (!empty($classes)) {
                $classes['all'] = __('All Classes');
                $classOptions += $classes;
            } else {
                $institutionClassId = -1;
            }
        }


        $this->controller->set(compact('classOptions', 'institution_class_id'));

        // GPA Name options
        $gpaOptions = $GpaSystems->find('list')
            ->where([
                $GpaSystems->aliasField('academic_period_id') => $academicPeriodId,
                $GpaSystems->aliasField('education_grade_id') => $educationGradeId
            ])
            ->toArray();

        $gpaOptions = array_filter($gpaOptions) ?: ['-1' => '-- ' . __('Select GPA Name') . ' --'];
        $gpaOptions = ['-1' => '-- ' . __('Select GPA Name') . ' --'] + $gpaOptions;
        $this->controller->set(compact('gpaOptions', 'gpa_id'));

        // WHERE conditions
        $where = [
            $this->aliasField('institution_id') => $institutionId,
            $this->aliasField('academic_period_id') => $academicPeriodId,
            $this->aliasField('education_grade_id') => $educationGradeId,
            $this->aliasField('student_status_id NOT IN') => 3,
        ];

        if ($institutionClassId !== 'all' && $institutionClassId > 0) {
            $where[$this->aliasField('institution_class_id IS')] = $institutionClassId;
        }

        // LEFT JOIN condition for GPA
        $leftJoin = [
            $GpaTable->aliasField('student_id') . ' = ' . $this->aliasField('student_id')
        ];
        if ($gpaId > 0) {
            $leftJoin[] = $GpaTable->aliasField('education_grades_gpa_id') . ' = ' . $gpaId;
        }


        // Final query build
        $query
            ->select([
                'id' => $this->aliasField('id'),
                'institution_id' => $this->aliasField('institution_id'),
                'institution_class_id' => $this->aliasField('institution_class_id'),
                'education_grade_id' => $this->aliasField('education_grade_id'),
                'academic_period_id' => $this->aliasField('academic_period_id'),
                'student_id' => $this->aliasField('student_id'),
                'education_grades_gpa_id' => $GpaTable->aliasField('education_grades_gpa_id'),
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
                [$GpaTable->getAlias() => $GpaTable->getTable()],
                $leftJoin
            )
            ->where($where)
            ->group([$this->aliasField('student_id'),
                $GpaTable->aliasField('education_grades_gpa_id')
            ]);

        // Default ordering if no sorting param is set
        if (is_null($this->request->getQuery('sort'))) {
            $query->contain('Users')->order(['Users.first_name', 'Users.last_name']);
        }

        // Controls
        $encodedQueryString = $this->request->getParam('pass')[1];
        $extra['elements']['controls'] = [
            'name' => 'Institution.Gpa/controls',
            'data' => ['encodedQueryString' => $encodedQueryString],
            'options' => [],
            'order' => 1
        ];

        // Sorting whitelist
        $defaultSort = ['report_card_status', 'Users.first_name', 'Users.openemis_no'];
        $extra['options']['sortWhitelist'] = array_merge($extra['options']['sortWhitelist'] ?? [], $defaultSort);

        // Search logic
        if ($search = $this->getSearchKey()) {
            $extra['OR'] = $this->getNameSearchConditions([
                'alias' => 'Users',
                'searchTerm' => $search
            ]);
        }
    }


    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $gradeId = $this->request->getQuery('education_grade_id');
        //POCOR-9170[START]
        // $classId = $this->request->getQuery('class_id');
        $classId = $this->request->getQuery('institution_class_id');
        //POCOR-9170[END]
        $gpaName = $this->request->getQuery('gpa_name'); //POCOR-9038
        $gpaId = $this->request->getQuery('gpa_id'); //POCOR-9170


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
        // if ($canGenerateAll && isset($gpaName)) {
        if ($canGenerateAll && isset($gpaId)) { // POCOR-9170
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
        $params = $this->getQueryString() ?? [];
        $url = $this->url('index');
        $this->AcademicPeriods =self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods'); // POCOR-9162 start

        if ($params) {
            self::addGpaReportCards( // POCOR-9162
                $params['student_id'],
                $params['academic_period_id'],
                $params['institution_id'],
                $params['education_grade_id']);
            $this->Alert->success('ReportCardStatuses.gpa');
        } else {
            $this->Alert->warning('ReportCardStatuses.noTemplate');
        }
        unset($params['student_id']);

        $url['1'] = $this->paramsEncode($params);
        $event->stopPropagation();
        return $this->controller->redirect($url);
    }

    public function generateAll(Event $event, ArrayObject $extra)
    {
        $this->AcademicPeriods =self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods'); // POCOR-9162

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
                self::addGpaReportCards( // POCOR-9162
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

    public static function addGpaReportCards($studentId, // POCOR-9162
                                       $academicPeriodId,
                                       $institutionId,
                                       $educationGradeId): array
    {

        // first get education grade gpas for this student
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
        foreach ($gpaIds as $gpaId) {
//            Log::debug('GPA ID: ' . $gpaId);
            $newGPA = self::insertGpaPerStudentPerGpa( // POCOR-9162
                $institutionId,
                $studentId,
                $academicPeriodId,
                $educationGradeId,
                $gpaId);
            $gpaGPAs[] = $newGPA;
        }
        return $gpaGPAs;

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
        $gpa_id = $entity->education_grades_gpa_id;
//        dd($entity);
        $query = $studentsGpa->find()->where([
            'student_id' => $entity->student_id,
            'education_grade_id' => $entity->education_grade_id,
            'institution_id' => $entity->institution_id,
            'academic_period_id' => $entity->academic_period_id
        ]);

        if(!empty($gpa_id)) {
            $query = $query->where([$studentsGpa->aliasField('education_grades_gpa_id') => $gpa_id]);
        } else {
            $query = $query->where([$studentsGpa->aliasField('education_grades_gpa_id') => -1]);
        } //POCOR-9038
        $findGpa = $query->first();
        if (is_numeric($findGpa->gpa)) {
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

        $gpaTable =self::getDynamicTableInstance('Gpa.GpaSystem');

            $gpa_id = $entity->education_grades_gpa_id;

        if(!empty($gpa_id)  &&  $gpa_id != -1) {
            $gpaRecord = $gpaTable
               ->find('all')
                ->select(['name'])
               ->where(['id' => $gpa_id])
               ->first();
        }

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
    public static function getGpaForStudentGpa(
        int $institutionId,
        int $studentId,
        int $academicPeriodId,
        int $educationGradeId,
        int $educationGradeGpaId
    ): float {
        $connection = ConnectionManager::get('default');

        $sql = "
        SELECT IFNULL(ind_gpa.gpa_per_student, 0.00) gpa,
                        ind_gpa.points_list
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
                AND IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date),
                 institution_students.student_status_id = 1,
                  institution_students.student_status_id IN (1, 7, 6, 8))
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
                       ,GROUP_CONCAT( CONCAT(subq.education_subject_id, '=', gpa_grading_options.point, '-' , subq.total_mark)) AS points_list

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
                           ,IFNULL(subq2.total_mark / term_info.total_weight ,0) total_mark
                    FROM institution_subject_students
                    INNER JOIN
                    (
                        SELECT  assessments.academic_period_id
                               ,assessments.education_grade_id
                               ,IFNULL(assessment_periods.academic_term, 1) academic_term
                               ,MIN(assessment_periods.start_date) assessment_period_start_date
                               ,MAX(assessment_periods.end_date) assessment_period_end_date,
                               IFNULL(ROUND(SUM(assessment_periods.weight), 2), 1) total_weight
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
                               ,IFNULL( ROUND( SUM(assessment_item_results.marks * assessment_periods.weight),2 ), 0 ) AS total_mark
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
                          ON subq.assessment_period_end_date >= education_grades_gpa.start_date
                                  AND subq.assessment_period_end_date <= education_grades_gpa.end_date
                             AND subq.assessment_period_start_date >= education_grades_gpa.start_date
                                 AND subq.assessment_period_start_date <= education_grades_gpa.end_date
                              AND education_grades_gpa.academic_period_id = subq.academic_period_id
                              AND education_grades_gpa.education_grade_id = subq.education_grade_id
                AND education_grades_gpa.academic_period_id = subq.academic_period_id
                AND education_grades_gpa.education_grade_id = subq.education_grade_id
                AND education_grades_gpa.id = $educationGradeGpaId
                INNER JOIN gpa_grading_options
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
            GROUP BY main_q.student_id
                ,main_q.institution_id
                ,main_q.academic_period_id
                ,main_q.education_grade_id
                ,ind_gpa.education_grades_gpa_id;
            ) gpa_final
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
        return $result['gpa'] ?? 0.00;
    }

    public static function insertGpaPerStudentPerGpa(
        $institutionId,
        $studentId,
        $academicPeriodId,
        $educationGradeId,
        $educationGradeGpaId): \Cake\Datasource\EntityInterface
    {
        $gpa = self::getGpaForStudentGpa($institutionId,
            $studentId,
            $academicPeriodId,
            $educationGradeId,
            $educationGradeGpaId);
//        $gpa = 0;
        $session = new Session();
        if (is_null($session->read('Auth.User.id'))) {
            $userId = 1;    // Super Admin
        }else {
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

        if ($existing) {
            $existing = $gpaTable->patchEntity($existing, [
                'gpa' => $gpa,
                'modified_user_id' => $userId,
                'modified' => FrozenTime::now()
            ]);
        } else {
            $existing = $gpaTable->newEntity([
                'student_id' => $studentId,
                'institution_id' => $institutionId,
                'academic_period_id' => $academicPeriodId,
                'education_grade_id' => $educationGradeId,
                'education_grades_gpa_id' => $educationGradeGpaId,
                'gpa' => $gpa,
                'created_user_id' => $userId,
                'created' => FrozenTime::now()
            ]);
        }
        $conn = $gpaTable->getConnection();
        $conn->begin();

        if ($gpaTable->save($existing)) {
            $conn->commit();
            return $existing; // or whatever
        } else {
            $conn->rollback();
            throw new \Exception("Failed to save GPA record.");
        }

    }


}
