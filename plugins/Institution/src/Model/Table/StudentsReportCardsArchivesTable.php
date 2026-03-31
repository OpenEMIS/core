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
use Cake\Event\EventInterface;
use Cake\Log\Log;
use Cake\Datasource\ConnectionManager;
use App\Model\Table\ControllerActionTable;

/**
 * StudentsReportCardsArchivesTable
 *
 * Handles operations related to archived student report card data.
 * This class is responsible for managing archived records, including
 * retrieval, association, and any business logic pertaining to archived
 * student report cards.
 *
 * @ticket POCOR-8898
 */

class StudentsReportCardsArchivesTable extends ControllerActionTable
{
    private $allDayOptions = [];
    private $statusOptions = [];
    private $reportProcessList = [];

    /**
     * @var \Cake\ORM\Table|null
     */
    protected $ReportCards;

    /**
     * @var \Cake\ORM\Table|null
     */
    protected $StudentsReportCards;

    /**
     * @var \Cake\ORM\Table|null
     */
    protected $ReportCardEmailProcesses;

    /**
     * @var \Cake\ORM\Table|null
     */
    protected $ReportCardProcesses;

    /**
     * @var \Cake\ORM\Table|null
     */
    protected $institutionClass;

    // for status
    const NEW_REPORT = 1;
    const IN_PROGRESS = 2;
    const GENERATED = 3;
    const PUBLISHED = 4;
    const ERROR = -1;

    const MAX_PROCESSES = 2;

    public $fileTypes = [
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'png' => 'image/png',
        'rtf' => 'text/rtf',
        'txt' => 'text/plain',
        'csv' => 'text/csv',
        'pdf' => 'application/pdf',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'zip' => 'application/zip'
    ];

    /**
     * Initializes the table, associations, and behaviors.
     * @param array $config
     * @return void
     * Sets up table, associations, and behaviors for archived student report cards.
     */
    public function initialize(array $config): void
    {

        $this->setTable('institution_class_students');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' => 'institution_class_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('NextInstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' => 'next_institution_class_id']);
        $this->hasMany('InstitutionClassGrades', ['className' => 'Institution.InstitutionClassGrades']);

        $this->addBehavior('User.AdvancedNameSearch');

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);

        $this->ReportCards = TableRegistry::getTableLocator()->get('ReportCard.ReportCards');
        $this->StudentsReportCards = TableRegistry::getTableLocator()->get('Institution.InstitutionStudentsReportCardsArchived');
        $this->ReportCardEmailProcesses = TableRegistry::getTableLocator()->get('ReportCard.ReportCardEmailProcesses');
        $this->ReportCardProcesses = TableRegistry::getTableLocator()->get('ReportCard.ReportCardProcesses');

        $this->statusOptions = [
            self::NEW_REPORT => __('New'),
            self::IN_PROGRESS => __('In Progress'),
            self::GENERATED => __('Generated'),
            self::PUBLISHED => __('Published'),
            self::ERROR => __('Error')
        ];
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => [
                'ReportCardArchives' => ['student_id', 'institution_class_id', 'class_id', 'education_grade_id', 'academic_period_id']
            ]
        ]);
    }

    /**
     * Returns the implemented events for this table.
     * @return array
     * Lists all custom and parent events handled by this table.
     */
    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.downloadAll'] = 'downloadAll';
        $events['ControllerAction.Model.downloadAllPdf'] = 'downloadAllPdf';
        $events['ControllerAction.Model.viewPDF'] = 'viewPDF'; //POCOR-7321
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';
        return $events;
    }

    /**
     * Updates the action buttons for a given entity.
     * @param EventInterface $event
     * @param Entity $entity
     * @param array $buttons
     * @return array
     * Modifies the action buttons based on report card status and permissions.
     */
    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        // check if report card request is valid
        $reportCardId = $this->request->getQuery('report_card_id');
        // POCOR-7998 refactored
        if (is_null($reportCardId)) {
            return $buttons;
        }
        $reportExists = $this->ReportCards->exists([$this->ReportCards->getPrimaryKey() => $reportCardId]);
        if (!$reportExists) {
            return $buttons;
        }
        $indexAttr = ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false];
        $params = [
            'report_card_id' => $reportCardId,
            'student_id' => $entity->student_id,
            // 'institution_id' => $entity->institution_id, V4
            'institution_id' => $entity['institution']['id'],
            'academic_period_id' => $entity->academic_period_id,
            'education_grade_id' => $entity->education_grade_id,
        ];

        // Download button, status must be generated or published
        $canDownload = $this->AccessControl->check(['Institutions', 'InstitutionStudentsReportCards', 'download']);
        $reportHasStatus = $entity->has('report_card_status');
        if (
            $canDownload
            && $reportHasStatus
            && in_array($entity->report_card_status, [self::GENERATED, self::PUBLISHED])
        ) {

            $buttons = $this->addDownloadExcelButton($buttons, $params);

            $buttons = $this->addDownloadPdfButton($buttons, $params);
        }

        $params['institution_class_id'] = $entity->institution_class_id;
        return $buttons;
    }

    /**
     * Prepares fields and state before the index action is rendered.
     * @param EventInterface $event
     * @param ArrayObject $extra
     * @return void
     * Sets up fields, timezone, and toolbar buttons for the index view.
     */
    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        //POCOR-8898: warn if no archived records exist for this institution
        $institutionId = $this->getInstitutionID();
        $archiveTable = $this->StudentsReportCards->getTable();
        $hasArchived = ConnectionManager::get('default')->execute(
            "SELECT 1 FROM `{$archiveTable}` WHERE institution_id = {$institutionId} LIMIT 1"
        )->fetch('assoc'); //POCOR-8898
        if (empty($hasArchived)) {
            $this->Alert->warning(__('No archived student report cards found for this institution.'), ['reset' => true, 'type' => 'string']); //POCOR-8898
        }

        $this->field('openemis_no', ['sort' => ['field' => 'Users.openemis_no']]);
        $this->field('student_name', ['type' => 'integer', 'sort' => ['field' => 'Users.first_name']]);
        $this->field('student_id', ['type' => 'hidden']);
        $this->field('report_card');
        $this->field('status', ['sort' => ['field' => 'report_card_status']]);
        $this->field('started_on');
        $this->field('completed_on');
        $this->field('email_status');
        $this->fields['next_institution_class_id']['visible'] = false;
        $this->fields['academic_period_id']['visible'] = false;
        $this->fields['student_status_id']['visible'] = false;
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');

        $ConfigItem = $ConfigItems
            ->find()
            ->select(['zonevalue' => 'ConfigItems.value'])
            ->where([
                $ConfigItems->aliasField('name') => 'Time Zone'
            ])
            ->first();
        $timeZone = $ConfigItem->zonevalue;

        if (empty($timeZone)) {
            $this->Alert->warning('ReportCardStatuses.timezone');
            $timeZone = 'GMT';
        }
        try {
            $dateTimeZone = new \DateTimeZone($timeZone);
        } catch (\Exception $e) {
            $timeZone = 'GMT';
        }

        date_default_timezone_set($timeZone);

        $conn = ConnectionManager::get('default');
        $institutionId = $this->getInstitutionID();
        $ReportCardProcessesTable = TableRegistry::getTableLocator()->get('ReportCard.ReportCardProcesses');
        $entitydata = $ReportCardProcessesTable->find('all', ['conditions' => [
            'institution_id' => $institutionId,
            'status !=' => '-1'
        ]])->where([$ReportCardProcessesTable->aliasField('modified IS NOT NULL')])->toArray();

        foreach ($entitydata as $keyy => $entity) {

            $now = new DateTime();
            $currentDateTime = $now->format('Y-m-d H:i:s');
            $c_timestap = strtotime($currentDateTime);

            $modifiedDate = $entity->modified->timezone($timeZone)->format('Y-m-d H:i:s');

            if ($entity->status == 2) {
                $currentTimeZone = new DateTime();
                $modifiedDate = ($modifiedDate === null) ? $currentTimeZone : $modifiedDate;
                $m_timestap = strtotime($modifiedDate);
                $interval = abs($c_timestap - $m_timestap);
                $diff_mins = round($interval / 60);

                if ($diff_mins > 30) {
                    $entity->status = self::ERROR;
                    $entity->modified = $currentTimeZone;
                    $ReportCardProcessesTable->save($entity);
                    $StudentsReportCards = TableRegistry::getTableLocator()->get('Institution.InstitutionStudentsReportCardsArchived');
                    $StudentsReportCards->updateAll([
                        'status' => -1
                    ], ['student_id' => $entity->student_id, 'report_card_id' => $entity->report_card_id]);
                }
            }
        }

        $this->setFieldOrder(['openemis_no', 'student_name', 'report_card', 'status', 'started_on', 'completed_on', 'email_status']);


        $this->reportProcessList = $this->ReportCardProcesses
            ->find()
            ->select([
                $this->ReportCardProcesses->aliasField('report_card_id'),
                $this->ReportCardProcesses->aliasField('institution_class_id'),
                $this->ReportCardProcesses->aliasField('student_id'),
                $this->ReportCardProcesses->aliasField('institution_id'),
                $this->ReportCardProcesses->aliasField('education_grade_id'),
                $this->ReportCardProcesses->aliasField('academic_period_id')
            ])
            ->where([
                $this->ReportCardProcesses->aliasField('status') => $this->ReportCardProcesses::NEW_REPORT //POCOR-7989
            ])
            ->order([
                $this->ReportCardProcesses->aliasField('created'),
                $this->ReportCardProcesses->aliasField('student_id')
            ])
            ->enableHydration(false)
            ->toArray();

        $this->addExtraButtons($extra);
    }

    /**
     * Adds extra toolbar buttons to the view (e.g., back button).
     * @param ArrayObject $extra
     * @return void
     * Adds manual/help and back buttons to the toolbar.
     */
    private function addExtraButtons(ArrayObject $extra)
    {
        $toolbarButtons = $extra['toolbarButtons'];
        $this->addManualButton($toolbarButtons);

        $this->addBackButton($toolbarButtons);
    }


    /**
     * Adds a manual/help button to the toolbar.
     * @param $toolbarButtons
     * @return void
     * Adds a help/manual button if a manual URL exists.
     */
    private function addManualButton($toolbarButtons)
    {
        // $options  = ['Institution', 'Institutions', 'StudentReportCardsArchives'];
        // $is_manual_exist = $this->getManualUrl(...$options);
        // if (!empty($is_manual_exist)) {
        //     $btnAttr = [
        //         'class' => 'btn btn-xs btn-default icon-big',
        //         'data-toggle' => 'tooltip',
        //         'data-placement' => 'bottom',
        //         'escape' => false,
        //         'target' => '_blank'
        //     ];

        //     $customButtonName = 'help';
        //     $customButtonUrl = $is_manual_exist['url'];
        //     $customButtonLabel = '<i class="fa fa-question-circle"></i>';
        //     $customButtonTitle = __('Help');
        //     $this->generateButton($toolbarButtons, $customButtonName, $customButtonTitle, $customButtonLabel, $customButtonUrl, $btnAttr);
        // }
    }

    /**
     * Adds a custom button to the toolbar.
     * @param ArrayObject $toolbarButtons
     * @param $name
     * @param $title
     * @param $label
     * @param $url
     * @param null $btnAttr
     * @return void
     * Helper to add a custom button to the toolbar.
     */
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
        $toolbarButtons[$name] = $customButton;
    }

    /**
     * Adds a back button to the toolbar.
     * @param $toolbarButtons
     * @return void
     * Adds a back navigation button to the toolbar.
     */
    private function addBackButton($toolbarButtons)
    {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $customButtonUrl = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'ReportCardStatuses',
            '0' => 'index',
            $encodedQueryString,
        ];
        $customButtonName = 'back';
        $customButtonLabel = '<i class="fa kd-back"></i>';
        $customButtonTitle = __('Back');
        $this->generateButton($toolbarButtons, $customButtonName, $customButtonTitle, $customButtonLabel, $customButtonUrl);
    }


    /**
     * Prepares the query and sets up filters before fetching index data.
     * @param EventInterface $event
     * @param Query $query
     * @param ArrayObject $extra
     * @return void
     * Sets up filters and query conditions for the index data.
     */
    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $institutionId = $this->getInstitutionID();
        $Classes = $this->InstitutionClasses;
        $InstitutionGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');

        //POCOR-8898: start — filters built from archive table only, so dropdowns show only what was actually archived
        $archiveTable = $this->StudentsReportCards->getTable();

        // Academic Periods filter — only periods present in the archive for this institution
        $archivedPeriodIds = ConnectionManager::get('default')->execute(
            "SELECT DISTINCT academic_period_id FROM `{$archiveTable}` WHERE institution_id = {$institutionId}"
        )->fetchAll('assoc'); //POCOR-8898
        $archivedPeriodIds = array_column($archivedPeriodIds, 'academic_period_id'); //POCOR-8898

        $academicPeriodOptions = [];
        if (!empty($archivedPeriodIds)) {
            $academicPeriodOptions = $this->AcademicPeriods->find('list')
                ->where([$this->AcademicPeriods->aliasField('id IN') => $archivedPeriodIds])
                ->order([$this->AcademicPeriods->aliasField('order')])
                ->toArray(); //POCOR-8898
        }
        $selectedAcademicPeriod = !is_null($this->request->getQuery('academic_period_id'))
            ? $this->request->getQuery('academic_period_id')
            : (!empty($archivedPeriodIds) ? reset($archivedPeriodIds) : null); //POCOR-8898
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;

        // Report Cards filter — only report cards present in the archive for this institution + period
        $archivedReportCardIds = ConnectionManager::get('default')->execute(
            "SELECT DISTINCT report_card_id FROM `{$archiveTable}`
             WHERE institution_id = {$institutionId}
               AND academic_period_id = " . (int)$selectedAcademicPeriod
        )->fetchAll('assoc'); //POCOR-8898
        $archivedReportCardIds = array_column($archivedReportCardIds, 'report_card_id'); //POCOR-8898

        $reportCardOptions = [];
        if (!empty($archivedReportCardIds)) {
            $reportCardOptions = $this->ReportCards->find('list')
                ->where([$this->ReportCards->aliasField('id IN') => $archivedReportCardIds])
                ->toArray(); //POCOR-8898
        }
        $reportCardOptions = ['-1' => '-- ' . __('Select Report Card') . ' --'] + $reportCardOptions;
        $selectedReportCard = !is_null($this->request->getQuery('report_card_id')) ? $this->request->getQuery('report_card_id') : -1;
        $this->controller->set(compact('reportCardOptions', 'selectedReportCard'));

        // Class filter — only classes present in the archive for this institution + period + report card
        $classOptions = [];
        $selectedClass = !is_null($this->request->getQuery('class_id')) ? $this->request->getQuery('class_id') : -1;
        $educationGradeByReportCardId = '';
        if ($selectedReportCard != -1) {
            $archivedClassIds = ConnectionManager::get('default')->execute(
                "SELECT DISTINCT institution_class_id FROM `{$archiveTable}`
                 WHERE institution_id = {$institutionId}
                   AND academic_period_id = " . (int)$selectedAcademicPeriod . "
                   AND report_card_id = " . (int)$selectedReportCard
            )->fetchAll('assoc'); //POCOR-8898
            $archivedClassIds = array_column($archivedClassIds, 'institution_class_id'); //POCOR-8898

            if (!empty($archivedClassIds)) {
                $classOptions = $Classes->find('list')
                    ->where([$Classes->aliasField('id IN') => $archivedClassIds])
                    ->order([$Classes->aliasField('name')])
                    ->toArray(); //POCOR-8898
            }

            $reportCardEntity = $this->ReportCards->find()->where(['id' => $selectedReportCard])->first();
            if (!empty($reportCardEntity)) {
                $educationGradeByReportCardId = $reportCardEntity->education_grade_id; //POCOR-7212
            } else {
                $selectedClass = -1;
            }
        }

        if (!empty($classOptions)) {
            $classOptions['all'] = "All Classes";
        }

        $classOptions = ['-1' => '-- ' . __('Select Class') . ' --'] + $classOptions;
        $this->controller->set(compact('classOptions', 'selectedClass'));
        //POCOR-8898: end
        $where[$this->aliasField('institution_class_id')] = $selectedClass;
        $where[$this->aliasField('institution_id')] = $institutionId; //POCOR-6817
        $where[$this->aliasField('student_status_id NOT IN')] = 3; //POCOR-6817

        if (!empty($educationGradeByReportCardId)) {
            $where[$this->aliasField('education_grade_id')] = $educationGradeByReportCardId;
        }
        //End
        $UsersTable = TableRegistry::get('Security.Users');
        $query
            ->select([
                'institution_class_id' => $this->aliasField('institution_class_id'),
                'education_grade_id' => $this->aliasField('education_grade_id'),
                'academic_period_id' => $this->aliasField('academic_period_id'),
                'report_card_id' => $this->StudentsReportCards->aliasField('report_card_id'),
                'student_id' => $UsersTable->aliasField('id'),
                'student_name' => $UsersTable->find()->func()->concat([
                    $UsersTable->aliasfield('first_name') => 'literal',
                    "  ",
                    $UsersTable->aliasfield('last_name') => 'literal'
                ]),
                'openemis_no' => $UsersTable->aliasField('openemis_no'),
                'report_card_status' => $this->ReportCardProcesses->aliasField('status'), //POCOR-9228
                'report_card_started_on' => $this->StudentsReportCards->aliasField('started_on'),
                'report_card_completed_on' => $this->StudentsReportCards->aliasField('completed_on'),
                'email_status_id' => $this->ReportCardEmailProcesses->aliasField('status'),
                'email_error_message' => $this->ReportCardEmailProcesses->aliasField('error_message')
            ])
            ->contain(['StudentStatuses' => function ($q) {
                return $q->where(['StudentStatuses.code NOT IN ' => ['WITHDRAWN']]);
            }])
            ->innerJoin([$UsersTable->getAlias() => $UsersTable->getTable()], [
                $UsersTable->aliasField('id = ') . $this->aliasField('student_id')
            ])
            ->innerJoin( //POCOR-8898: INNER JOIN — only show students who have an archived record
                [$this->StudentsReportCards->getAlias() => $this->StudentsReportCards->getTable()],
                [
                    $this->StudentsReportCards->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $this->StudentsReportCards->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $this->StudentsReportCards->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                    $this->StudentsReportCards->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
                    $this->StudentsReportCards->aliasField('report_card_id = ') . $selectedReportCard
                ]
            )
            ->leftJoin(
                [$this->ReportCardEmailProcesses->getAlias() => $this->ReportCardEmailProcesses->getTable()],
                [
                    $this->ReportCardEmailProcesses->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $this->ReportCardEmailProcesses->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $this->ReportCardEmailProcesses->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                    $this->ReportCardEmailProcesses->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),

                    $this->ReportCardEmailProcesses->aliasField('report_card_id = ') . $selectedReportCard
                ]
            )
            ->leftJoin(
                [$this->ReportCardProcesses->getAlias() => $this->ReportCardProcesses->getTable()],
                [
                    $this->ReportCardProcesses->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $this->ReportCardProcesses->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $this->ReportCardProcesses->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                    $this->ReportCardProcesses->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
                    $this->ReportCardProcesses->aliasField('report_card_id = ') . $selectedReportCard
                ]
            )
            ->where($where)
            ->all();

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

    /**
     * Post-processes the data after the index query is run.
     * @param EventInterface $event
     * @param Query $query
     * @param ResultSet $data
     * @param ArrayObject $extra
     * @return void
     * Handles post-processing after index data is fetched.
     */
    public function indexAfterAction(EventInterface $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $reportCardId = $this->request->getQuery('report_card_id');
        $classId = $this->request->getQuery('class_id');

        $loginUserIdUser = $this->Auth->User('id');
        $securityRoles = $this->AccessControl->getRolesByUser($loginUserIdUser)->toArray();
        $securityRoleIds = [];
        foreach ($securityRoles as $key => $value) {
            $securityRoleIds[] = $value->security_role_id;
        }
        $userSuperAddmin = $this->Session->read('Auth.User.super_admin');
        if ($userSuperAddmin == 1) {
            if (!is_null($reportCardId) && !is_null($classId)) {
                $existingReportCard = $this->ReportCards->exists([$this->ReportCards->getPrimaryKey() => $reportCardId]);
                $existingClass = $this->InstitutionClasses->exists([$this->InstitutionClasses->getPrimaryKey() => $classId]);
                // only show toolbar buttons if request for report card and class is valid
                if ($existingReportCard && $existingClass) {
                    $generatedCount = 0;
                    $publishedCount = 0;
                    $dataCount = count($data);
                    // count statuses to determine which buttons are shown
                    foreach ($data as $student) {
                        if ($student->has('report_card_status')) {
                            if ($student->report_card_status == self::GENERATED) {
                                $generatedCount += 1;
                            } else if ($student->report_card_status == self::PUBLISHED) {
                                $publishedCount += 1;
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
                    $SecurityFunctionsAllExcelData = $SecurityFunctions
                        ->find()
                        ->where([
                            $SecurityFunctions->aliasField('name') => 'Download All Excel'
                        ])
                        ->first();

                    $SecurityRoleFunctionsTable = TableRegistry::get('Security.SecurityRoleFunctions');
                    $SecurityRoleFunctionsTableAllExcelData = $SecurityRoleFunctionsTable
                        ->find()
                        ->where([
                            $SecurityRoleFunctionsTable->aliasField('security_function_id') => $SecurityFunctionsAllExcelData->id,


                        ])
                        ->count();

                    $SecurityFunctions = TableRegistry::get('Security.SecurityFunctions');
                    $SecurityFunctionsAllPdfData = $SecurityFunctions
                        ->find()
                        ->where([
                            $SecurityFunctions->aliasField('name') => 'Download All Pdf'
                        ])
                        ->first();

                    $SecurityRoleFunctionsTable = TableRegistry::get('Security.SecurityRoleFunctions');
                    $SecurityRoleFunctionsTableAllPdfData = $SecurityRoleFunctionsTable
                        ->find()
                        ->where([
                            $SecurityRoleFunctionsTable->aliasField('security_function_id') => $SecurityFunctionsAllPdfData->id,

                        ])
                        ->count();
                    // Download all button
                    if ($generatedCount > 0 || $publishedCount > 0) {
                        if ($this->AccessControl->isAdmin()) {
                            $downloadButtonPdf['url'] = $this->setQueryString($this->url('downloadAllPdf'), $params);
                            $downloadButtonPdf['type'] = 'button';
                            $downloadButtonPdf['label'] = '<i class="fa kd-download"></i>';
                            $downloadButtonPdf['attr'] = $toolbarAttr;
                            $downloadButtonPdf['attr']['title'] = __('Download All PDF');
                            $extra['toolbarButtons']['downloadAllPdf'] = $downloadButtonPdf;
                        } else {
                            if ($SecurityRoleFunctionsTableAllPdfData >= 1) {
                                $downloadButtonPdf['url'] = $this->setQueryString($this->url('downloadAllPdf'), $params);
                                $downloadButtonPdf['type'] = 'button';
                                $downloadButtonPdf['label'] = '<i class="fa kd-download"></i>';
                                $downloadButtonPdf['attr'] = $toolbarAttr;
                                $downloadButtonPdf['attr']['title'] = __('Download All PDF');
                                $extra['toolbarButtons']['downloadAllPdf'] = $downloadButtonPdf;
                            }
                        }
                    }
                    if ($generatedCount > 0 || $publishedCount > 0) {
                        if ($this->AccessControl->isAdmin()) {
                            $downloadButton['url'] = $this->setQueryString($this->url('downloadAll'), $params);
                            $downloadButton['type'] = 'button';
                            $downloadButton['label'] = '<i class="fa kd-download"></i>';
                            $downloadButton['attr'] = $toolbarAttr;
                            $downloadButton['attr']['title'] = __('Download All Excel');
                            $extra['toolbarButtons']['downloadAll'] = $downloadButton;
                        } else {

                            $ExcludedSecurityRoleEntity = $this->canGenerateAnyDate($reportCardId);  //POCOR-7551

                            if (($SecurityRoleFunctionsTableAllExcelData >= 1) || ($ExcludedSecurityRoleEntity == 1)) {
                                $downloadButton['url'] = $this->setQueryString($this->url('downloadAll'), $params);
                                $downloadButton['type'] = 'button';
                                $downloadButton['label'] = '<i class="fa kd-download"></i>';
                                $downloadButton['attr'] = $toolbarAttr;
                                $downloadButton['attr']['title'] = __('Download All Excel');
                                $extra['toolbarButtons']['downloadAll'] = $downloadButton;
                            }
                        }
                    }
                }
            }
        } else {
            if (!is_null($reportCardId) && !is_null($classId) && !empty($securityRoleIds)) {
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
                            } else if ($student->report_card_status == self::PUBLISHED) {
                                $publishedCount += 1;
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


                    $SecurityFunctions = TableRegistry::getTableLocator()->get('Security.SecurityFunctions');
                    $SecurityFunctionsAllExcelData = $SecurityFunctions
                        ->find()
                        ->where([
                            $SecurityFunctions->aliasField('name') => 'Download All Excel'
                        ])
                        ->first();

                    $SecurityRoleFunctionsTable = TableRegistry::get('Security.SecurityRoleFunctions');
                    $SecurityRoleFunctionsTableAllExcelData = $SecurityRoleFunctionsTable
                        ->find()
                        ->where([
                            $SecurityRoleFunctionsTable->aliasField('security_function_id') => $SecurityFunctionsAllExcelData->id,
                            $SecurityRoleFunctionsTable->aliasField('_execute') => 1,
                            $SecurityRoleFunctionsTable->aliasField('security_role_id IN') => $securityRoleIds
                        ])
                        ->count();

                    $SecurityFunctions = TableRegistry::get('Security.SecurityFunctions');
                    $SecurityFunctionsAllPdfData = $SecurityFunctions
                        ->find()
                        ->where([
                            $SecurityFunctions->aliasField('name') => 'Download All Pdf'
                        ])
                        ->first();

                    $SecurityRoleFunctionsTable = TableRegistry::get('Security.SecurityRoleFunctions');
                    $SecurityRoleFunctionsTableAllPdfData = $SecurityRoleFunctionsTable
                        ->find()
                        ->where([
                            $SecurityRoleFunctionsTable->aliasField('security_function_id') => $SecurityFunctionsAllPdfData->id,
                            $SecurityRoleFunctionsTable->aliasField('_execute') => 1,
                            $SecurityRoleFunctionsTable->aliasField('security_role_id IN') => $securityRoleIds
                        ])
                        ->count();




                    // Download all button
                    if ($generatedCount > 0 || $publishedCount > 0) {
                        if ($this->AccessControl->isAdmin()) {
                            $downloadButtonPdf['url'] = $this->setQueryString($this->url('downloadAllPdf'), $params);
                            $downloadButtonPdf['type'] = 'button';
                            $downloadButtonPdf['label'] = '<i class="fa kd-download"></i>';
                            $downloadButtonPdf['attr'] = $toolbarAttr;
                            $downloadButtonPdf['attr']['title'] = __('Download All PDF');
                            $extra['toolbarButtons']['downloadAllPdf'] = $downloadButtonPdf;
                        } else {
                            if ($SecurityRoleFunctionsTableAllPdfData >= 1) { //POCOR-7131 change in if condition
                                $downloadButtonPdf['url'] = $this->setQueryString($this->url('downloadAllPdf'), $params);
                                $downloadButtonPdf['type'] = 'button';
                                $downloadButtonPdf['label'] = '<i class="fa kd-download"></i>';
                                $downloadButtonPdf['attr'] = $toolbarAttr;
                                $downloadButtonPdf['attr']['title'] = __('Download All PDF');
                                $extra['toolbarButtons']['downloadAllPdf'] = $downloadButtonPdf;
                            }
                        }
                    }
                    if ($generatedCount > 0 || $publishedCount > 0) {
                        if ($this->AccessControl->isAdmin()) {
                            $downloadButton['url'] = $this->setQueryString($this->url('downloadAll'), $params);
                            $downloadButton['type'] = 'button';
                            $downloadButton['label'] = '<i class="fa kd-download"></i>';
                            $downloadButton['attr'] = $toolbarAttr;
                            $downloadButton['attr']['title'] = __('Download All Excel');
                            $extra['toolbarButtons']['downloadAll'] = $downloadButton;
                        } else {
                            if ($SecurityRoleFunctionsTableAllExcelData >= 1) {
                                $downloadButton['url'] = $this->setQueryString($this->url('downloadAll'), $params);
                                $downloadButton['type'] = 'button';
                                $downloadButton['label'] = '<i class="fa kd-download"></i>';
                                $downloadButton['attr'] = $toolbarAttr;
                                $downloadButton['attr']['title'] = __('Download All Excel');
                                $extra['toolbarButtons']['downloadAll'] = $downloadButton;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Returns the list of searchable fields for the table.
     * @param EventInterface $event
     * @param ArrayObject $searchableFields
     * @return void
     * Adds fields that can be searched in the table.
     */
    public function getSearchableFields(EventInterface $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'student_id';
        $searchableFields[] = 'openemis_no';
    }

    /**
     * Prepares the view before rendering the action.
     * @param EventInterface $event
     * @param ArrayObject $extra
     * @return void
     * Sets up fields and order for the view action.
     */
    public function viewBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('academic_period_id');
        $this->field('institution_class_id', ['visible' => true]);
        $this->field('institution_class', ['visible' => true]);
        $this->field('openemis_no', ['sort' => ['field' => 'Users.openemis_no']]);
        $this->field('student_name');
        $this->field('report_card');
        $this->field('status', ['sort' => ['field' => 'report_card_status']]);
        $this->field('started_on');
        $this->field('completed_on');
        $this->field('email_status');
        $this->fields['next_institution_class_id']['visible'] = false;
        $this->fields['student_status_id']['visible'] = false;
        $this->setFieldOrder(['academic_period_id', 'institution_class', 'openemis_no', 'student_name', 'report_card', 'status', 'started_on', 'completed_on', 'report_queue', 'email_status']);
    }

    /**
     * Prepares the query before fetching data for the view action.
     * @param EventInterface $event
     * @param Query $query
     * @param ArrayObject $extra
     * @return void
     * Sets up query conditions for the view action.
     */
    public function viewBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $params = $this->request->getQuery();
        $reportCardTable = TableRegistry::getTableLocator()->get('ReportCard.ReportCards');
        $this->institutionClass = TableRegistry::get('Institution.InstitutionClasses');

        $decodeParam = $this->paramsDecode($this->request->getAttribute('params')['pass'][1]);
        $conditions = [];
        $CheckStudent = $this->StudentsReportCards->find()->where([$this->StudentsReportCards->aliasField('student_id') => $decodeParam['student_id'], $this->StudentsReportCards->aliasField('report_card_id') => $params['report_card_id']])->first();
        if (!empty($CheckStudent)) {
            $conditions[$this->StudentsReportCards->aliasField('report_card_id')] = $params['report_card_id'];
        }
        $query
            ->select([
                'report_card_id' => $this->StudentsReportCards->aliasField('report_card_id'),
                'report_card_status' => $this->StudentsReportCards->aliasField('status'),
                'report_card_started_on' => $this->StudentsReportCards->aliasField('started_on'),
                'report_card_completed_on' => $this->StudentsReportCards->aliasField('completed_on'),
                'email_status_id' => $this->ReportCardEmailProcesses->aliasField('status'),
                'email_error_message' => $this->ReportCardEmailProcesses->aliasField('error_message'),
                'student_id' => $this->aliasField('student_id'),
                'openemis_no' => 'Users.openemis_no',
                'academic_period_id' => 'AcademicPeriods.name',
                'institution_class' => 'InstitutionClasses.name',
                'student_name' => $query->func()->group_concat([
                    'Users.first_name' => 'literal',
                    " ",
                    'Users.last_name' => 'literal'
                ]),
            ])
            ->contain(['Users', 'AcademicPeriods', 'InstitutionClasses'])
            ->leftJoin(
                [$this->StudentsReportCards->getAlias() => $this->StudentsReportCards->getTable()],
                [
                    $this->StudentsReportCards->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $this->StudentsReportCards->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $this->StudentsReportCards->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                    $this->StudentsReportCards->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),

                ]
            )
            ->leftJoin(
                [$this->ReportCardEmailProcesses->getAlias() => $this->ReportCardEmailProcesses->getTable()],
                [
                    $this->ReportCardEmailProcesses->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $this->ReportCardEmailProcesses->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $this->ReportCardEmailProcesses->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                    $this->ReportCardEmailProcesses->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
                    $this->ReportCardEmailProcesses->aliasField('report_card_id = ') . $params['report_card_id']
                ]
            )
            ->where($conditions)
            ->order(['report_card_id' => 'DESC']);
    }

    /**
     * Gets the status for a given entity.
     * @param EventInterface $event
     * @param Entity $entity
     * @return string
     * Returns the status label for the report card entity.
     */
    public function onGetStatus(EventInterface $event, Entity $entity)
    {
        if ($entity->has('report_card_status')) {
            $value = $this->statusOptions[$entity->report_card_status];
        } else {
            $value = $this->statusOptions[self::NEW_REPORT];
        }
        return $value;
    }

    /**
     * Gets the started_on value for a given entity.
     * @param EventInterface $event
     * @param Entity $entity
     * @return string
     * Returns the started_on date/time in the configured timezone.
     */
    public function onGetStartedOn(EventInterface $event, Entity $entity)
    {
        $ConfigItemTable = TableRegistry::get('Configuration.ConfigItems');
        $ConfigItem = $ConfigItemTable
            ->find()
            ->select(['zonevalue' => 'ConfigItems.value'])
            ->where([
                $ConfigItemTable->aliasField('name') => 'Time Zone'
            ])
            ->first();
        $timZone = $ConfigItem->zonevalue;
        try {
            $dateTimeZone = new \DateTimeZone($timZone);
        } catch (\Exception $e) {
            $timZone = 'GMT';
        }
        $value = '';
        if ($timZone) {
            if ($entity->has('report_card_started_on')) {
                $date = new DateTime($entity->report_card_started_on, new DateTimeZone($timZone));
                $date->setTimezone(new DateTimeZone($timZone));
                $value = $date->format('F d, Y h:i:s');
            }
        }
        return $value;
    }

    /**
     * Gets the completed_on value for a given entity.
     * @param EventInterface $event
     * @param Entity $entity
     * @return string
     * Returns the completed_on date/time in the configured timezone.
     */
    public function onGetCompletedOn(EventInterface $event, Entity $entity)
    {
        $ConfigItemTable = TableRegistry::get('Configuration.ConfigItems');
        $ConfigItem = $ConfigItemTable
            ->find()
            ->select(['zonevalue' => 'ConfigItems.value'])
            ->where([
                $ConfigItemTable->aliasField('name') => 'Time Zone'
            ])
            ->first();
        $timZone = $ConfigItem->zonevalue;
        $value = '';
        if ($timZone) {
            if ($entity->has('report_card_completed_on')) {
                if (!empty($timZone)) {
                    $date = new DateTime($entity->report_card_completed_on, new DateTimeZone($timZone));
                    $date->setTimezone(new DateTimeZone($timZone));
                    $value = $date->format('F d, Y h:i:s');
                }
            }
        }
        return $value;
    }

    /**
     * Gets the report queue for a given entity.
     * @param EventInterface $event
     * @param Entity $entity
     * @return string
     * Returns the report queue position for the entity.
     */
    public function onGetReportQueue(EventInterface $event, Entity $entity)
    {
        if ($entity->has('report_card_id')) {
            $reportCardId = $entity->report_card_id;
        } else if (!is_null($this->request->getQuery('report_card_id'))) {
            $reportCardId = $this->request->getQuery('report_card_id');
        }

        $search = [
            'report_card_id' => $reportCardId,
            'institution_class_id' => $entity->institution_class_id,
            'student_id' => $entity->student_id,
            'institution_id' => $entity['institution']['id'],
            'education_grade_id' => $entity->education_grade_id,
            'academic_period_id' => $entity->academic_period_id
        ];
        $resultIndex = array_search($search, $this->reportProcessList);
        if ($resultIndex !== false) {
            $totalQueueCount = count($this->reportProcessList);
            return sprintf(__('%s of %s'), $resultIndex + 1, $totalQueueCount);
        } else {
            return '<i class="fa fa-minus"></i>';
        }
    }

    /**
     * Gets the OpenEMIS number for a given entity.
     * @param EventInterface $event
     * @param Entity $entity
     * @return string
     * Returns the OpenEMIS number for the student entity.
     */
    public function onGetOpenemisNo(EventInterface $event, Entity $entity)
    {

        $value = '';
        if ($entity->has('user')) {
            $value = $entity->user->openemis_no;
        }
        return $value;
    }


    /**
     * Gets the report card for a given entity.
     * @param EventInterface $event
     * @param Entity $entity
     * @return string
     * Returns the report card name for the entity.
     */
    public function onGetReportCard(EventInterface $event, Entity $entity)
    {
        $value = '';
        $params = $this->request->getQuery();
        if ($entity->has('report_card_id')) {
            $reportCardId = $params['report_card_id'];
        } else if (!is_null($this->request->getQuery('report_card_id'))) {
            // used if student report card record has not been created yet
            $reportCardId = $this->request->getQuery('report_card_id');
        }

        if (!empty($reportCardId)) {
            $reportCardEntity = $this->ReportCards->find()->where(['id' => $reportCardId])->first();
            if (!empty($reportCardEntity)) {
                $value = $reportCardEntity->code_name;
            }
        }
        return $value;
    }

    /**
     * Gets the email status for a given entity.
     * @param EventInterface $event
     * @param Entity $entity
     * @return string
     * Returns the email status for the report card entity.
     */
    public function onGetEmailStatus(EventInterface $event, Entity $entity)
    {
        $emailStatuses = $this->ReportCardEmailProcesses->getEmailStatus();
        $value = '<i class="fa fa-minus"></i>';

        if ($entity->has('email_status_id')) {
            $value = $emailStatuses[$entity->email_status_id];

            if ($entity->email_status_id == $this->ReportCardEmailProcesses::ERROR && $entity->has('email_error_message')) {
                $value .= '&nbsp&nbsp;<i class="fa fa-exclamation-circle fa-lg table-tooltip icon-red" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="' . $entity->email_error_message . '"></i>';
            }
        }

        return $value;
    }

    private function getUserSecurityRoles()
    {
        $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
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


    private function getDownloadButtons(array $buttons, $downloadName, $downloadUrl, $buttonName)
    {
        $indexAttr = ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false];
        $isAdmin = $this->AccessControl->isAdmin();

        if (!$isAdmin) {
            $security_role_ids = $this->getUserSecurityRoles();
            $SecurityRoleFunctions = TableRegistry::get('Security.SecurityRoleFunctions');
            $SecurityFunctions = TableRegistry::get('Security.SecurityFunctions');
            $where = [$SecurityRoleFunctions->aliasField('security_role_id IN') => $security_role_ids];
        }

        $canUserDownload = false;

        if ($isAdmin) {
            $canUserDownload = true;
        }
        if (!$isAdmin) {
            $canDownloadData = $SecurityFunctions
                ->find()
                ->where([
                    $SecurityFunctions->aliasField('name') => $downloadName
                ])
                ->first();

            //$SecurityRoleFunctions = TableRegistry::get('Security.SecurityRoleFunctions');
            $canUserDownloadData = $SecurityRoleFunctions
                ->find()
                ->where([
                    $SecurityRoleFunctions->aliasField('security_function_id') => $canDownloadData->id,
                    $SecurityRoleFunctions->aliasField('_execute') => 1,
                    $where
                ])->first();
            $canUserDownload = !empty($canUserDownloadData);
        }

        if ($canUserDownload) {

            $buttons[$buttonName] = [
                'label' => '<i class="fa kd-download"></i>' . __($downloadName),
                'attr' => $indexAttr,
                'url' => $downloadUrl
            ];
        }
        return $buttons;
    }


    private function addDownloadExcelButton(array $buttons, array $params)
    {
        $downloadExcelName = 'Download Excel';
        $buttonName = 'download';
        $downloadExcelUrl = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'InstitutionStudentsReportCardsArchived',
            '0' => $buttonName,
            '1' => $this->paramsEncode($params)
        ];
        $buttons = $this->getDownloadButtons($buttons, $downloadExcelName, $downloadExcelUrl, $buttonName);
        return $buttons;
    }


    private function addDownloadPdfButton(array $buttons, array $params)
    {
        $downloadPdfName = 'Download Pdf';
        $buttonName = 'downloadPdf';
        $downloadPdfUrl = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'InstitutionStudentsReportCardsArchived',
            '0' => $buttonName,
            '1' => $this->paramsEncode($params)
        ];
        $buttons = $this->getDownloadButtons($buttons, $downloadPdfName, $downloadPdfUrl, $buttonName);
        return $buttons;
    }

    public function downloadAll(EventInterface $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();

        // only download report cards with generated or published status
        $statusArray = [self::GENERATED, self::PUBLISHED];

        $files = $this->StudentsReportCards->find()
            ->contain(['Students', 'ReportCards'])
            ->where([
                $this->StudentsReportCards->aliasField('institution_id') => $params['institution_id'],
                $this->StudentsReportCards->aliasField('institution_class_id') => $params['institution_class_id'],
                $this->StudentsReportCards->aliasField('report_card_id') => $params['report_card_id'],
                $this->StudentsReportCards->aliasField('status IN ') => $statusArray,
                $this->StudentsReportCards->aliasField('file_name IS NOT NULL'),
                $this->StudentsReportCards->aliasField('file_content IS NOT NULL')
            ])
            ->toArray();
        ConnectionManager::get('default')->disconnect();

        if (!empty($files)) {
            $path = WWW_ROOT . 'export' . DS . 'customexcel' . DS;
            $zipName = 'ReportCards' . '_' . date('Ymd') . 'T' . date('His') . '.zip';
            $filepath = $path . $zipName;

            $zip = new ZipArchive;
            $zip->open($filepath, ZipArchive::CREATE);
            foreach ($files as $file) {
                $content = $this->getFile($file->file_content);
                if ($content !== false) {
                    $zip->addFromString($file->file_name, $content);
                    unset($content); // Free up memory
                }
            }
            $zip->close();
            header("Pragma: public", true);
            header("Expires: 0"); // set expiration time
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-Type: application/force-download");
            header("Content-Type: application/zip");
            header("Content-Length: " . filesize($filepath));
            header("Content-Disposition: attachment; filename=" . $zipName);
            readfile($filepath);
            // ob_clean();
            // flush();
            // sleep(10);

            // delete file after download
            unlink($filepath);
            exit();
        } else {
            $event->stopPropagation();
            $this->Alert->warning('ReportCardStatuses.noFilesToDownload');
            return $this->controller->redirect($this->url('index'));
        }
    }

    /*
     *  Download pdf in bulk
     * */
    public function downloadAllPdf(EventInterface $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();

        // only download report cards with generated or published status
        $statusArray = [self::GENERATED, self::PUBLISHED];

        $files = $this->StudentsReportCards->find()
            ->contain(['Students', 'ReportCards'])
            ->where([
                $this->StudentsReportCards->aliasField('institution_id') => $params['institution_id'],
                $this->StudentsReportCards->aliasField('institution_class_id') => $params['institution_class_id'],
                $this->StudentsReportCards->aliasField('report_card_id') => $params['report_card_id'],
                $this->StudentsReportCards->aliasField('status IN ') => $statusArray,
                $this->StudentsReportCards->aliasField('file_name IS NOT NULL'),
                $this->StudentsReportCards->aliasField('file_content IS NOT NULL'),
                $this->StudentsReportCards->aliasField('file_content_pdf IS NOT NULL')
            ])
            ->toArray();

        if (!empty($files)) {
            $path = WWW_ROOT . 'export' . DS . 'customexcel' . DS;
            $zipName = 'ReportCards' . '_' . date('Ymd') . 'T' . date('His') . '.zip';
            $filepath = $path . $zipName;

            $zip = new ZipArchive;
            $zip->open($filepath, ZipArchive::CREATE);

            foreach ($files as $file) {
                $fileName = $file->file_name;
                $fileNameData = explode(".", $fileName);
                $fileName = $fileNameData[0] . '.pdf';

                $zip->addFromString($fileName, $this->getFile($file->file_content_pdf));

            }
            $zip->close();

            header("Pragma: public", true);
            header("Expires: 0"); // set expiration time
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-Type: application/force-download");
            header("Content-Type: application/zip");
            header("Content-Length: " . filesize($filepath));
            header("Content-Disposition: attachment; filename=" . $zipName);
            readfile($filepath);

            // delete file after download
            unlink($filepath);
            exit();
        } else {
            $event->stopPropagation();
            $this->Alert->warning('ReportCardStatuses.noFilesToDownload');
            return $this->controller->redirect($this->url('index'));
        }
    }

    /**
     * View PDF method for displaying PDF files inline
     * POCOR-7321
     */
    public function viewPDF(EventInterface $event, ArrayObject $extra)
    {
        $ids = $this->paramsDecode($this->paramsPass(0));

        if ($this->exists($ids)) {
            $data = $this->get($ids);
            $fileName = $data->file_name;
            $fileNameData = explode(".", $fileName);
            $fileName = $fileNameData[0] . '.pdf';
            $pathInfo['extension'] = 'pdf';
            $file = $this->getFile($data->file_content_pdf);
            $fileType = 'application/pdf';

            header("Pragma: public", true);
            header("Expires: 0"); // set expiration time
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            // header("Content-Type: application/force-download");
            header("Content-Type: application/octet-stream");
            header("Content-Type: " . $fileType);
            header('Content-Disposition: inline; filename="' . $fileName . '"');

            echo $file;
        }
        exit();
    }

    /**
     * Private method to get file content from PHP resource
     * @param resource $phpResourceFile
     * @return string
     */
    private function getFile($phpResourceFile)
    {
        $file = '';
        while (!feof($phpResourceFile)) {
            $file .= fread($phpResourceFile, 8192);
        }
        fclose($phpResourceFile);

        return $file;
    }
}
