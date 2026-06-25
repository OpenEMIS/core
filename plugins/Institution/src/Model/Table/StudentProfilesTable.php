<?php
namespace Institution\Model\Table;

use ArrayObject;
use ZipArchive;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\ResultSet;
use Cake\Event\EventInterface;
use Cake\I18n\FrozenTime;
use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;
use Institution\Model\Traits\ProfilePermissionTrait; //POCOR-9598: centralised profile permission check
use Institution\Model\Traits\StaleProfileBannerTrait; //POCOR-9593: stale-profile alert banner

/**
 *
 * This class is used to generate the Student profile
 * Ticket - POCOR6286
 * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
 *
 */
class StudentProfilesTable extends ControllerActionTable
{
    use ProfilePermissionTrait; //POCOR-9598: security_role_functions execute-permission check
    use StaleProfileBannerTrait; //POCOR-9593: stale-profile alert banner

    private $statusOptions = [];
    private $reportProcessList = [];

    //POCOR-9598: security_functions name+controller for student profile buttons (portable — no hardcoded IDs)
    const GENERATE_FUNCTION_NAME = 'Generate Students Profile';
    const DOWNLOAD_FUNCTION_NAME = 'Download Students Profile';
    const FUNCTION_CONTROLLER    = 'Institutions';

    // for status
    CONST NEW_REPORT = 1;
    CONST IN_PROGRESS = 2;
    CONST GENERATED = 3;
    CONST PUBLISHED = 4;
    CONST FAILED = 5; //POCOR-9598: matches GenerateProfileCommandBase FAILED status

    CONST MAX_PROCESSES = 2;

    public $fileTypes = [
        'jpeg'  => 'image/jpeg',
        'jpg'   => 'image/jpeg',
        'gif'   => 'image/gif',
        'png'   => 'image/png',
        // 'jpeg'=>'image/pjpeg',
        // 'jpeg'=>'image/x-png'
        'rtf'   => 'text/rtf',
        'txt'   => 'text/plain',
        'csv'   => 'text/csv',
        'pdf'   => 'application/pdf',
        'ppt'   => 'application/vnd.ms-powerpoint',
        'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'doc'   => 'application/msword',
        'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'   => 'application/vnd.ms-excel',
        'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'zip'   => 'application/zip'
    ];

    public function initialize(array $config): void
    {
        $this->setTable('institution_class_students');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id', 'joinType' => 'INNER']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('NextInstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' =>'next_institution_class_id']);
        $this->hasMany('InstitutionClassGrades', ['className' => 'Institution.InstitutionClassGrades']);

        $this->addBehavior('User.AdvancedNameSearch');

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);

        $this->StudentTemplates = TableRegistry::getTableLocator()->get('ProfileTemplate.StudentTemplates');
        $this->InstitutionStudentsProfileTemplates = TableRegistry::getTableLocator()->get('Institution.InstitutionStudentsProfileTemplates');
        $this->StudentReportCardProcesses = TableRegistry::getTableLocator()->get('ReportCard.StudentReportCardProcesses');
        $this->StudentReportCardEmailProcesses = TableRegistry::getTableLocator()->get('ReportCard.StudentReportCardEmailProcesses');

        $this->statusOptions = [
            self::NEW_REPORT => __('New'),
            self::IN_PROGRESS => __('In Progress'),
            self::GENERATED => __('Generated'),
            self::PUBLISHED => __('Published'),
            self::FAILED => __('Failed'), //POCOR-9598: display label for generation failures
        ];

        $this->addBehavior('Institution.InstitutionTab');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.generate'] = 'generate';
        $events['ControllerAction.Model.generateAll'] = 'generateAll';
        $events['ControllerAction.Model.downloadAll'] = 'downloadAll';
        $events['ControllerAction.Model.downloadAllPdf'] = 'downloadAllPdf';
        $events['ControllerAction.Model.downloadExcel'] = 'downloadExcel';
        //START:POCOR-6667
        $events['ControllerAction.Model.viewPDF'] = 'viewPDF';
        //END:POCOR-6667
        $events['ControllerAction.Model.downloadPDF'] = 'downloadPDF';
        $events['ControllerAction.Model.publish'] = 'publish';
        $events['ControllerAction.Model.publishAll'] = 'publishAll';
        $events['ControllerAction.Model.unpublish'] = 'unpublish';
        $events['ControllerAction.Model.unpublishAll'] = 'unpublishAll';
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';
        $events['ControllerAction.Model.email'] = 'email';
        $events['ControllerAction.Model.emailAll'] = 'emailAll';
        return $events;
    }

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        unset($buttons['view']);
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        // check if report card request is valid
        $reportCardId = $this->request->getQuery('student_profile_template_id');
        $institutionId = $queryString['institution_id'];
        $academicPeriodId = $this->request->getQuery('academic_period_id');

        if (!is_null($reportCardId) && $this->StudentTemplates->exists([$this->StudentTemplates->getPrimaryKey() => $reportCardId])) {

            $indexAttr = ['role' => 'menuitem',
                'tabindex' => '-1',
                'escape' => false,
                'target' => '_blank'];
            $generateAttr = ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false];
            $viewAttr = ['role' => 'menuitem', // POCOR-9292 start
                'tabindex' => '-1',
                'escape' => false,
                'target' => '_blank']; // POCOR-9292 end
            $params = [
                'student_profile_template_id' => $reportCardId,
                'student_id' => $entity->student_id,
                'institution_id' => $institutionId,
                'academic_period_id' => $academicPeriodId,
                'education_grade_id' => $entity->education_grade_id,
            ];


            // Download button, status must be generated or published
            if ($this->AccessControl->check(['Institutions', 'StudentProfiles', 'downloadExcel']) 
                && $entity->has('report_card_status') && in_array($entity->report_card_status, [self::GENERATED, self::PUBLISHED])) 
            {
                //POCOR-9585
                 $downloadUrl = $this->setQueryString($this->url('downloadExcel'), $params);
                 $buttons['download'] = [
                     'label' => '<i class="fa kd-download"></i>'.__('Download Excel'),
                     'attr' => $indexAttr,
                     'url' => $downloadUrl
                 ];
            }
            
            if ($this->AccessControl->check(['Institutions', 'StudentProfiles', 'download']) 
                && $entity->has('report_card_status') && in_array($entity->report_card_status, [self::GENERATED, self::PUBLISHED])) 
            {
                 //POCOR-9585
                $viewPdfUrl = $this->setQueryString($this->url('viewPDF'), $params);
                $buttons['viewPdf'] = [
                    'label' => '<i class="fa fa-eye"></i>'.__('View PDF'),
                    'attr' => $viewAttr, // POCOR-9292
                    'url' => $viewPdfUrl
                ];

                //END:POCOR-6667
                $downloadPdfUrl = $this->setQueryString($this->url('downloadPDF'), $params);
                $buttons['downloadPdf'] = [
                    'label' => '<i class="fa kd-download"></i>'.__('Download PDF'),
                    'attr' => $indexAttr,
                    'url' => $downloadPdfUrl
                ];
            }

            // Generate button, all statuses
            if ($this->AccessControl->check(['Institutions', 'StudentProfiles', 'generate'])) {
                $generateUrl = $this->setQueryString($this->url('generate'), $params);

                $reportCard = $this->StudentTemplates
                                    ->find()
                                    ->where([
                                        $this->StudentTemplates->aliasField('id') => $reportCardId])
                                    ->first();


                if (!empty($reportCard->generate_start_date)) {
                $generateStartDate = $reportCard->generate_start_date->format('Y-m-d');
                }

                if (!empty($reportCard->generate_end_date)) {
                $generateEndDate = $reportCard->generate_end_date->format('Y-m-d');
                }
                $date = FrozenTime::now()->format('Y-m-d');

                if ((!empty($generateStartDate) && !empty($generateEndDate)) && ($date >= $generateStartDate && $date <= $generateEndDate)) {
                            $buttons['generate'] = [
                            'label' => '<i class="fa fa-refresh"></i>'. __('Generate'),
                            'attr' => $generateAttr,
                            'url' => $generateUrl,
                            0 => $encodedQueryString
                            ];
                } else {
                    $generateAttr['title'] = $this->getMessage('StudentProfiles.date_closed');
                    $buttons['generate'] = [
                            'label' => '<i class="fa fa-refresh"></i>'. __('Generate'),
                            'attr' => $generateAttr,
                            'url' => 'javascript:void(0)',
                            0 => $encodedQueryString
                            ];
                }
            }
        }
        //POCOR-5191::Start
        $student_profile_security_roles_table = TableRegistry::getTableLocator()->get('Student.StudentProfileSecurityRoles');
        $instituttionnTable = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $securitygroupusersTable = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
        $institutionId = $this->getInstitutionID();
        $insData = $instituttionnTable->get($institutionId);
       // $security_group_id = $insData->security_group_id;
        $user_id = $this->Session->read('Auth.User.id');
        $ProfileTemplatesId = $this->request->getQuery('student_profile_template_id');
        if($ProfileTemplatesId != null){
            $roles = $student_profile_security_roles_table->find()->where(['student_profile_template_id'=> $this->request->getQuery('student_profile_template_id')])->toArray();
        }
        //print_r($this->request->getQuery('student_profile_template_id'));die;

        $curr_u_roles = $securitygroupusersTable->find()->where(['security_user_id'=>$user_id])->toArray();
        $rolArr = [];
        $rolArrrr = [];
        foreach($roles as $rol){
            $rolArr[] = $rol->security_role_id;
        }

        foreach($curr_u_roles as $curr_uu_roles){
            $rolArrrr[] = $curr_uu_roles->security_role_id;
        }
        $result = array_intersect($rolArrrr, $rolArr);
        $nResult = reset($result);

        if($this->Session->read('Auth.User.super_admin') != 1){
            if(!empty($nResult)){
                if(!in_array($nResult, $rolArr)){
                    $buttons = [];
                }
            }else{
                $buttons = [];
            }
        }
        //POCOR-5191::End
        return $buttons;
    }
    
    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('openemis_no', ['sort' => ['field' => 'Users.openemis_no']]);
        $this->field('student_id', ['type' => 'integer', 'sort' => ['field' => 'Users.first_name']]);
        $this->field('profile_name');
        $this->field('status', ['sort' => ['field' => 'report_card_status']]);
        $this->field('started_on');
        $this->field('completed_on');
        $this->field('email_status');
        $this->field('age', ['type' => 'string', 'label' => false]); //POCOR-9593: age indicator — no column header
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('report_queue');
        $this->setFieldOrder(['age', 'openemis_no', 'student_id', 'profile_name', 'status', 'started_on', 'completed_on', 'report_queue', 'email_status']); //POCOR-9593: age first, no label
        $this->setFieldVisible(['index'], ['age', 'openemis_no', 'student_id', 'profile_name', 'status', 'started_on', 'completed_on', 'report_queue', 'email_status']); //POCOR-9593: age first, no label

        // SQL Query to get the current processing list for report_queue table
        $this->reportProcessList = $this->StudentReportCardProcesses
            ->find()
            ->select([
                $this->StudentReportCardProcesses->aliasField('student_profile_template_id'),
                $this->StudentReportCardProcesses->aliasField('student_id'),
                $this->StudentReportCardProcesses->aliasField('institution_id'),
                $this->StudentReportCardProcesses->aliasField('academic_period_id')
            ])
            ->where([
                $this->StudentReportCardProcesses->aliasField('status') => $this->StudentReportCardProcesses::NEW_PROCESS
            ])
            ->order([
                $this->StudentReportCardProcesses->aliasField('created'),
                $this->StudentReportCardProcesses->aliasField('student_id')
            ])
            ->enableHydration(false)
            ->toArray();
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $AcademicPeriod = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $StudentStatuses = TableRegistry::getTableLocator()->get('Student.StudentStatuses');
        $institutionId = $this->getInstitutionID();
        // Academic Periods filter
        $academicPeriodOptions = $AcademicPeriod->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->getQuery('academic_period_id')) ? $this->request->getQuery('academic_period_id') : $AcademicPeriod->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        //End

        // Report Cards filter
        $reportCardOptions = [];
        $reportCardOptions = $this->StudentTemplates->find('list')
            ->where([
                $this->StudentTemplates->aliasField('academic_period_id') => $selectedAcademicPeriod
            ])
            ->toArray();


        $reportCardOptions = ['-1' => '-- '.__('Select Profile').' --'] + $reportCardOptions;//POCOR-6655 renamed filter name
        $selectedReportCard = !is_null($this->request->getQuery('student_profile_template_id')) ? $this->request->getQuery('student_profile_template_id') : -1;
        $this->controller->set(compact('reportCardOptions', 'selectedReportCard'));
        //End
        $where[$this->aliasField('institution_id')] = $institutionId;
        $where[$this->aliasField('student_status_id')] = 1;
        $query
            ->select([
                'student_profile_template_id' => $this->InstitutionStudentsProfileTemplates->aliasField('student_profile_template_id'),
                'report_card_status' => $this->InstitutionStudentsProfileTemplates->aliasField('status'),
                'report_card_started_on' => $this->InstitutionStudentsProfileTemplates->aliasField('started_on'),
                'report_card_completed_on' => $this->InstitutionStudentsProfileTemplates->aliasField('completed_on'),
                'email_status_id' => $this->StudentReportCardEmailProcesses->aliasField('status'),
                'email_error_message' => $this->StudentReportCardEmailProcesses->aliasField('error_message'),
                'student_id' => $this->aliasField('student_id'),
                'student_id' => 'Users.id'
            ])->contain('Users')
            ->matching('StudentStatuses', function ($q) {
                return $q->where(['StudentStatuses.code' => 'CURRENT']);
            })
            ->leftJoin([$this->InstitutionStudentsProfileTemplates->getAlias() => $this->InstitutionStudentsProfileTemplates->getTable()],
                [
                    $this->InstitutionStudentsProfileTemplates->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $this->InstitutionStudentsProfileTemplates->aliasField('institution_id = ') . $institutionId,
                    $this->InstitutionStudentsProfileTemplates->aliasField('academic_period_id = ') . $selectedAcademicPeriod,
                    $this->InstitutionStudentsProfileTemplates->aliasField('student_profile_template_id = ') . $selectedReportCard
                ]
            )
            ->leftJoin([$this->StudentReportCardEmailProcesses->getAlias() => $this->StudentReportCardEmailProcesses->getTable()],
                [
                    $this->StudentReportCardEmailProcesses->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $this->StudentReportCardEmailProcesses->aliasField('institution_id = ') . $institutionId,
                    $this->StudentReportCardEmailProcesses->aliasField('academic_period_id = ') . $selectedAcademicPeriod,
                    $this->StudentReportCardEmailProcesses->aliasField('student_profile_template_id = ') . $selectedReportCard
                ]
            )
            ->EnableAutoFields(true)
            ->group([
                $this->aliasField('student_id'),
                $this->aliasField('academic_period_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('education_grade_id'),
                $this->aliasField('student_status_id')
            ])
            ->where($where)
            // ->where([$this->aliasField('student_status_id') => 1])
            ->all();
            //Log::write('debug',$query);
        if (is_null($this->request->getQuery('sort'))) {
            $query
                ->contain('Users')
                ->order(['Users.first_name', 'Users.last_name']);
        }
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $extra['elements']['controls'] = ['name' => 'Institution.ProfileTemplates/Studentcontrols', 'options' => [], 'order' => 1,'data' => [

                'encodedQueryString' => $encodedQueryString,
            ],
        ];

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
            $extra['OR'] = $nameConditions; // to be merged with auto_search 'OR' conditions
        }
    }

    public function indexAfterAction(EventInterface $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $reportCardId = $this->request->getQuery('student_profile_template_id');
        $institutionId = $this->getInstitutionID();
        $academicPeriodId = $this->request->getQuery('academic_period_id');
        $educationGradeId = $this->request->getQuery('education_grade_id');

        if (!is_null($reportCardId) && !is_null($institutionId)) {
            $existingReportCard = $this->StudentTemplates->exists([$this->StudentTemplates->getPrimaryKey() => $reportCardId]);

            // only show toolbar buttons if request for report card and class is valid
            if ($existingReportCard) {
                $generatedCount = 0;
                $publishedCount = 0;

                // count statuses to determine which buttons are shown
                foreach($data as $student) {
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
                    'institution_id' => $institutionId,
                    'academic_period_id' => $academicPeriodId,
                    'education_grade_id' => $educationGradeId,
                    'student_profile_template_id' => $reportCardId
                ];
                // POCOR-9165 start
                $encodedParams = $this->paramsEncode($params);
                $downloadAllPdfUrl = $this->url('downloadAllPdf');
                $downloadAllPdfUrl['1'] = $encodedParams;
                $downloadAllUrl = $this->url('downloadAll');
                $downloadAllUrl['1'] = $encodedParams;
                $generateAllUrl = $this->url('generateAll'); // POCOR-9296
                $generateAllUrl['1'] = $encodedParams; // POCOR-9296
                unset($downloadAllPdfUrl['?']);
                unset($downloadAllUrl['?']);
                unset($generateAllUrl['?']); // POCOR-9296
                // POCOR-9165 end
                if ($generatedCount > 0 || $publishedCount > 0) {
                    $downloadButtonPdf['url'] = $downloadAllPdfUrl;
                    $downloadButtonPdf['type'] = 'button';
                    $downloadButtonPdf['label'] = '<i class="fa kd-download"></i>';
                    $downloadButtonPdf['attr'] = $toolbarAttr;
                    $downloadButtonPdf['attr']['title'] = __('Download All PDF');
                    $extra['toolbarButtons']['downloadAllPdf'] = $downloadButtonPdf;
                }

                if ($generatedCount > 0 || $publishedCount > 0) {
                    $downloadButton['url'] = $downloadAllUrl;
                    $downloadButton['type'] = 'button';
                    $downloadButton['label'] = '<i class="fa kd-download"></i>';
                    $downloadButton['attr'] = $toolbarAttr;
                    $downloadButton['attr']['title'] = __('Download All Excel');
                    $extra['toolbarButtons']['downloadAll'] = $downloadButton;
                }

                // Generate all button
                $generateButton['url'] = $generateAllUrl; // POCOR-9296
                $generateButton['type'] = 'button';
                $generateButton['label'] = '<i class="fa fa-refresh"></i>';
                $generateButton['attr'] = $toolbarAttr;
                $generateButton['attr']['title'] = __('Generate All');
                //$ReportCards = TableRegistry::getTableLocator()->get('ReportCard.ReportCards');
                if (!is_null($this->request->getQuery('student_profile_template_id'))) {
                    $reportCardId = $this->request->getQuery('student_profile_template_id');
                }

                $ReportCardsData = $this->StudentTemplates
                                    ->find()
                                    ->where([
                                        $this->StudentTemplates->aliasField('id') => $reportCardId])
                                    ->first();


                if (!empty($ReportCardsData->generate_start_date)) {
                $generateStartDate = $ReportCardsData->generate_start_date->format('Y-m-d');
                }

                if (!empty($ReportCardsData->generate_end_date)) {
                $generateEndDate = $ReportCardsData->generate_end_date->format('Y-m-d');
                }
                $date = FrozenTime::now()->format('Y-m-d');

                if (!empty($generateStartDate) && !empty($generateEndDate) && $date >= $generateStartDate && $date <= $generateEndDate) {

                    $extra['toolbarButtons']['generateAll'] = $generateButton;

                } else {
                    //POCOR-9598: start - hide Generate All button and show warning when date window is closed
                    $this->Alert->warning(__('This profile template generation is not enabled. Consult with system administrator to check the dates.'), ['type' => 'string', 'reset' => true]);
                    //POCOR-9598: end
                }

                //POCOR-9593: start - stale profile banner (runs after POCOR-9598 so it overwrites with richer message)
                $staleTemplate = $this->StudentTemplates->find()
                    ->where([$this->StudentTemplates->aliasField('id') => $this->request->getQuery('student_profile_template_id')])
                    ->first();
                $this->showStaleProfileBanner($data, $staleTemplate, 'report_card_completed_on', 'report_card_status');
                //POCOR-9593: end

                // Publish all button
                if ($generatedCount > 0) {
                    $publishButton['url'] = $this->setQueryString($this->url('publishAll'), $params);
                    $publishButton['type'] = 'button';
                    $publishButton['label'] = '<i class="fa kd-publish"></i>';
                    $publishButton['attr'] = $toolbarAttr;
                    $publishButton['attr']['title'] = __('Publish All');
                    $extra['toolbarButtons']['publishAll'] = $publishButton;
                }

                // Unpublish all button
                if ($publishedCount > 0) {
                    $unpublishButton['url'] = $this->setQueryString($this->url('unpublishAll'), $params);
                    $unpublishButton['type'] = 'button';
                    $unpublishButton['label'] = '<i class="fa kd-unpublish"></i>';
                    $unpublishButton['attr'] = $toolbarAttr;
                    $unpublishButton['attr']['title'] = __('Unpublish All');
                    $extra['toolbarButtons']['unpublishAll'] = $unpublishButton;
                }

                // Email all button is published
                if ($publishedCount > 0) {
                    $emailButton['url'] = $this->setQueryString($this->url('emailAll'), $params);
                    $emailButton['type'] = 'button';
                    $emailButton['label'] = '<i class="fa fa-envelope"></i>';
                    $emailButton['attr'] = $toolbarAttr;
                    $emailButton['attr']['title'] = __('Email All');
                    $extra['toolbarButtons']['emailAll'] = $emailButton;
                }
            }
        }
    }

    public function getSearchableFields(EventInterface $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'student_id';
        $searchableFields[] = 'openemis_no';
    }

    public function viewBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->setFieldOrder(['academic_period_id', 'openemis_no', 'student_id', 'profile_name', 'status', 'started_on', 'completed_on', 'report_queue', 'email_status']);
        $this->setFieldVisible(['view'], ['academic_period_id', 'openemis_no', 'student_id', 'profile_name', 'status', 'started_on', 'completed_on', 'report_queue', 'email_status']);
    }

    public function viewBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $params = $this->request->getQuery;
        $institutionId = $this->getInstitutionID();

        $query
            ->select([
                'student_profile_template_id' => $this->InstitutionStudentsProfileTemplates->aliasField('student_profile_template_id'),
                'report_card_status' => $this->InstitutionStudentsProfileTemplates->aliasField('status'),
                'report_card_started_on' => $this->InstitutionStudentsProfileTemplates->aliasField('started_on'),
                'report_card_completed_on' => $this->InstitutionStudentsProfileTemplates->aliasField('completed_on'),
                'email_status_id' => $this->StudentReportCardEmailProcesses->aliasField('status'),
                'email_error_message' => $this->StudentReportCardEmailProcesses->aliasField('error_message')
            ])
            ->leftJoin([$this->InstitutionStudentsProfileTemplates->getAlias() => $this->InstitutionStudentsProfileTemplates->getTable()],
                [
                    $this->InstitutionStudentsProfileTemplates->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $this->InstitutionStudentsProfileTemplates->aliasField('institution_id = ') . $this->aliasField('institution_id')
                ]
            )
            ->leftJoin([$this->StudentReportCardEmailProcesses->getAlias() => $this->StudentReportCardEmailProcesses->getTable()],
                [
                    $this->StudentReportCardEmailProcesses->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $this->StudentReportCardEmailProcesses->aliasField('institution_id = ') . $this->InstitutionStudentsProfileTemplates->aliasField('institution_id'),
                    $this->StudentReportCardEmailProcesses->aliasField('academic_period_id = ') . $this->InstitutionStudentsProfileTemplates->aliasField('academic_period_id'),
                    $this->StudentReportCardEmailProcesses->aliasField('student_profile_template_id = ') . $this->InstitutionStudentsProfileTemplates->aliasField('student_profile_template_id')
                ]
            )
            //->autoFields(true)
            ;
    }

    public function onGetStatus(EventInterface $event, Entity $entity)
    {
        if ($entity->has('report_card_status')) {
            $value = $this->statusOptions[$entity->report_card_status];
        } else {
            $value = $this->statusOptions[self::NEW_REPORT];
        }
        return $value;
    }

    public function onGetStartedOn(EventInterface $event, Entity $entity)
    {
        $value = '';

        if ($entity->has('report_card_started_on') && !empty($entity->report_card_started_on)) {
            $startedOnValue = new FrozenTime($entity->report_card_started_on);
            $value = $startedOnValue->format('Y-m-d H:i:s'); //POCOR-9593: direct format — formatDateTime() not available on this branch
        }

        return $value;
    }

    public function onGetCompletedOn(EventInterface $event, Entity $entity)
    {
        $value = '';

        if ($entity->has('report_card_completed_on') && !empty($entity->report_card_completed_on)) {
            $completedOnValue = new FrozenTime($entity->report_card_completed_on);
            $value = $completedOnValue->format('Y-m-d H:i:s'); //POCOR-9593: direct format — formatDateTime() not available on this branch
        }

        return $value;
    }

    //POCOR-9593: start - profile age indicator square
    public function onGetAge(EventInterface $event, Entity $entity)
    {
        $completedOn = $entity->has('report_card_completed_on') ? $entity->report_card_completed_on : null;
        $status = $entity->has('report_card_status') ? $entity->report_card_status : self::NEW_REPORT;

        if (empty($completedOn) || !in_array($status, [self::GENERATED, self::PUBLISHED])) {
            return '<span style="display:inline-block;width:14px;height:14px;border:2px solid #aaa;background:transparent;vertical-align:middle;" title="' . __('Not yet generated') . '"></span>';
        }

        $now = FrozenTime::now(); //POCOR-9593: use diffInDays for correct future-date handling
        $completed = new FrozenTime($completedOn);
        $days = $now->greaterThan($completed) ? (int) $now->diffInDays($completed) : 0; //POCOR-9593: 0 if completed_on is in the future

        if ($days < 30) {
            $color = '#2196F3';
        } elseif ($days < 365) {
            $color = '#FFC107';
        } else {
            $color = '#F44336';
        }

        $title = sprintf(__('Generated %d days ago'), $days);
        return '<span style="display:inline-block;width:14px;height:14px;background:' . $color . ';vertical-align:middle;" title="' . $title . '"></span>';
    }
    //POCOR-9593: end

    public function onGetReportQueue(EventInterface $event, Entity $entity)
    {
        if ($entity->has('student_profile_template_id')) {
            $reportCardId = $entity->student_profile_template_id;
        } else if (!is_null($this->request->getQuery('student_profile_template_id'))) {
            $reportCardId = $this->request->getQuery('student_profile_template_id');
        }
        $academicPeriodId = $this->request->getQuery('academic_period_id');

        $search = [
            'student_profile_template_id' => $reportCardId,
            'student_id' => $entity->student_id,
            'institution_id' => $entity->institution_id,
            'academic_period_id' => $academicPeriodId
        ];
        $resultIndex = array_search($search, $this->reportProcessList);

        if ($resultIndex !== false) {
            $totalQueueCount = count($this->reportProcessList);
            return sprintf(__('%s of %s'), $resultIndex + 1, $totalQueueCount);
        } else {
            return '<i class="fa fa-minus"></i>';
        }
    }

    public function onGetOpenemisNo(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('user')) {
            $value = $entity->user->openemis_no;
        }
        return $value;
    }

    public function onGetProfileName(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('student_profile_template_id')) {
            $reportCardId = $entity->student_profile_template_id;
        } else if (!is_null($this->request->getQuery('student_profile_template_id'))) {
            // used if student report card record has not been created yet
            $reportCardId = $this->request->getQuery('student_profile_template_id');
        }

        if (!empty($reportCardId)) {
            $reportCardEntity = $this->StudentTemplates->find()->where(['id' => $reportCardId])->first();
            if (!empty($reportCardEntity)) {
                $value = $reportCardEntity->name;
            }
        }
        return $value;
    }

    public function onGetEmailStatus(EventInterface $event, Entity $entity)
    {
        $emailStatuses = $this->StudentReportCardEmailProcesses->getEmailStatus();
        $value = '<i class="fa fa-minus"></i>';

        if ($entity->has('email_status_id')) {
            $value = $emailStatuses[$entity->email_status_id];

            if ($entity->email_status_id == $this->StudentReportCardEmailProcesses::ERROR && $entity->has('email_error_message')) {
                $value .= '&nbsp&nbsp;<i class="fa fa-exclamation-circle fa-lg table-tooltip icon-red" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="' . $entity->email_error_message . '"></i>';
            }
        }

        return $value;
    }

    public function downloadExcel(EventInterface $event, ArrayObject $extra)
    {
        $model = $this->InstitutionStudentsProfileTemplates;
        $ids = $this->getQueryString();
        $institutionId = $this->getInstitutionID();
        $ids['institution_id'] = $institutionId;
        if ($model->exists($ids)) {
            $data = $model->find()->where($ids)->first();
            $fileName = $data->file_name;
            $pathInfo = pathinfo($fileName);
            $file = $this->getFile($data->file_content);
            $fileType = 'image/jpg';
            if (array_key_exists($pathInfo['extension'], $this->fileTypes)) {
                $fileType = $this->fileTypes[$pathInfo['extension']];
            }

            // echo '<img src="data:image/jpg;base64,' .   base64_encode($file)  . '" />';

            header("Pragma: public", true);
            header("Expires: 0"); // set expiration time
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-Type: application/force-download");
            header("Content-Type: application/octet-stream");
            header("Content-Type: " . $fileType);
            header('Content-Disposition: attachment; filename="' . $fileName . '"');

            echo $file;
        }
        exit();
    }

    public function downloadPDF(EventInterface $event, ArrayObject $extra)
    {
        $model = $this->InstitutionStudentsProfileTemplates;
        $ids = $this->getQueryString();
        $institutionId = $this->getInstitutionID();
        $ids['institution_id'] = $institutionId;
        if ($model->exists($ids)) {
            $data = $model->find()->where($ids)->first();
            $fileName = $data->file_name;
            $fileNameData = explode(".",$fileName);
            $fileName = $fileNameData[0].'.pdf';
            $pathInfo['extension'] = 'pdf';
            $file = $this->getFile($data->file_content_pdf);
            $fileType = 'image/jpg';
            if (array_key_exists($pathInfo['extension'], $this->fileTypes)) {
                $fileType = $this->fileTypes[$pathInfo['extension']];
            }

            // echo '<img src="data:image/jpg;base64,' .   base64_encode($file)  . '" />';

            header("Pragma: public", true);
            header("Expires: 0"); // set expiration time
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-Type: application/force-download");
            header("Content-Type: application/octet-stream");
            header("Content-Type: " . $fileType);
            header('Content-Disposition: attachment; filename="' . $fileName . '"');

            echo $file;
        }
        exit();
    }

    /*
    * Function is created to view PDF in browser
    * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
    * return file
    * @ticket POCOR-6667
    */

    public function viewPDF(EventInterface $event, ArrayObject $extra)
    {
        $model = $this->InstitutionStudentsProfileTemplates;
        $ids = $this->getQueryString();
        $institutionId = $this->getInstitutionID();
        $ids['institution_id'] = $institutionId;
        if ($model->exists($ids)) {
            $data = $model->find()->where($ids)->first();
            $fileName = $data->file_name;
            $fileNameData = explode(".",$fileName);
            $fileName = $fileNameData[0].'.pdf';
            $pathInfo['extension'] = 'pdf';
            $file = $this->getFile($data->file_content_pdf);
            $fileType = 'image/jpg';
            if (array_key_exists($pathInfo['extension'], $this->fileTypes)) {
                $fileType = $this->fileTypes[$pathInfo['extension']];
            }

            // echo '<img src="data:image/jpg;base64,' .   base64_encode($file)  . '" />';

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

    public function generate(EventInterface $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
//        dd($params);
        $hasTemplate = $this->StudentTemplates->checkIfHasTemplate($params['student_profile_template_id']);
        $institutionId = $this->getInstitutionID();
        if ($hasTemplate) {
            $this->addReportCardsToProcesses($params['institution_id'], // POCOR-9296
                $params['academic_period_id'],
                $params['student_profile_template_id'],
                $params['student_id']);
            $this->triggerGenerateReportCardsCommand($institutionId, $params['education_grade_id'], $params['academic_period_id'], $params['student_profile_template_id'], $params['student_id']); //POCOR-9598: now uses generate_student_profile Command
            $this->Alert->warning('StudentProfiles.generate');
        }

        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function generateAll(EventInterface $event, ArrayObject $extra)
    {

        $params = $this->getQueryString();
        $hasTemplate = $this->StudentTemplates->checkIfHasTemplate($params['student_profile_template_id']);
//        dd($params);

        $institutionId = $this->getInstitutionID();
        if ($hasTemplate) {
            $StudentReportCardProcesses = TableRegistry::getTableLocator()->get('ReportCard.StudentReportCardProcesses');
            $inProgress = $StudentReportCardProcesses->find()
                ->where([
                    $StudentReportCardProcesses->aliasField('student_profile_template_id') => $params['student_profile_template_id'],
                    $StudentReportCardProcesses->aliasField('student_id IS') => $params['student_id'],
                    $StudentReportCardProcesses->aliasField('academic_period_id') => $params['academic_period_id'],
                    $StudentReportCardProcesses->aliasField('institution_id') => $institutionId,
                ])
                ->count();


            if (!$inProgress) {
                $this->addReportCardsToProcesses($params['institution_id'], // POCOR-9296
                    $params['academic_period_id'],
                    $params['student_profile_template_id'],
                    $params['student_id']);
                $this->triggerGenerateReportCardsCommand($params['institution_id'], $params['education_grade_id'], $params['academic_period_id'], $params['student_profile_template_id'], $params['student_id']); //POCOR-9598: now uses generate_student_profile Command
                $this->Alert->warning('StudentProfiles.generateAll');
            } else {
                $this->Alert->warning('StudentProfiles.inProgress');
            }
        } else {
            $this->Alert->warning('StudentProfiles.noTemplate');
        }

        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function downloadAllPdf(EventInterface $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();

        $institutionId = $this->getInstitutionID();
        // only download report cards with generated or published status
        $statusArray = [self::GENERATED, self::PUBLISHED];

        $files = $this->InstitutionStudentsProfileTemplates->find()
            ->contain(['StudentTemplates'])
            ->where([
                $this->InstitutionStudentsProfileTemplates->aliasField('institution_id') => $institutionId,
                $this->InstitutionStudentsProfileTemplates->aliasField('academic_period_id') => $params['academic_period_id'],
                $this->InstitutionStudentsProfileTemplates->aliasField('student_profile_template_id') => $params['student_profile_template_id'],
                $this->InstitutionStudentsProfileTemplates->aliasField('status IN ') => $statusArray,
                $this->InstitutionStudentsProfileTemplates->aliasField('file_name IS NOT NULL'),
                $this->InstitutionStudentsProfileTemplates->aliasField('file_content IS NOT NULL')
            ])
            ->toArray();

        if (!empty($files)) {
            $path = WWW_ROOT . 'export' . DS . 'customexcel' . DS;
            $zipName = 'StudentReportCards' . '_' . date('Ymd') . 'T' . date('His') . '.zip';
            $filepath = $path . $zipName;

            $zip = new ZipArchive;
            $zip->open($filepath, ZipArchive::CREATE);

            foreach ($files as $file) {
                $fileName = $file->file_name;
                $fileNameData = explode(".",$fileName);
                $fileName = $fileNameData[0].'.pdf';

                $zip->addFromString($fileName,  $this->getFile($file->file_content_pdf));

            }
            $zip->close();

            header("Pragma: public", true);
            header("Expires: 0"); // set expiration time
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-Type: application/force-download");
            header("Content-Type: application/zip");
            header("Content-Length: ".filesize($filepath));
            header("Content-Disposition: attachment; filename=".$zipName);
            readfile($filepath);

            // delete file after download
            unlink($filepath);
            exit(); // POCOR-9165
        } else {
            $event->stopPropagation();
            $this->Alert->warning('StudentProfiles.noFilesToDownload');
            return $this->controller->redirect($this->url('index'));
        }
    }

    public function downloadAll(EventInterface $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        $institutionId = $this->getInstitutionID();
        // only download report cards with generated or published status
        $statusArray = [self::GENERATED, self::PUBLISHED];

        $files = $this->InstitutionStudentsProfileTemplates->find()
            ->contain(['StudentTemplates'])
            ->where([
                $this->InstitutionStudentsProfileTemplates->aliasField('institution_id') => $institutionId,
                $this->InstitutionStudentsProfileTemplates->aliasField('academic_period_id') => $params['academic_period_id'],
                $this->InstitutionStudentsProfileTemplates->aliasField('student_profile_template_id') => $params['student_profile_template_id'],
                $this->InstitutionStudentsProfileTemplates->aliasField('status IN ') => $statusArray,
                $this->InstitutionStudentsProfileTemplates->aliasField('file_name IS NOT NULL'),
                $this->InstitutionStudentsProfileTemplates->aliasField('file_content IS NOT NULL')
            ])
            ->toArray();
        if (!empty($files)) {
            $path = WWW_ROOT . 'export' . DS . 'customexcel' . DS;
            $zipName = 'StudentReportCards' . '_' . date('Ymd') . 'T' . date('His') . '.zip';
            $filepath = $path . $zipName;

            $zip = new ZipArchive;
            $zip->open($filepath, ZipArchive::CREATE);
            foreach ($files as $file) {
              $zip->addFromString($file->file_name,  $this->getFile($file->file_content));
            }
            $zip->close();

            header("Pragma: public", true);
            header("Expires: 0"); // set expiration time
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-Type: application/force-download");
            header("Content-Type: application/zip");
            header("Content-Length: ".filesize($filepath));
            header("Content-Disposition: attachment; filename=".$zipName);
            readfile($filepath);

            // delete file after download
            unlink($filepath);
            exit(); // POCOR-9165
        } else {
            $event->stopPropagation();
            $this->Alert->warning('StudentProfiles.noFilesToDownload');
            return $this->controller->redirect($this->url('index'));
        }
    }

    public function publish(EventInterface $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        $result = $this->InstitutionStudentsProfileTemplates->updateAll(['status' => self::PUBLISHED], $params);
        $this->Alert->success('StudentProfiles.publish');
        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function publishAll(EventInterface $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        //POCOR:7448 ::Start
        $condition = [];
        if(!empty($params['institution_id'])){
            $condition['institution_id'] = $params['institution_id'];
        }
        if(!empty($params['academic_period_id'])){
            $condition['academic_period_id'] = $params['academic_period_id'];
        }
        if(!empty($params['education_grade_id'])){
            $condition['education_grade_id'] = $params['education_grade_id'];
        }
        if(!empty($params['student_profile_template_id'])){
            $condition['student_profile_template_id'] = $params['student_profile_template_id'];
        }
        //POCOR:7448 ::end
        // only publish report cards with generated status to published status
        $result = $this->InstitutionStudentsProfileTemplates->updateAll(['status' => self::PUBLISHED], [
            $condition, //POCOR-7448
            'status' => self::GENERATED
        ]);

        if ($result) {
            $this->Alert->success('StudentProfiles.publishAll');
        } else {
            $this->Alert->warning('StudentProfiles.noFilesToPublish');
        }

        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function unpublish(EventInterface $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        $result = $this->InstitutionStudentsProfileTemplates->updateAll(['status' => self::NEW_REPORT], $params);
        $this->Alert->success('StudentProfiles.unpublish');
        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function unpublishAll(EventInterface $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();

        // only unpublish report cards with published status to new status
        $result = $this->InstitutionStudentsProfileTemplates->updateAll(['status' => self::NEW_REPORT], [
            $params,
            'status' => self::PUBLISHED,
        ]);

        if ($result) {
            $this->Alert->success('StudentProfiles.unpublishAll');
        } else {
            $this->Alert->warning('StudentProfiles.noFilesToUnpublish');
        }

        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function email(EventInterface $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        $institutionId = $this->getInstitutionID();
        $this->addReportCardsToEmailProcesses($institutionId, $params['education_grade_id'], $params['academic_period_id'], $params['student_profile_template_id'], $params['student_id']);
        $this->triggerEmailAllReportCardsShell($institutionId, $params['education_grade_id'], $params['academic_period_id'], $params['student_profile_template_id'], $params['student_id']);
        $this->Alert->warning('StudentProfiles.email');

        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function emailAll(EventInterface $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        $institutionId = $this->getInstitutionID();
        $inProgress = $this->StudentReportCardEmailProcesses->find()
            ->where([
                $this->StudentReportCardEmailProcesses->aliasField('student_profile_template_id') => $params['student_profile_template_id'],
                $this->StudentReportCardEmailProcesses->aliasField('institution_id') => $institutionId,
                $this->StudentReportCardEmailProcesses->aliasField('education_grade_id IS') => $params['education_grade_id'],
                $this->StudentReportCardEmailProcesses->aliasField('academic_period_id') => $params['academic_period_id'],
                $this->StudentReportCardEmailProcesses->aliasField('status') => $this->StudentReportCardEmailProcesses::SENDING
            ])
            ->count();

        if (!$inProgress) {
            $this->addReportCardsToEmailProcesses($institutionId, $params['education_grade_id'], $params['academic_period_id'], $params['student_profile_template_id']);
            $this->triggerEmailAllReportCardsShell($institutionId, $params['education_grade_id'], $params['academic_period_id'], $params['student_profile_template_id']);

            $this->Alert->warning('StudentProfiles.emailAll');
        } else {
            $this->Alert->warning('StudentProfiles.emailInProgress');
        }

        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    private function addReportCardsToProcesses($institutionId, $academicPeriodId, $reportCardId, $studentId = null) // POCOR-9296
    {
        Log::write('debug', 'Initialize Add All Student Profile Report Cards '.$reportCardId . ' to processes ('.FrozenTime::now().')');

        $StudentReportCardProcesses = TableRegistry::getTableLocator()->get('ReportCard.StudentReportCardProcesses');
        $institutionClassStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
        $where = [];
        $where[$this->aliasField('academic_period_id')] = $academicPeriodId;
        $where[$this->aliasField('institution_id')] = $institutionId;
        if (!is_null($studentId)) {
            $where[$this->aliasField('student_id')] = $studentId;
        }
        $institutionStudents = $this->find()
            ->select([
                'student_id' => $this->aliasField('student_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('education_grade_id'),
            ])->contain(['Users'])
            ->group([
                $this->aliasField('student_id'),
                $this->aliasField('academic_period_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('education_grade_id'),
                $this->aliasField('student_status_id')
            ])
            ->where($where)
            ->where([$this->aliasField('student_status_id') => 1])
            ->toArray();
            //echo "<pre>"; print_r($institutionStudents);die;

        foreach ($institutionStudents as $student) {
            // Report card processes
            $idKeys = [
                'student_profile_template_id' => $reportCardId,
                'institution_id' => $student->institution_id,
                'student_id' => $student->student_id,
                'education_grade_id' => $student->education_grade_id
            ];

            $data = [
                'status' => $StudentReportCardProcesses::NEW_PROCESS,
                'academic_period_id' => $academicPeriodId,
                'created' => date('Y-m-d H:i:s')
            ];
            $obj = array_merge($idKeys, $data);
            $newEntity = $StudentReportCardProcesses->newEntity($obj);
            $StudentReportCardProcesses->save($newEntity);
            // end

            // Report card email processes
            $emailIdKeys = $idKeys;
            if ($this->StudentReportCardEmailProcesses->exists($emailIdKeys)) {
                $reportCardEmailProcessEntity = $this->StudentReportCardEmailProcesses->find()
                    ->where($emailIdKeys)
                    ->first();
                $this->StudentReportCardEmailProcesses->delete($reportCardEmailProcessEntity);
            }
            // end

            // student report card
            $recordIdKeys = [
                'student_profile_template_id' => $reportCardId,
                'student_id' => $student->student_id,
                'institution_id' => $student->institution_id,
                'education_grade_id' => $student->education_grade_id,
                'academic_period_id' => $academicPeriodId,
            ];
            if ($this->InstitutionStudentsProfileTemplates->exists($recordIdKeys)) {
                $studentsReportCardEntity = $this->InstitutionStudentsProfileTemplates->find()
                    ->where($recordIdKeys)
                    ->first();

                $newData = [
                    'status' => $this->InstitutionStudentsProfileTemplates::NEW_REPORT,
                    'started_on' => NULL,
                    'completed_on' => NULL,
                    'file_name' => NULL,
                    'file_content' => NULL,
                    'student_id' => $student->student_id
                ];

                $newEntity = $this->InstitutionStudentsProfileTemplates->patchEntity($studentsReportCardEntity, $newData);

                if (!$this->InstitutionStudentsProfileTemplates->save($newEntity)) {
                    Log::write('debug', 'Error Add All Student profile Report Cards '.$reportCardId.' for Grade '.$educationGradeId.' to processes ('.FrozenTime::now().')');
                    Log::write('debug', json_encode($newEntity->getErrors()));
                }
            }
            // end
        }

        Log::write('debug', 'End Add All Student profile Report Cards '.$educationGradeId.' for Grade '.$institutionGradeId.' to processes ('.FrozenTime::now().')');
    }

    private function triggerGenerateReportCardsCommand($institutionId, $educationGradeId, $academicPeriodId, $reportCardId, $studentId = null) //POCOR-9598: replaces GenerateAllStudentReportCards, now calls generate_student_profile Command
    {
        //Log::debug('@StudentProfilesTable::triggerGenerateReportCardsCommand ENTRY institutionId=' . $institutionId . ' educationGradeId=' . $educationGradeId . ' academicPeriodId=' . $academicPeriodId . ' reportCardId=' . $reportCardId . ' studentId=' . $studentId); //[TEMP-LOG]

        $SystemProcesses = TableRegistry::getTableLocator()->get('SystemProcesses');
        $StudentReportCardProcesses = TableRegistry::getTableLocator()->get('ReportCard.StudentReportCardProcesses');
        $today = FrozenTime::now();

        //POCOR-9598: start — reset student_report_card_processes records stuck RUNNING > 6 hours
        $cutoff6h = clone($today);
        $cutoff6h->subHours(24); //POCOR-9598: 24h window for large countries
        $stuckQueueCount = $StudentReportCardProcesses->find()
            ->where([
                $StudentReportCardProcesses->aliasField('status') => $StudentReportCardProcesses::RUNNING,
                $StudentReportCardProcesses->aliasField('created') . ' <' => $cutoff6h->format('Y-m-d H:i:s'),
            ])
            ->count();
        //Log::debug('@StudentProfilesTable::triggerGenerateReportCardsCommand stuckQueueCount (RUNNING > 24h)=' . $stuckQueueCount . ' cutoff=' . $cutoff6h->format('Y-m-d H:i:s')); //[TEMP-LOG]
        if ($stuckQueueCount > 0) {
            $StudentReportCardProcesses->updateAll(
                ['status' => $StudentReportCardProcesses::NEW_PROCESS],
                [
                    $StudentReportCardProcesses->aliasField('status') => $StudentReportCardProcesses::RUNNING,
                    $StudentReportCardProcesses->aliasField('created') . ' <' => $cutoff6h->format('Y-m-d H:i:s'),
                ]
            );
            //Log::debug('@StudentProfilesTable::triggerGenerateReportCardsCommand reset ' . $stuckQueueCount . ' stuck queue records back to NEW_PROCESS'); //[TEMP-LOG]
        }
        //POCOR-9598: end

        $runningProcess = $SystemProcesses->getRunningProcesses($this->getRegistryAlias());
        //Log::debug('@StudentProfilesTable::triggerGenerateReportCardsCommand runningProcessCount=' . count($runningProcess) . ' MAX_PROCESSES=' . self::MAX_PROCESSES . ' registryAlias=' . $this->getRegistryAlias()); //[TEMP-LOG]

        foreach ($runningProcess as $key => $processData) {
            $systemProcessId = $processData['id'];
            $pId = !empty($processData['process_id']) ? $processData['process_id'] : 0;
            $createdDate = $processData['created'];

            $expiryDate = clone($createdDate);
            $expiryDate->addMinutes(30);

            //Log::debug('@StudentProfilesTable::triggerGenerateReportCardsCommand checking stale process systemProcessId=' . $systemProcessId . ' pId=' . $pId . ' expired=' . ($expiryDate < $today ? 'YES' : 'NO')); //[TEMP-LOG]

            if ($expiryDate < $today) {
                $SystemProcesses->updateProcess($systemProcessId, FrozenTime::now(), $SystemProcesses::COMPLETED);
                $SystemProcesses->killProcess($pId);
            }
        }
        // Re-query after cleanup for an accurate live count
        $runningProcess = $SystemProcesses->getRunningProcesses($this->getRegistryAlias()); //POCOR-9598
        //Log::debug('@StudentProfilesTable::triggerGenerateReportCardsCommand freshRunningCount=' . count($runningProcess) . ' willSpawn=' . (count($runningProcess) <= self::MAX_PROCESSES ? 'YES' : 'NO')); //[TEMP-LOG]

        if (count($runningProcess) <= self::MAX_PROCESSES) {
            $processModel = $this->getRegistryAlias();
            $passArray = [
                'institution_id' => $institutionId,
                'education_grade_id' => $educationGradeId,
                'student_profile_template_id' => $reportCardId
            ];
            if (!is_null($studentId)) {
                $passArray['student_id'] = $studentId;
            }
            $params = json_encode($passArray);

            $args = escapeshellarg($processModel) . ' ' . escapeshellarg($params); //POCOR-9598: escapeshellarg prevents bash brace expansion splitting JSON on commas

            $cmd = ROOT . DS . 'bin' . DS . 'cake generate_student_profile ' . $args; //POCOR-9598: migrated from Shell to Command
            $logs = ROOT . DS . 'logs' . DS . 'GenerateStudentProfile.log 2>&1 & echo $!'; //POCOR-9598: 2>&1 captures stderr
            $shellCmd = $cmd . ' >> ' . $logs;
            //Log::debug('@StudentProfilesTable::triggerGenerateReportCardsCommand SPAWNING cmd=' . $shellCmd); //[TEMP-LOG]
            try {
                $pid = exec($shellCmd);
                //Log::debug('@StudentProfilesTable::triggerGenerateReportCardsCommand SPAWNED pid=' . $pid); //[TEMP-LOG]
                Log::write('debug', $shellCmd);
            } catch (\Exception $ex) {
                Log::write('error', __METHOD__ . ' exception when generate student profile: ' . $ex);
            }
        } else {
            //Log::debug('@StudentProfilesTable::triggerGenerateReportCardsCommand NOT spawning, reached MAX_PROCESSES=' . self::MAX_PROCESSES); //[TEMP-LOG]
        }
        //Log::debug('@StudentProfilesTable::triggerGenerateReportCardsCommand EXIT'); //[TEMP-LOG]
    }

    private function addReportCardsToEmailProcesses($institutionId, $educationGradeId, $academicPeriodId, $reportCardId, $studentId = null)
    {
        Log::write('debug', 'Initialize Add All Student Profile Report Cards '.$reportCardId.' for Grade '.$educationGradeId.' to email processes ('.FrozenTime::now().')');

        $institutionClassStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
        $where = [];
        $where[$institutionClassStudents->aliasField('education_grade_id IS')] = $educationGradeId;
        $where[$institutionClassStudents->aliasField('academic_period_id')] = $academicPeriodId;
        if (!is_null($studentId)) {
            $where[$institutionClassStudents->aliasField('student_id')] = $studentId;
        }
        $institutionStudents = $institutionClassStudents->find()
        ->select([
            $institutionClassStudents->aliasField('student_id'),
            $institutionClassStudents->aliasField('institution_id'),
            $institutionClassStudents->aliasField('education_grade_id'),
        ])
        ->innerJoin(
            ['Profiles' => $this->InstitutionStudentsProfileTemplates->getTable()],
            [
                'Profiles.student_id = ' . $institutionClassStudents->aliasField('student_id'),
                'Profiles.institution_id = ' . $institutionClassStudents->aliasField('institution_id'),
                'Profiles.academic_period_id' => $academicPeriodId,
                'Profiles.education_grade_id IS' => $educationGradeId,
                'Profiles.student_profile_template_id' => $reportCardId,
                'Profiles.status' => self::PUBLISHED
            ]
        )
        ->group([
            $institutionClassStudents->aliasField('student_id'),
            $institutionClassStudents->aliasField('academic_period_id'),
            $institutionClassStudents->aliasField('institution_id'),
            $institutionClassStudents->aliasField('education_grade_id'),
            $institutionClassStudents->aliasField('student_status_id')
        ])
        ->where($where)
        ->where([$institutionClassStudents->aliasField('student_status_id') => 1])
        ->toArray();

        foreach ($institutionStudents as $student) {
            // Report card processes
            $idKeys = [
                'student_profile_template_id' => $reportCardId,
                'institution_id' => $student->institution_id,
                'student_id' => $student->student_id,
                'education_grade_id' => $student->education_grade_id
            ];

            $data = [
                'status' => $this->StudentReportCardEmailProcesses::SENDING,
                'academic_period_id' => $academicPeriodId,
                'created' => date('Y-m-d H:i:s')
            ];

            $obj = array_merge($idKeys, $data);
            $newEntity = $this->StudentReportCardEmailProcesses->newEntity($obj);
            $this->StudentReportCardEmailProcesses->save($newEntity);
            // end
        }

        Log::write('debug', 'End Add All Student Profile Report Cards '.$reportCardId.' for Grade '.$educationGradeId.' to email processes ('.FrozenTime::now().')');
    }

    private function triggerEmailAllReportCardsShell($institutionId, $educationGradeId, $institutionClassId, $reportCardId, $studentId = null)
    {
        $SystemProcesses = TableRegistry::getTableLocator()->get('SystemProcesses');
        $runningProcess = $SystemProcesses->getRunningProcesses($this->StudentReportCardEmailProcesses->getRegistryAlias());

        // to-do: add logic to purge shell which is 30 minutes old

        if (count($runningProcess) <= self::MAX_PROCESSES) {
            $name = 'EmailAllStudentReportCards';
            $pid = '';
            $processModel = $this->StudentReportCardEmailProcesses->getRegistryAlias();
            $eventName = '';
            $passArray = [
                'institution_id' => $institutionId,
                'education_grade_id' => $educationGradeId,
                'student_profile_template_id' => $reportCardId
            ];
            if (!is_null($studentId)) {
                $name = 'EmailAllStudentReportCards';
                $passArray['student_id'] = $studentId;
            }
            $params = json_encode($passArray);
            $systemProcessId = $SystemProcesses->addProcess($name, $pid, $processModel, $eventName, $params);
            $SystemProcesses->updateProcess($systemProcessId, null, $SystemProcesses::RUNNING, 0);

            $args = '';
            $args .= !is_null($systemProcessId) ? ' '.$systemProcessId : '';

            $cmd = ROOT . DS . 'bin' . DS . 'cake EmailAllStudentReportCards'.$args;
            $logs = ROOT . DS . 'logs' . DS . 'EmailAllStudentReportCards.log & echo $!';
            $shellCmd = $cmd . ' >> ' . $logs;

            try {
                $pid = exec($shellCmd);
                Log::write('debug', $shellCmd);
            } catch(\Exception $ex) {
                Log::write('error', __METHOD__ . ' exception when email all report cards : '. $ex);
            }
        }
    }

    private function getFile($phpResourceFile) {
        $file = '';
        while (!feof($phpResourceFile)) {
            $file .= fread($phpResourceFile, 8192);
        }
        fclose($phpResourceFile);

        return $file;
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'age') {
            return ''; //POCOR-9593: age indicator column has no header
        } else if ($field == 'openemis_no') {
            return __('OpenEMIS ID');
        } else if ($field == 'student_id') {
            return  __('Student');
        }else if ($field == 'status') {
            return  __('Status');
        }else if ($field == 'profile_name') {
            return  __('Profile Name');
        }else if ($field == 'started_on') {
            return  __('Started On');
        }else if ($field == 'completed_on') {
            return  __('Completed On');
        }else if ($field == 'report_queue') {
            return  __('Report Queue');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
