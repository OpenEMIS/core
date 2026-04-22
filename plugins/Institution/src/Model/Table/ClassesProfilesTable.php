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
 * This class is used to generate report from profile tabs
 * We can generate/download report and trigger event from this class
 * @author Anubhav Jain <anubhav.jain@mail.valuecoders.com>
 *
 */
class ClassesProfilesTable extends ControllerActionTable
{
    use ProfilePermissionTrait; //POCOR-9598: security_role_functions execute-permission check
    use StaleProfileBannerTrait; //POCOR-9593: stale-profile alert banner

    private $statusOptions = [];
    private $reportProcessList = [];

    //POCOR-9598: security_functions name+controller for class profile buttons (portable — no hardcoded IDs)
    const GENERATE_FUNCTION_NAME = 'Generate Classes Profile';
    const DOWNLOAD_FUNCTION_NAME = 'Download Classes Profile';
    const FUNCTION_CONTROLLER    = 'Institutions';
    // for status
    CONST NEW_REPORT = 1;
    CONST IN_PROGRESS = 2;
    CONST GENERATED = 3;
    CONST PUBLISHED = 4;
    CONST FAILED = 5; //POCOR-9598: Failed status for stuck-in-progress profiles

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
        $this->setTable('institutions');
        parent::initialize($config);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);

        $this->ReportCards = TableRegistry::getTableLocator()->get('ProfileTemplate.ClassTemplates');
        $this->ClassProfiles = TableRegistry::getTableLocator()->get('Institution.ClassProfiles');
        $this->ClassProfileProcesses = TableRegistry::getTableLocator()->get('ReportCard.ClassProfileProcesses');

        $this->statusOptions = [
            self::NEW_REPORT => __('New'),
            self::IN_PROGRESS => __('In Progress'),
            self::GENERATED => __('Generated'),
            self::PUBLISHED => __('Published'),
            self::FAILED => __('Failed') //POCOR-9598: Failed status
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
        return $events;
    }

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        // check if report card request is valid
        $reportCardId = $this->request->getQuery('class_profile_template_id');
        $academicPeriodId = $this->request->getQuery('academic_period_id');
        //START:POCOR-6667
        unset($buttons['view']);
        //END:POCOR-6667
        if (!is_null($reportCardId) && $this->ReportCards->exists([$this->ReportCards->getPrimaryKey() => $reportCardId])) {
            $indexAttr = ['role' => 'menuitem',
                'tabindex' => '-1',
                'escape' => false,
                'target' => '_blank'];
            $generateAttr = ['role' => 'menuitem',
                'tabindex' => '-1',
                'escape' => false];
            $params = [
                'class_profile_template_id' => $reportCardId,
                'institution_id' => $entity->id ?? $this->getQueryString('institution_id'), //POCOR-8551
                'academic_period_id' => $academicPeriodId,
                'institution_class_id' => $entity->institution_class_id,
            ];

            // Download button, status must be generated or published
            if ($this->AccessControl->check(['Institutions', 'ClassesProfiles', 'downloadExcel'])
                && $entity->has('report_card_status') && in_array($entity->report_card_status, [self::GENERATED, self::PUBLISHED])) 
            {
                $downloadUrl = $this->url('downloadExcel');
                $downloadUrl['1'] = $queryString;
                $buttons['download'] = [
                    'label' => '<i class="fa kd-download"></i>'.__('Download Excel'),
                    'attr' => $indexAttr,
                    'url' => $downloadUrl
                ];
            }

            //POCOR-9585 start
            if($this->AccessControl->check(['Institutions', 'ClassesProfiles', 'download']) &&
             $entity->has('report_card_status') && in_array($entity->report_card_status, [self::GENERATED, self::PUBLISHED]))
            {
                $viewPdfUrl = $this->url('viewPDF');
                $viewPdfUrl['1'] = $queryString;
                $buttons['viewPdf'] = [
                    'label' => '<i class="fa fa-eye"></i>'.__('View PDF'),
                    'attr' => $indexAttr,
                    'url' => $viewPdfUrl
                ];

                $downloadPdfUrl =$this->url('downloadPDF');
                $downloadPdfUrl['1'] = $queryString;
                $buttons['downloadPdf'] = [
                    'label' => '<i class="fa kd-download"></i>'.__('Download PDF'),
                    'attr' => $indexAttr,
                    'url' => $downloadPdfUrl
                ];
            } //POCOR-9585 end

            // Generate button, all statuses
            if ($this->AccessControl->check(['Institutions', 'ClassesProfiles', 'generate'])) {
                $generateUrl = $this->setQueryString($this->url('generate'), $params);

                $reportCard = $this->ReportCards
                                    ->find()
                                    ->where([
                                        $this->ReportCards->aliasField('id') => $reportCardId])
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
                            'url' => $generateUrl
                            ];
                } else {
                    $generateAttr['title'] = $this->getMessage('ClassesProfiles.date_closed');
                    $buttons['generate'] = [
                            'label' => '<i class="fa fa-refresh"></i>'. __('Generate'),
                            'attr' => $generateAttr,
                            'url' => 'javascript:void(0)'
                            ];
                }
            }
            // Publish button, status must be generated
            if ($this->AccessControl->check(['Institutions', 'ClassesProfiles', 'publish']) && $entity->has('report_card_status')
                    && ( $entity->report_card_status == self::GENERATED
                         || $entity->report_card_status == '12'
                       )
                ) {
                $publishUrl = $this->setQueryString($this->url('publish'), $params);
                $buttons['publish'] = [
                    'label' => '<i class="fa kd-publish"></i>'.__('Publish'),
                    'attr' => $generateAttr,
                    'url' => $publishUrl
                ];
            }

            // Unpublish button, status must be published
            if ($this->AccessControl->check(['Institutions', 'ClassesProfiles', 'unpublish'])
                    && $entity->has('report_card_status')
                    && ( $entity->report_card_status == self::PUBLISHED
                          || $entity->report_card_status == '16'
                        )
                    ) {
                $unpublishUrl = $this->setQueryString($this->url('unpublish'), $params);
                $buttons['unpublish'] = [
                    'label' => '<i class="fa kd-unpublish"></i>'.__('Unpublish'),
                    'attr' => $generateAttr,
                    'url' => $unpublishUrl
                ];
            }
        }
        return $buttons;
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('class_name', ['sort' => ['field' => 'class_name']]);
        $this->field('institution_name', ['sort' => ['field' => 'name']]);
        $this->field('profile_name');
        $this->field('status', ['sort' => ['field' => 'report_card_status']]);
        $this->field('started_on');
        $this->field('completed_on');
        $this->field('age', ['type' => 'string', 'label' => false]); //POCOR-9593: age indicator — no column header
        $this->fields['institution_class_id']['visible'] = false;
        $this->fields['next_institution_class_id']['visible'] = false;
        $this->fields['academic_period_id']['visible'] = false;
        $this->fields['student_status_id']['visible'] = false;
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('report_queue');
        $this->setFieldOrder(['age', 'class_name', 'institution_name', 'profile_name', 'status', 'started_on', 'completed_on', 'report_queue']); //POCOR-9593: age first, no label
        $this->setFieldVisible(['index'], ['age', 'class_name', 'institution_name', 'profile_name', 'status', 'started_on', 'completed_on', 'report_queue']); //POCOR-9593: age first, no label

        // SQL Query to get the current processing list for report_queue table
        $this->reportProcessList = $this->ClassProfileProcesses
            ->find()
            ->select([
                $this->ClassProfileProcesses->aliasField('class_profile_template_id'),
                $this->ClassProfileProcesses->aliasField('institution_id'),
                $this->ClassProfileProcesses->aliasField('academic_period_id'),
                $this->ClassProfileProcesses->aliasField('institution_class_id')
            ])
            ->where([
                $this->ClassProfileProcesses->aliasField('status') => $this->ClassProfileProcesses::NEW_PROCESS
            ])
            ->order([
                $this->ClassProfileProcesses->aliasField('created'),
            ])
            //->hydrate(false)
            ->toArray();
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->getSession();
        $institutionId = $this->getInstitutionID();
        $AcademicPeriod = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        // Academic Periods filter
        $academicPeriodOptions = $AcademicPeriod->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->getQuery('academic_period_id')) ? $this->request->getQuery('academic_period_id') : $AcademicPeriod->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        //End
        $ProfileTemplates = TableRegistry::getTableLocator()->get('ProfileTemplate.ClassProfileTemplates');
        // Report Cards filter
        $reportCardOptions = [];
        $reportCardOptions = $ProfileTemplates->find('list')
            ->where([
                $ProfileTemplates->aliasField('academic_period_id') => $selectedAcademicPeriod
            ])
            ->toArray();

        $reportCardOptions = ['-1' => '-- '.__('Select Class Profile Template').' --'] + $reportCardOptions;
        $selectedReportCard = !is_null($this->request->getQuery('class_profile_template_id')) ? $this->request->getQuery('class_profile_template_id') : -1;
        $this->controller->set(compact('reportCardOptions', 'selectedReportCard'));
        //End

        $where[$this->aliasField('id')] = $institutionId;
        //End
        $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        $query
            ->select([
                'institution_class_id' => $InstitutionClasses->aliasField('id'),
                'class_name' => $InstitutionClasses->aliasField('name'),
                'institution_name' => $this->aliasField('name'),
                'class_profile_template_id' => $this->ClassProfiles->aliasField('class_profile_template_id'),
                'report_card_status' => $this->ClassProfiles->aliasField('status'),
                'report_card_started_on' => $this->ClassProfiles->aliasField('started_on'),
                'report_card_completed_on' => $this->ClassProfiles->aliasField('completed_on'),
            ])
            ->innerJoin([$InstitutionClasses->getAlias() => $InstitutionClasses->getTable()],
                [
                    $InstitutionClasses->aliasField('institution_id = ') . $this->aliasField('id'),
                    $InstitutionClasses->aliasField('academic_period_id = ') . $selectedAcademicPeriod,
                ]
            )
            ->leftJoin([$this->ClassProfiles->getAlias() => $this->ClassProfiles->getTable()],
                [
                    $this->ClassProfiles->aliasField('institution_id = ') . $this->aliasField('id'),
                    $this->ClassProfiles->aliasField('academic_period_id = ') . $selectedAcademicPeriod,
                    $this->ClassProfiles->aliasField('institution_class_id = ') . $InstitutionClasses->aliasField('id'),
                    $this->ClassProfiles->aliasField('class_profile_template_id = ') . $selectedReportCard
                ]
            )
            ->where($where)
            //->autoFields(true)
            ->order([
                $this->aliasField('name'),
            ])
            ->all();

        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $extra['elements']['controls'] = ['name' => 'Institution.ReportCards/Classcontrols', 'data' => ['encodedQueryString' => $encodedQueryString], 'options' => [], 'order' => 1];

        // sort
        $sortList = ['report_card_status', 'class_name', 'institution_name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;

    }

    public function indexAfterAction(EventInterface $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $reportCardId = $this->request->getQuery('class_profile_template_id');
        $academicPeriodId = $this->request->getQuery('academic_period_id');
        $institutionId = $this->request->getQuery('institution_id') ?? $this->getInstitutionID(); //POCOR-9593: fallback to session institution_id

        //Log::debug('@ClassesProfilesTable::indexAfterAction ENTRY reportCardId=' . ($reportCardId ?? 'NULL') . ' academicPeriodId=' . ($academicPeriodId ?? 'NULL') . ' institutionId=' . ($institutionId ?? 'NULL')); //[TEMP-LOG]

        if (!is_null($reportCardId)) {
            $existingReportCard = $this->ReportCards->exists([$this->ReportCards->getPrimaryKey() => $reportCardId]);
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

                //Log::debug('@ClassesProfilesTable::indexAfterAction toolbar check generatedCount=' . $generatedCount . ' publishedCount=' . $publishedCount . ' reportCardId=' . $reportCardId); //[TEMP-LOG]

                $toolbarAttr = [
                    'class' => 'btn btn-xs btn-default',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false
                ];

                $params = [
                    'academic_period_id' => $academicPeriodId,
                    'class_profile_template_id' => $reportCardId,
                    'institution_id' => $institutionId
                ];
                // Download all button
                if ($generatedCount > 0 || $publishedCount > 0) {
                    $downloadButtonPdf['url'] = $this->setQueryString($this->url('downloadAllPdf'), $params);
                    $downloadButtonPdf['type'] = 'button';
                    $downloadButtonPdf['label'] = '<i class="fa kd-download"></i>';
                    $downloadButtonPdf['attr'] = $toolbarAttr;
                    $downloadButtonPdf['attr']['title'] = __('Download All PDF');
                    $extra['toolbarButtons']['downloadAllPdf'] = $downloadButtonPdf;
                }
                if ($generatedCount > 0 || $publishedCount > 0) {
                    $downloadButton['url'] = $this->setQueryString($this->url('downloadAll'), $params);
                    $downloadButton['type'] = 'button';
                    $downloadButton['label'] = '<i class="fa kd-download"></i>';
                    $downloadButton['attr'] = $toolbarAttr;
                    $downloadButton['attr']['title'] = __('Download All Excel');
                    $extra['toolbarButtons']['downloadAll'] = $downloadButton;
                }
                // Generate all button
                $generateButton['url'] = $this->setQueryString($this->url('generateAll'), $params);
                $generateButton['type'] = 'button';
                $generateButton['label'] = '<i class="fa fa-refresh"></i>';
                $generateButton['attr'] = $toolbarAttr;
                $generateButton['attr']['title'] = __('Generate All');
                if (!is_null($this->request->getQuery('class_profile_template_id'))) {
                    $reportCardId = $this->request->getQuery('class_profile_template_id');
                }

                $ReportCardsData = $this->ReportCards
                                    ->find()
                                    ->where([
                                        $this->ReportCards->aliasField('id') => $reportCardId])
                                    ->first();
                if (!empty($ReportCardsData->generate_start_date)) {
                    $generateStartDate = $ReportCardsData->generate_start_date->format('Y-m-d');
                }

                if (!empty($ReportCardsData->generate_end_date)) {
                    $generateEndDate = $ReportCardsData->generate_end_date->format('Y-m-d');
                }
                $date = FrozenTime::now()->format('Y-m-d');

                //Log::debug('@ClassesProfilesTable::indexAfterAction generateAll dateCheck generateStartDate=' . ($generateStartDate ?? 'NULL') . ' generateEndDate=' . ($generateEndDate ?? 'NULL') . ' today=' . $date . ' windowOpen=' . ((!empty($generateStartDate) && !empty($generateEndDate) && $date >= $generateStartDate && $date <= $generateEndDate) ? 'YES' : 'NO')); //[TEMP-LOG]

                if (!empty($generateStartDate) && !empty($generateEndDate) && $date >= $generateStartDate && $date <= $generateEndDate) {
                    $extra['toolbarButtons']['generateAll'] = $generateButton;
                } else {
                    //POCOR-9598: start - hide Generate All button and show warning when date window is closed
                    $this->Alert->warning(__('This profile template generation is not enabled. Consult with system administrator to check the dates.'), ['type' => 'string', 'reset' => true]);
                    //POCOR-9598: end
                }

                //POCOR-9593: start - stale profile banner (runs after POCOR-9598 so it overwrites with richer message)
                $staleTemplate = $this->ReportCards->find()
                    ->where([$this->ReportCards->aliasField('id') => $this->request->getQuery('class_profile_template_id')])
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
            }
        }
    }

    public function getSearchableFields(EventInterface $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'institution_name';
    }

    public function viewBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->setFieldOrder(['class_name', 'institution_name', 'report_card', 'status', 'started_on', 'completed_on', 'report_queue']);
        $this->setFieldVisible(['view'], ['class_name', 'institution_name', 'report_card', 'status', 'started_on', 'completed_on', 'report_queue']);
    }

    public function viewBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $params = $this->request->getQuery();
        if(!empty($params['class_profile_template_id'])) {
            $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
            $query
                ->select([
                    'institution_class_id' => $InstitutionClasses->aliasField('id'),
                    'class_name' => $InstitutionClasses->aliasField('name'),
                    'institution_name' => $this->aliasField('name'),
                    'class_profile_template_id' => $this->ClassProfiles->aliasField('class_profile_template_id'),
                    'report_card_status' => $this->ClassProfiles->aliasField('status'),
                    'report_card_started_on' => $this->ClassProfiles->aliasField('started_on'),
                    'report_card_completed_on' => $this->ClassProfiles->aliasField('completed_on'),
                ])
                ->innerJoin([$InstitutionClasses->getAlias() => $InstitutionClasses->getTable()],
                    [
                        $InstitutionClasses->aliasField('institution_id = ') . $this->aliasField('id'),
                        $InstitutionClasses->aliasField('academic_period_id = ') . $selectedAcademicPeriod,
                    ]
                )
                ->leftJoin([$this->ClassProfiles->getAlias() => $this->ClassProfiles->getTable()],
                    [
                        $this->ClassProfiles->aliasField('institution_id = ') . $this->aliasField('id'),
                        $this->ClassProfiles->aliasField('academic_period_id = ') . $params['academic_period_id'],
                        $this->ClassProfiles->aliasField('class_profile_template_id = ') . $params['class_profile_template_id']
                    ]
                )
                //->autoFields(true)
                ;
        } else {
            $query
                ->select([
                    'institution_name' => $this->aliasField('name'),
                ])
                //->autoFields(true)
                ;
        }
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

        $title = __('Generated %d days ago', $days); //POCOR-9593: single translatable string
        return '<span style="display:inline-block;width:14px;height:14px;background:' . $color . ';vertical-align:middle;" title="' . $title . '"></span>';
    }
    //POCOR-9593: end

    public function onGetReportQueue(EventInterface $event, Entity $entity)
    {
        if ($entity->has('class_profile_template_id')) {
            $reportCardId = $entity->class_profile_template_id;
        } else if (!is_null($this->request->getQuery('class_profile_template_id'))) {
            $reportCardId = $this->request->getQuery('class_profile_template_id');
        }

        $academicPeriodId = $this->request->getQuery('academic_period_id');

        $search = [
            'class_profile_template_id' => $reportCardId,
            'institution_class_id' => $entity->institution_class_id,
            'institution_id' => $entity->id,
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

    public function onGetProfileName(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('class_profile_template_id')) {
            $reportCardId = $entity->class_profile_template_id;
        } else if (!is_null($this->request->getQuery('class_profile_template_id'))) {
            // used if student report card record has not been created yet
            $reportCardId = $this->request->getQuery('class_profile_template_id');
        }

        if (!empty($reportCardId)) {
            $reportCardEntity = $this->ReportCards->find()->where(['id' => $reportCardId])->first();
            if (!empty($reportCardEntity)) {
                $value = $reportCardEntity->name;
            }
        }
        return $value;
    }

    public function downloadExcel(EventInterface $event, ArrayObject $extra)
    {
        $model = $this->ClassProfiles;
        $ids = $this->getQueryString();

        if ($model->exists($ids)) {
            $data = $model->get($ids);
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
        $model = $this->ClassProfiles;
        $ids = $this->getQueryString();

        if ($model->exists($ids)) {
            $data = $model->get($ids);
            $fileName = $data->file_name;
            $fileNameData = explode(".",$fileName);
			$fileName = $fileNameData[0].'.pdf';
			$pathInfo['extension'] = 'pdf';
            // START POCOR-7838
            $file_content_pdf = $data->file_content_pdf;
            if (empty($file_content_pdf)) {
                $this->Alert->error('No PDF Generated', ['type' => 'text']);
                return $this->controller->redirect($this->url('index'));
            }
            if (!empty($file_content_pdf)) {
                $file = $this->getFile($file_content_pdf);
            }
            // END POCOR-7838
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
        $model = $this->ClassProfiles;
        $ids = $this->getQueryString();

        if ($model->exists($ids)) {
            $data = $model->get($ids);
            $fileName = $data->file_name;
            $fileNameData = explode(".",$fileName);

			$fileName = $fileNameData[0].'.pdf';
			$pathInfo['extension'] = 'pdf';
			// START POCOR-7838
            $file_content_pdf = $data->file_content_pdf;
            if (empty($file_content_pdf)) {
                $this->Alert->error('No PDF Generated', ['type' => 'text']);
                return $this->controller->redirect($this->url('index'));
            }
            if (!empty($file_content_pdf)) {
                $file = $this->getFile($file_content_pdf);
            }
            // END POCOR-7838
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
            header('Content-Disposition: inline; filename="' . $filename . '"');

            echo $file;
        }
        exit();
    }

    public function generate(EventInterface $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        //Log::debug('@ClassesProfilesTable::generate ENTRY params=' . json_encode($params)); //[TEMP-LOG]
        $hasTemplate = $this->ReportCards->checkIfHasTemplate($params['class_profile_template_id']);
        //Log::debug('@ClassesProfilesTable::generate hasTemplate=' . ($hasTemplate ? 'true' : 'false') . ' class_profile_template_id=' . ($params['class_profile_template_id'] ?? 'NULL') . ' institutionId=' . ($params['institution_id'] ?? 'NULL')); //[TEMP-LOG]

        if ($hasTemplate) {
            //Log::debug('@ClassesProfilesTable::generate hasTemplate=true, calling addReportCardsToProcesses'); //[TEMP-LOG]
            $this->addReportCardsToProcesses($params['academic_period_id'], $params['class_profile_template_id'], $params['institution_id'], $params['institution_class_id']);
            //Log::debug('@ClassesProfilesTable::generate back from addReportCardsToProcesses, calling triggerGenerateReportCardCommand'); //[TEMP-LOG]
            $this->triggerGenerateReportCardCommand($params['academic_period_id'], $params['class_profile_template_id'], $params['institution_id'], $params['institution_class_id']);
            //Log::debug('@ClassesProfilesTable::generate back from triggerGenerateReportCardCommand'); //[TEMP-LOG]
            $this->Alert->warning('ReportCardStatuses.generate');
        } else {
            //Log::debug('@ClassesProfilesTable::generate hasTemplate=false, showing noTemplate alert'); //[TEMP-LOG]
            $url = $this->url('index');
            $this->Alert->warning('ReportCardStatuses.noTemplate');
        }

        $event->stopPropagation();
        //Log::debug('@ClassesProfilesTable::generate EXIT redirecting to index'); //[TEMP-LOG]
        return $this->controller->redirect($this->url('index'));
    }

    public function generateAll(EventInterface $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        //Log::debug('@ClassesProfilesTable::generateAll ENTRY params=' . json_encode($params)); //[TEMP-LOG]
        $hasTemplate = $this->ReportCards->checkIfHasTemplate($params['class_profile_template_id']);

        if ($hasTemplate) {
            $InstitutionReportCardProcesses = TableRegistry::getTableLocator()->get('ReportCard.ClassProfileProcesses');
            $inProgress = $InstitutionReportCardProcesses->find()
                ->where([
                    $InstitutionReportCardProcesses->aliasField('class_profile_template_id') => $params['class_profile_template_id'],
                    $InstitutionReportCardProcesses->aliasField('institution_id') => $params['institution_id'],
                    $InstitutionReportCardProcesses->aliasField('institution_class_id') => $params['institution_class_id'],
                    $InstitutionReportCardProcesses->aliasField('academic_period_id') => $params['academic_period_id']
                ])
                ->count();

            //Log::debug('@ClassesProfilesTable::generateAll hasTemplate=' . ($hasTemplate ? 'true' : 'false') . ' inProgress=' . $inProgress . ' institutionId=' . ($params['institution_id'] ?? 'NULL')); //[TEMP-LOG]
            if (!$inProgress) {
                //Log::debug('@ClassesProfilesTable::generateAll inProgress=0, calling addReportCardsToProcesses'); //[TEMP-LOG]
                $this->addReportCardsToProcesses($params['academic_period_id'], $params['class_profile_template_id'], $params['institution_id'], $params['institution_class_id']);
                //Log::debug('@ClassesProfilesTable::generateAll back from addReportCardsToProcesses, calling triggerGenerateReportCardCommand'); //[TEMP-LOG]
                $this->triggerGenerateReportCardCommand($params['academic_period_id'], $params['class_profile_template_id'], $params['institution_id'], $params['institution_class_id']);
                //Log::debug('@ClassesProfilesTable::generateAll back from triggerGenerateReportCardCommand'); //[TEMP-LOG]
                $this->Alert->warning('ReportCardStatuses.generateAll');
            } else {
                //Log::debug('@ClassesProfilesTable::generateAll inProgress=' . $inProgress . ', showing inProgress alert'); //[TEMP-LOG]
                $this->Alert->warning('ReportCardStatuses.inProgress');
            }
        } else {
            //Log::debug('@ClassesProfilesTable::generateAll hasTemplate=false, showing noTemplate alert'); //[TEMP-LOG]
            $this->Alert->warning('ReportCardStatuses.noTemplate');
        }

        $event->stopPropagation();
        //Log::debug('@ClassesProfilesTable::generateAll EXIT redirecting to index'); //[TEMP-LOG]
        return $this->controller->redirect($this->url('index'));
    }

    public function downloadAllPdf(EventInterface $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        // only download report cards with generated or published status
        $statusArray = [self::GENERATED, self::PUBLISHED];

        $files = $this->ClassProfiles->find()
            ->contain(['ClassTemplates'])
            ->where([
                $this->ClassProfiles->aliasField('class_profile_template_id') => $params['class_profile_template_id'],
                $this->ClassProfiles->aliasField('academic_period_id') => $params['academic_period_id'],
                $this->ClassProfiles->aliasField('status IN ') => $statusArray,
                $this->ClassProfiles->aliasField('file_name IS NOT NULL'),
                $this->ClassProfiles->aliasField('file_content IS NOT NULL')
                // START POCOR-7838
                , $this->ClassProfiles->aliasField('file_content_pdf IS NOT NULL')
                // END POCOR-7838
            ])
            ->toArray();

        if (!empty($files)) {
            $path = WWW_ROOT . 'export' . DS . 'customexcel' . DS;
            $zipName = 'ClassProfiles' . '_' . date('Ymd') . 'T' . date('His') . '.zip';
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
            $this->Alert->warning('ReportCardStatuses.noFilesToDownload');
            return $this->controller->redirect($this->url('index'));
        }
    }

    public function downloadAll(EventInterface $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();

        // only download report cards with generated or published status
        $statusArray = [self::GENERATED, self::PUBLISHED];

        $files = $this->ClassProfiles->find()
            ->contain(['ClassTemplates'])
            ->where([
                $this->ClassProfiles->aliasField('class_profile_template_id') => $params['class_profile_template_id'],
                $this->ClassProfiles->aliasField('academic_period_id') => $params['academic_period_id'],
                $this->ClassProfiles->aliasField('status IN ') => $statusArray,
                $this->ClassProfiles->aliasField('file_name IS NOT NULL'),
                $this->ClassProfiles->aliasField('file_content IS NOT NULL')
            ])
            ->toArray();

        if (!empty($files)) {
            $path = WWW_ROOT . 'export' . DS . 'customexcel' . DS;
            $zipName = 'ClassProfiles' . '_' . date('Ymd') . 'T' . date('His') . '.zip';
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
            $this->Alert->warning('ReportCardStatuses.noFilesToDownload');
            return $this->controller->redirect($this->url('index'));
        }
    }

    public function publish(EventInterface $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        $result = $this->ClassProfiles->updateAll(['status' => self::PUBLISHED], $params);
        $this->Alert->success('ReportCardStatuses.publish');
        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function publishAll(EventInterface $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();

        // only publish report cards with generated status to published status
        $result = $this->ClassProfiles->updateAll(['status' => self::PUBLISHED], [
            $params,
            'status' => self::GENERATED
        ]);

        if ($result) {
            $this->Alert->success('ReportCardStatuses.publishAll');
        } else {
            $this->Alert->warning('ReportCardStatuses.noFilesToPublish');
        }

        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function unpublish(EventInterface $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        $result = $this->ClassProfiles->updateAll(['status' => self::NEW_REPORT], $params);
        $this->Alert->success('ReportCardStatuses.unpublish');
        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function unpublishAll(EventInterface $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        // only unpublish report cards with published status to new status
        $result = $this->ClassProfiles->updateAll(['status' => self::NEW_REPORT], [
            $params,
            'status' => self::PUBLISHED
        ]);

        if ($result) {
            $this->Alert->success('ReportCardStatuses.unpublishAll');
        } else {
            $this->Alert->warning('ReportCardStatuses.noFilesToUnpublish');
        }

        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    private function addReportCardsToProcesses($academicPeriodId, $reportCardId, $institutionId = null, $institutionClassId = null)
    {
        //Log::debug('@ClassesProfilesTable::addReportCardsToProcesses ENTRY academicPeriodId=' . $academicPeriodId . ' reportCardId=' . $reportCardId . ' institutionId=' . ($institutionId ?? 'NULL') . ' institutionClassId=' . ($institutionClassId ?? 'NULL')); //[TEMP-LOG]
        Log::write('debug', 'Initialize Add All Class Report Cards '.$reportCardId.' for Institution '.$institutionId.' to processes ('.FrozenTime::now().')');

        $ClassProfileProcesses = TableRegistry::getTableLocator()->get('ReportCard.ClassProfileProcesses');
        $InstitutionTable = TableRegistry::getTableLocator()->get('Institution.Institutions');//POCOR-8551
        $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        $where = [];
        if (!is_null($institutionId)) {
            $where[$InstitutionTable->aliasField('id')] = $institutionId;
        }
        if(!is_null($institutionClassId)){
            $where[$InstitutionClasses->aliasField('id')] = $institutionClassId;
        }
        $institutionData = $InstitutionTable->find()
            ->select([
                $InstitutionTable->aliasField('id'),
                'institution_class_id' => $InstitutionClasses->aliasField('id'),
            ])
            ->innerJoin([$InstitutionClasses->getAlias() => $InstitutionClasses->getTable()],//POCOR-8551
                [
                    $InstitutionClasses->aliasField('institution_id = ') . $InstitutionTable->aliasField('id'),
                    $InstitutionClasses->aliasField('academic_period_id = ') . $academicPeriodId,
                ]
            )
            ->where($where)
            ->toArray();

        //Log::debug('@ClassesProfilesTable::addReportCardsToProcesses fetched ' . count($institutionData) . ' institution records'); //[TEMP-LOG]

        foreach ($institutionData as $institution) {
            // Class Report card processes
            $idKeys = [
                'class_profile_template_id' => $reportCardId,
                'institution_id' => $institution->id,
                'institution_class_id' => $institution->institution_class_id,
            ];

            $data = [
                'status' => $ClassProfileProcesses::NEW_PROCESS,
                'academic_period_id' => $academicPeriodId,
                'created' => date('Y-m-d H:i:s')
            ];
            $obj = array_merge($idKeys, $data);
            $newEntity = $ClassProfileProcesses->newEntity($obj);
            $ClassProfileProcesses->save($newEntity);
            // end

            // class report card
            $recordIdKeys = [
                'class_profile_template_id' => $reportCardId,
                'institution_id' => $institution->id,
                'academic_period_id' => $academicPeriodId,
                'institution_class_id' => $institution->institution_class_id,
            ];

            if ($this->ClassProfiles->exists($recordIdKeys)) {
                $classesReportCardEntity = $this->ClassProfiles->find()
                    ->where($recordIdKeys)
                    ->first();

                $newData = [
                    'status' => $this->ClassProfiles::NEW_REPORT,
                    'started_on' => NULL,
                    'completed_on' => NULL,
                    'file_name' => NULL,
                    'file_content' => NULL,
                    'institution_id' => $institution->id,
                    'institution_class_id' => $institution->institution_class_id
                ];

                $newEntity = $this->ClassProfiles->patchEntity($classesReportCardEntity, $newData);

                if (!$this->ClassProfiles->save($newEntity)) {
                    Log::write('debug', 'Error Add All Class profile Report Cards '.$reportCardId.' for Class '.$institution->institution_class_id.' of Institution'.$institution->id.' to processes ('.FrozenTime::now().')');
                    Log::write('debug', $newEntity->errors());
                }
            }
            // end
        }

        Log::write('debug', 'End Add All Class profile Report Cards '.$reportCardId.' for Class '.$institution->institution_class_id.' of Institution'.$institution->id.' to processes ('.FrozenTime::now().')');
        //Log::debug('@ClassesProfilesTable::addReportCardsToProcesses EXIT total records added=' . count($institutionData)); //[TEMP-LOG]
    }

    private function triggerGenerateReportCardCommand($academicPeriodId, $reportCardId, $institutionId = null, $institutionClassId = null)
    {
        //Log::debug('@ClassesProfilesTable::triggerGenerateReportCardCommand ENTRY academicPeriodId=' . $academicPeriodId . ' reportCardId=' . $reportCardId . ' institutionId=' . ($institutionId ?? 'NULL') . ' institutionClassId=' . ($institutionClassId ?? 'NULL')); //[TEMP-LOG]

        $SystemProcesses = TableRegistry::getTableLocator()->get('SystemProcesses');
        $ClassProfileProcesses = TableRegistry::getTableLocator()->get('ReportCard.ClassProfileProcesses');
        $today = FrozenTime::now();

        //POCOR-9598: start — reset class_profile_processes queue records stuck in RUNNING for > 6 hours
        $cutoff6h = clone($today);
        $cutoff6h->subHours(24); //POCOR-9598: 24h window for large countries
        $stuckQueueCount = $ClassProfileProcesses->find()
            ->where([
                $ClassProfileProcesses->aliasField('status') => $ClassProfileProcesses::RUNNING,
                $ClassProfileProcesses->aliasField('created') . ' <' => $cutoff6h->format('Y-m-d H:i:s'),
            ])
            ->count();
        //Log::debug('@ClassesProfilesTable::triggerGenerateReportCardCommand stuckQueueCount (RUNNING > 24h)=' . $stuckQueueCount . ' cutoff=' . $cutoff6h->format('Y-m-d H:i:s')); //[TEMP-LOG]
        if ($stuckQueueCount > 0) {
            $ClassProfileProcesses->updateAll(
                ['status' => $ClassProfileProcesses::NEW_PROCESS],
                [
                    $ClassProfileProcesses->aliasField('status') => $ClassProfileProcesses::RUNNING,
                    $ClassProfileProcesses->aliasField('created') . ' <' => $cutoff6h->format('Y-m-d H:i:s'),
                ]
            );
            //Log::debug('@ClassesProfilesTable::triggerGenerateReportCardCommand reset ' . $stuckQueueCount . ' stuck queue records back to NEW_PROCESS'); //[TEMP-LOG]
        }
        //POCOR-9598: end

        $runningProcess = $SystemProcesses->getRunningProcesses($this->getRegistryAlias()); //POCOR-8551

        //Log::debug('@ClassesProfilesTable::triggerGenerateReportCardCommand runningProcessCount=' . count($runningProcess) . ' MAX_PROCESSES=' . self::MAX_PROCESSES . ' registryAlias=' . $this->getRegistryAlias()); //[TEMP-LOG]

        //POCOR-9598: start — expire system_processes stuck RUNNING for > 30 min and re-query for fresh count
        foreach ($runningProcess as $key => $processData) {
            $systemProcessId = $processData['id'];
            $pId = !empty($processData['process_id']) ? $processData['process_id'] : 0;
            $createdDate = $processData['created'];

            $expiryDate = clone($createdDate);
            $expiryDate->addMinutes(30);

            //Log::debug('@ClassesProfilesTable::triggerGenerateReportCardCommand checking stale process systemProcessId=' . $systemProcessId . ' pId=' . $pId . ' createdDate=' . $createdDate->format('Y-m-d H:i:s') . ' expiryDate=' . $expiryDate->format('Y-m-d H:i:s') . ' expired=' . ($expiryDate < $today ? 'YES' : 'NO')); //[TEMP-LOG]

            if ($expiryDate < $today) {
                //Log::debug('@ClassesProfilesTable::triggerGenerateReportCardCommand marking stale process ' . $systemProcessId . ' as COMPLETED and killing pid ' . $pId); //[TEMP-LOG]
                $SystemProcesses->updateProcess($systemProcessId, FrozenTime::now(), $SystemProcesses::COMPLETED);
                $SystemProcesses->killProcess($pId);
            }
        }
        // Re-query after cleanup so the spawn decision uses the actual live count, not the pre-cleanup snapshot
        $runningProcess = $SystemProcesses->getRunningProcesses($this->getRegistryAlias());
        //POCOR-9598: end

        //Log::debug('@ClassesProfilesTable::triggerGenerateReportCardCommand after stale check, freshRunningCount=' . count($runningProcess) . ' MAX_PROCESSES=' . self::MAX_PROCESSES . ' willSpawn=' . (count($runningProcess) <= self::MAX_PROCESSES ? 'YES' : 'NO')); //[TEMP-LOG]

        if (count($runningProcess) <= self::MAX_PROCESSES) {
            $processModel = $this->getRegistryAlias();//POCOR-8551
            $passArray = [
                'institution_id' => $institutionId,
                'class_profile_template_id' => $reportCardId,
                'institution_class_id' => $institutionClassId
            ];

            $params = json_encode($passArray);

            $args = escapeshellarg($processModel) . ' ' . escapeshellarg($params); //POCOR-9598: escapeshellarg prevents bash brace expansion splitting JSON on commas

            $cmd = ROOT . DS . 'bin' . DS . 'cake generate_class_profile '.$args; //POCOR-9598: migrated from Shell to Command
            $logs = ROOT . DS . 'logs' . DS . 'GenerateAllClassProfiles.log 2>&1 & echo $!'; //POCOR-9598: 2>&1 captures stderr (PHP fatal errors, bootstrap crashes)
            $shellCmd = $cmd . ' >> ' . $logs;

            //Log::debug('@ClassesProfilesTable::triggerGenerateReportCardCommand SPAWNING cmd=' . $shellCmd); //[TEMP-LOG]

            try {
                $pid = exec($shellCmd);
                //Log::debug('@ClassesProfilesTable::triggerGenerateReportCardCommand process spawned with pid=' . $pid); //[TEMP-LOG]
                Log::write('debug', $shellCmd);
            } catch(\Exception $ex) {
                Log::error('@ClassesProfilesTable::triggerGenerateReportCardCommand exception when spawning: ' . $ex->getMessage());
                Log::write('error', __METHOD__ . ' exception when generate all report cards : '. $ex);
            }
        } else {
            //Log::debug('@ClassesProfilesTable::triggerGenerateReportCardCommand NOT spawning, reached MAX_PROCESSES=' . self::MAX_PROCESSES); //[TEMP-LOG]
        }

        //Log::debug('@ClassesProfilesTable::triggerGenerateReportCardCommand EXIT'); //[TEMP-LOG]
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
        } else if ($field == 'institution_name') {
            return __('Institution Name');
        } else if ($field == 'class_name') {
            return  __('Class Name');
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
