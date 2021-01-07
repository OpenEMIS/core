<?php
namespace Institution\Model\Table;

use ArrayObject;
use ZipArchive;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\ResultSet;
use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;

class ReportCardStatusesTable extends ControllerActionTable
{
    private $statusOptions = [];
    private $reportProcessList = [];

    // for status
    CONST NEW_REPORT = 1;
    CONST IN_PROGRESS = 2;
    CONST GENERATED = 3;
    CONST PUBLISHED = 4;

    CONST MAX_PROCESSES = 2;

    public function initialize(array $config)
    {
        $this->table('institution_class_students');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
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

        $this->ReportCards = TableRegistry::get('ReportCard.ReportCards');
        $this->StudentsReportCards = TableRegistry::get('Institution.InstitutionStudentsReportCards');
        $this->ReportCardEmailProcesses = TableRegistry::get('ReportCard.ReportCardEmailProcesses');
        $this->ReportCardProcesses = TableRegistry::get('ReportCard.ReportCardProcesses');

        $this->statusOptions = [
            self::NEW_REPORT => __('New'),
            self::IN_PROGRESS => __('In Progress'),
            self::GENERATED => __('Generated'),
            self::PUBLISHED => __('Published')
        ];
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.generate'] = 'generate';
        $events['ControllerAction.Model.generateAll'] = 'generateAll';
        $events['ControllerAction.Model.downloadAll'] = 'downloadAll';
        $events['ControllerAction.Model.downloadAllPdf'] = 'downloadAllPdf';
        $events['ControllerAction.Model.publish'] = 'publish';
        $events['ControllerAction.Model.publishAll'] = 'publishAll';
        $events['ControllerAction.Model.unpublish'] = 'unpublish';
        $events['ControllerAction.Model.unpublishAll'] = 'unpublishAll';
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';
        $events['ControllerAction.Model.email'] = 'email';
        $events['ControllerAction.Model.emailAll'] = 'emailAll';
        return $events;
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        // check if report card request is valid
        $reportCardId = $this->request->query('report_card_id');
        if (!is_null($reportCardId) && $this->ReportCards->exists([$this->ReportCards->primaryKey() => $reportCardId])) {

            $indexAttr = ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false];
            $params = [
                'report_card_id' => $reportCardId,
                'student_id' => $entity->student_id,
                'institution_id' => $entity->institution_id,
                'academic_period_id' => $entity->academic_period_id,
                'education_grade_id' => $entity->education_grade_id,
            ];

            // Download button, status must be generated or published
            if ($this->AccessControl->check(['Institutions', 'InstitutionStudentsReportCards', 'download']) && $entity->has('report_card_status') && in_array($entity->report_card_status, [self::GENERATED, self::PUBLISHED])) {
                $downloadUrl = [
                    'plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'InstitutionStudentsReportCards',
                    '0' => 'download',
                    '1' => $this->paramsEncode($params)
                ];
                $buttons['download'] = [
                    'label' => '<i class="fa kd-download"></i>'.__('Download Excel'),
                    'attr' => $indexAttr,
                    'url' => $downloadUrl
                ];
				$downloadPdfUrl = [
                    'plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'InstitutionStudentsReportCards',
                    '0' => 'downloadPdf',
                    '1' => $this->paramsEncode($params)
                ];
                $buttons['downloadPdf'] = [
                    'label' => '<i class="fa kd-download"></i>'.__('Download PDF'),
                    'attr' => $indexAttr,
                    'url' => $downloadPdfUrl
                ];
            }

            $params['institution_class_id'] = $entity->institution_class_id;

            // Generate button, all statuses
            if ($this->AccessControl->check(['Institutions', 'ReportCardStatuses', 'generate'])) {
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
                $date = Time::now()->format('Y-m-d');

                if ((!empty($generateStartDate) && !empty($generateEndDate)) && ($date >= $generateStartDate && $date <= $generateEndDate)) {
                            $buttons['generate'] = [
                            'label' => '<i class="fa fa-refresh"></i>'. __('Generate'),
                            'attr' => $indexAttr,
                            'url' => $generateUrl
                            ];
                } else {
                    $indexAttr['title'] = $this->getMessage('ReportCardStatuses.date_closed');
                    $buttons['generate'] = [
                            'label' => '<i class="fa fa-refresh"></i>'. __('Generate'),
                            'attr' => $indexAttr,
                            'url' => 'javascript:void(0)'
                            ];
                } 
            }

            // Publish button, status must be generated
            if ($this->AccessControl->check(['Institutions', 'ReportCardStatuses', 'publish']) && $entity->has('report_card_status') 
                    && ( $entity->report_card_status == self::GENERATED 
                         || $entity->report_card_status == '12' 
                       )
                ) {
                $publishUrl = $this->setQueryString($this->url('publish'), $params);
                $buttons['publish'] = [
                    'label' => '<i class="fa kd-publish"></i>'.__('Publish'),
                    'attr' => $indexAttr,
                    'url' => $publishUrl
                ];
            }

            // Unpublish button, status must be published
            if ($this->AccessControl->check(['Institutions', 'ReportCardStatuses', 'unpublish']) 
                    && $entity->has('report_card_status') 
                    && ( $entity->report_card_status == self::PUBLISHED 
                          || $entity->report_card_status == '16'
                        )
                    ) {
                $unpublishUrl = $this->setQueryString($this->url('unpublish'), $params);
                $buttons['unpublish'] = [
                    'label' => '<i class="fa kd-unpublish"></i>'.__('Unpublish'),
                    'attr' => $indexAttr,
                    'url' => $unpublishUrl
                ];
            }

            // Single email button, status must be published
            if ($this->AccessControl->check(['Institutions', 'ReportCardStatuses', 'email']) 
                    && $entity->has('report_card_status')
                    && ( $entity->report_card_status == self::PUBLISHED 
                            || $entity->report_card_status == '16' 
                        )
               )
               {
                if (empty($entity->email_status_id) || ($entity->has('email_status_id') && $entity->email_status_id != $this->ReportCardEmailProcesses::SENDING)) {
                    $emailUrl = $this->setQueryString($this->url('email'), $params);
                    $buttons['email'] = [
                        'label' => '<i class="fa fa-envelope"></i>'.__('Email'),
                        'attr' => $indexAttr,
                        'url' => $emailUrl
                    ];
                }
            }
        }
        return $buttons;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('openemis_no', ['sort' => ['field' => 'Users.openemis_no']]);
        $this->field('student_id', ['type' => 'integer', 'sort' => ['field' => 'Users.first_name']]);
        $this->field('report_card');
        $this->field('status', ['sort' => ['field' => 'report_card_status']]);
        $this->field('started_on');
        $this->field('completed_on');
        $this->field('email_status');
        $this->fields['next_institution_class_id']['visible'] = false;
        $this->fields['academic_period_id']['visible'] = false;
        $this->fields['student_status_id']['visible'] = false;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('report_queue');
        $this->setFieldOrder(['openemis_no', 'student_id', 'report_card', 'status', 'started_on', 'completed_on', 'report_queue', 'email_status']);

        // SQL Query to get the current processing list for report_queue table
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
                $this->ReportCardProcesses->aliasField('status') => $this->ReportCardProcesses::NEW_PROCESS
            ])
            ->order([
                $this->ReportCardProcesses->aliasField('created'),
                $this->ReportCardProcesses->aliasField('student_id')
            ])
            ->hydrate(false)
            ->toArray();
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $Classes = $this->InstitutionClasses;
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');

        // Academic Periods filter
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent();
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
        $selectedReportCard = !is_null($this->request->query('report_card_id')) ? $this->request->query('report_card_id') : -1;
        $this->controller->set(compact('reportCardOptions', 'selectedReportCard'));
        //End

        // Class filter
        $classOptions = [];
        $selectedClass = !is_null($this->request->query('class_id')) ? $this->request->query('class_id') : -1;

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
            } else {
                // if selected report card is not valid, do not show any students
                $selectedClass = -1;
            }
        }
        
        if(!empty($classOptions)){
            $classOptions['all']   = "All Classes" ;
        }
        
        $classOptions = ['-1' => '-- '.__('Select Class').' --'] + $classOptions;
        $this->controller->set(compact('classOptions', 'selectedClass'));
        $where[$this->aliasField('institution_class_id')] = $selectedClass;
        //End

        $query
            ->select([
                'report_card_id' => $this->StudentsReportCards->aliasField('report_card_id'),
                'report_card_status' => $this->StudentsReportCards->aliasField('status'),
                'report_card_started_on' => $this->StudentsReportCards->aliasField('started_on'),
                'report_card_completed_on' => $this->StudentsReportCards->aliasField('completed_on'),
                'email_status_id' => $this->ReportCardEmailProcesses->aliasField('status'),
                'email_error_message' => $this->ReportCardEmailProcesses->aliasField('error_message')
            ])
            ->leftJoin([$this->StudentsReportCards->alias() => $this->StudentsReportCards->table()],
                [
                    $this->StudentsReportCards->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $this->StudentsReportCards->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $this->StudentsReportCards->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                    $this->StudentsReportCards->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
                    $this->StudentsReportCards->aliasField('institution_class_id = ') . $this->aliasField('institution_class_id'),
                    $this->StudentsReportCards->aliasField('report_card_id = ') . $selectedReportCard
                ]
            )
            ->leftJoin([$this->ReportCardEmailProcesses->alias() => $this->ReportCardEmailProcesses->table()],
                [
                    $this->ReportCardEmailProcesses->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $this->ReportCardEmailProcesses->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $this->ReportCardEmailProcesses->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                    $this->ReportCardEmailProcesses->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
                    $this->ReportCardEmailProcesses->aliasField('institution_class_id = ') . $this->aliasField('institution_class_id'),
                    $this->ReportCardEmailProcesses->aliasField('report_card_id = ') . $selectedReportCard
                ]
            )
            ->autoFields(true)
            ->where($where)
            ->all();

        if (is_null($this->request->query('sort'))) {
            $query
                ->contain('Users')
                ->order(['Users.first_name', 'Users.last_name']);
        }

        $extra['elements']['controls'] = ['name' => 'Institution.ReportCards/controls', 'data' => [], 'options' => [], 'order' => 1];

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

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $reportCardId = $this->request->query('report_card_id');
        $classId = $this->request->query('class_id');

        if (!is_null($reportCardId) && !is_null($classId)) {
            $existingReportCard = $this->ReportCards->exists([$this->ReportCards->primaryKey() => $reportCardId]);
            $existingClass = $this->InstitutionClasses->exists([$this->InstitutionClasses->primaryKey() => $classId]);

            // only show toolbar buttons if request for report card and class is valid
            if ($existingReportCard && $existingClass) {
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
                    'institution_id' => $this->Session->read('Institution.Institutions.id'),
                    'institution_class_id' => $classId,
                    'report_card_id' => $reportCardId
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
                //$ReportCards = TableRegistry::get('ReportCard.ReportCards');
                if (!is_null($this->request->query('report_card_id'))) {
                    $reportCardId = $this->request->query('report_card_id');
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
                $date = Time::now()->format('Y-m-d');

                if (!empty($generateStartDate) && !empty($generateEndDate) && $date >= $generateStartDate && $date <= $generateEndDate) {
                    
                    $extra['toolbarButtons']['generateAll'] = $generateButton;
                    
                } else { 
                    $generateButton['attr']['data-html'] = true;
                    $generateButton['attr']['title'] .= __('<br>'.$this->getMessage('ReportCardStatuses.date_closed'));
                    $generateButton['url'] = 'javascript:void(0)';
                    $extra['toolbarButtons']['generateAll'] = $generateButton;
                }

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

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'student_id';
        $searchableFields[] = 'openemis_no';
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('institution_class_id', ['type' => 'integer']);
        $this->field('academic_period_id', ['visible' => true]);
        $this->setFieldOrder(['academic_period_id', 'institution_class_id', 'openemis_no', 'student_id', 'report_card', 'status', 'started_on', 'completed_on', 'report_queue', 'email_status']);
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $params = $this->request->query;

        $query
            ->select([
                'report_card_id' => $this->StudentsReportCards->aliasField('report_card_id'),
                'report_card_status' => $this->StudentsReportCards->aliasField('status'),
                'report_card_started_on' => $this->StudentsReportCards->aliasField('started_on'),
                'report_card_completed_on' => $this->StudentsReportCards->aliasField('completed_on'),
                'email_status_id' => $this->ReportCardEmailProcesses->aliasField('status'),
                'email_error_message' => $this->ReportCardEmailProcesses->aliasField('error_message')
            ])
            ->leftJoin([$this->StudentsReportCards->alias() => $this->StudentsReportCards->table()],
                [
                    $this->StudentsReportCards->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $this->StudentsReportCards->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $this->StudentsReportCards->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                    $this->StudentsReportCards->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
                    $this->StudentsReportCards->aliasField('institution_class_id = ') . $this->aliasField('institution_class_id')
                ]
            )
            ->leftJoin([$this->ReportCardEmailProcesses->alias() => $this->ReportCardEmailProcesses->table()],
                [
                    $this->ReportCardEmailProcesses->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $this->ReportCardEmailProcesses->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $this->ReportCardEmailProcesses->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                    $this->ReportCardEmailProcesses->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
                    $this->ReportCardEmailProcesses->aliasField('institution_class_id = ') . $this->aliasField('institution_class_id'),
                    $this->ReportCardEmailProcesses->aliasField('report_card_id = ') . $params['report_card_id']
                ]
            )
            ->autoFields(true);
    }

    public function onGetStatus(Event $event, Entity $entity)
    {
        if ($entity->has('report_card_status')) {
            $value = $this->statusOptions[$entity->report_card_status];
        } else {
            $value = $this->statusOptions[self::NEW_REPORT];
        }
        return $value;
    }

    public function onGetStartedOn(Event $event, Entity $entity)
    {
        $value = '';

        if ($entity->has('report_card_started_on')) {
            $startedOnValue = new Time($entity->report_card_started_on);
            $value = $this->formatDateTime($startedOnValue);
        }

        return $value;
    }

    public function onGetCompletedOn(Event $event, Entity $entity)
    {
        $value = '';

        if ($entity->has('report_card_completed_on')) {
            $completedOnValue = new Time($entity->report_card_completed_on);
            $value = $this->formatDateTime($completedOnValue);
        }

        return $value;
    }

    public function onGetReportQueue(Event $event, Entity $entity)
    {
        if ($entity->has('report_card_id')) {
            $reportCardId = $entity->report_card_id;
        } else if (!is_null($this->request->query('report_card_id'))) {
            $reportCardId = $this->request->query('report_card_id');
        }

        $search = [
            'report_card_id' => $reportCardId,
            'institution_class_id' => $entity->institution_class_id,
            'student_id' => $entity->student_id,
            'institution_id' => $entity->institution_id,
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

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('user')) {
            $value = $entity->user->openemis_no;
        }
        return $value;
    }

    public function onGetReportCard(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('report_card_id')) {
            $reportCardId = $entity->report_card_id;
        } else if (!is_null($this->request->query('report_card_id'))) {
            // used if student report card record has not been created yet
            $reportCardId = $this->request->query('report_card_id');
        }

        if (!empty($reportCardId)) {
            $reportCardEntity = $this->ReportCards->find()->where(['id' => $reportCardId])->first();
            if (!empty($reportCardEntity)) {
                $value = $reportCardEntity->code_name;
            }
        }
        return $value;
    }

    public function onGetEmailStatus(Event $event, Entity $entity)
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

    public function generate(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        $hasTemplate = $this->ReportCards->checkIfHasTemplate($params['report_card_id']);
        
        if ($hasTemplate) {
             $checkReportCard =  $this->checkReportCardsToBeProcess($params['institution_class_id'], $params['report_card_id']);
                
            if ($checkReportCard) {
                $this->Alert->warning('ReportCardStatuses.checkReportCardTemplatePeriod');
               return $this->controller->redirect($this->url('index'));
               die;
            }

            $this->addReportCardsToProcesses($params['institution_id'], $params['institution_class_id'], $params['report_card_id'], $params['student_id']);
            $this->triggerGenerateAllReportCardsShell($params['institution_id'], $params['institution_class_id'], $params['report_card_id'], $params['student_id']);
            $this->Alert->warning('ReportCardStatuses.generate');
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
        
        if ($hasTemplate) {
            $checkReportCard =  $this->checkReportCardsToBeProcess($params['institution_class_id'], $params['report_card_id']);
                
               if ($checkReportCard) {
                   $this->Alert->warning('ReportCardStatuses.checkReportCardTemplatePeriod');
                  return $this->controller->redirect($this->url('index'));
                  die;
               }

            $ReportCardProcesses = TableRegistry::get('ReportCard.ReportCardProcesses');
            $inProgress = $ReportCardProcesses->find()
                ->where([
                    $ReportCardProcesses->aliasField('report_card_id') => $params['report_card_id'],
                    $ReportCardProcesses->aliasField('institution_class_id') => $params['institution_class_id']
                ])
                ->count();      
                        

            if (!$inProgress) {                   
                $this->addReportCardsToProcesses($params['institution_id'], $params['institution_class_id'], $params['report_card_id']);
                $this->triggerGenerateAllReportCardsShell($params['institution_id'], $params['institution_class_id'], $params['report_card_id']);
                $this->Alert->warning('ReportCardStatuses.generateAll');
            } else {
                $this->Alert->warning('ReportCardStatuses.inProgress');
            }
        } else {
            $this->Alert->warning('ReportCardStatuses.noTemplate');
        }

        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function downloadAll(Event $event, ArrayObject $extra)
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

        if (!empty($files)) {
            $path = WWW_ROOT . 'export' . DS . 'customexcel' . DS;
            $zipName = 'ReportCards' . '_' . date('Ymd') . 'T' . date('His') . '.zip';
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
        } else {
            $event->stopPropagation();
            $this->Alert->warning('ReportCardStatuses.noFilesToDownload');
            return $this->controller->redirect($this->url('index'));
        }
    }
    /*
     *  Download pdf in bulk
     * */
    public function downloadAllPdf(Event $event, ArrayObject $extra)
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
        } else {
            $event->stopPropagation();
            $this->Alert->warning('ReportCardStatuses.noFilesToDownload');
            return $this->controller->redirect($this->url('index'));
        }
    }

    public function publish(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        $result = $this->StudentsReportCards->updateAll(['status' => self::PUBLISHED], $params);
        $this->Alert->success('ReportCardStatuses.publish');
        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function publishAll(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();

        // only publish report cards with generated status to published status
        $result = $this->StudentsReportCards->updateAll(['status' => self::PUBLISHED], [
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

    public function unpublish(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        $result = $this->StudentsReportCards->updateAll(['status' => self::NEW_REPORT], $params);
        $this->Alert->success('ReportCardStatuses.unpublish');
        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function unpublishAll(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();

        // only unpublish report cards with published status to new status
        $result = $this->StudentsReportCards->updateAll(['status' => self::NEW_REPORT], [
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

    public function email(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        $this->addReportCardsToEmailProcesses($params['institution_id'], $params['institution_class_id'], $params['report_card_id'], $params['student_id']);
        $this->triggerEmailAllReportCardsShell($params['institution_id'], $params['institution_class_id'], $params['report_card_id'], $params['student_id']);
        $this->Alert->warning('ReportCardStatuses.email');

        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function emailAll(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();

        $inProgress = $this->ReportCardEmailProcesses->find()
            ->where([
                $this->ReportCardEmailProcesses->aliasField('report_card_id') => $params['report_card_id'],
                $this->ReportCardEmailProcesses->aliasField('institution_class_id') => $params['institution_class_id'],
                $this->ReportCardEmailProcesses->aliasField('status') => $this->ReportCardEmailProcesses::SENDING
            ])
            ->count();

        if (!$inProgress) {
            $this->addReportCardsToEmailProcesses($params['institution_id'], $params['institution_class_id'], $params['report_card_id']);
            $this->triggerEmailAllReportCardsShell($params['institution_id'], $params['institution_class_id'], $params['report_card_id']);

            $this->Alert->warning('ReportCardStatuses.emailAll');
        } else {
            $this->Alert->warning('ReportCardStatuses.emailInProgress');
        }

        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    private function addReportCardsToProcesses($institutionId, $institutionClassId, $reportCardId, $studentId = null)
    {
        Log::write('debug', 'Initialize Add All Report Cards '.$reportCardId.' for Class '.$institutionClassId.' to processes ('.Time::now().')');

        $ReportCardProcesses = TableRegistry::get('ReportCard.ReportCardProcesses');
        $classStudentsTable = TableRegistry::get('Institution.InstitutionClassStudents');
        $where = [];
        $where[$classStudentsTable->aliasField('institution_class_id')] = $institutionClassId;
        if (!is_null($studentId)) {
            $where[$classStudentsTable->aliasField('student_id')] = $studentId;
        }
        $classStudents = $classStudentsTable->find()
            ->select([
                $classStudentsTable->aliasField('student_id'),
                $classStudentsTable->aliasField('institution_id'),
                $classStudentsTable->aliasField('academic_period_id'),
                $classStudentsTable->aliasField('education_grade_id'),
                $classStudentsTable->aliasField('institution_class_id')
            ])
            ->where($where)
            ->toArray();

        foreach ($classStudents as $student) {
            // Report card processes
            $idKeys = [
                'report_card_id' => $reportCardId,
                'institution_class_id' => $student->institution_class_id,
                'student_id' => $student->student_id
            ];

            $data = [
                'status' => $ReportCardProcesses::NEW_PROCESS,
                'institution_id' => $student->institution_id,
                'education_grade_id' => $student->education_grade_id,
                'academic_period_id' => $student->academic_period_id,
                'created' => date('Y-m-d H:i:s')
            ];
            $obj = array_merge($idKeys, $data);
            $newEntity = $ReportCardProcesses->newEntity($obj);
            $ReportCardProcesses->save($newEntity);
            // end

            // Report card email processes
            $emailIdKeys = $idKeys;
            if ($this->ReportCardEmailProcesses->exists($emailIdKeys)) {
                $reportCardEmailProcessEntity = $this->ReportCardEmailProcesses->find()
                    ->where($emailIdKeys)
                    ->first();
                $this->ReportCardEmailProcesses->delete($reportCardEmailProcessEntity);
            }
            // end

            // Student report card
            $recordIdKeys = [
                'report_card_id' => $reportCardId,
                'student_id' => $student->student_id,
                'institution_id' => $student->institution_id,
                'academic_period_id' => $student->academic_period_id,
                'education_grade_id' => $student->education_grade_id,
                'institution_class_id' => $student->institution_class_id,
            ];
            if ($this->StudentsReportCards->exists($recordIdKeys)) {
                $studentsReportCardEntity = $this->StudentsReportCards->find()
                    ->where($recordIdKeys)
                    ->first();

                $newData = [
                    'status' => $this->StudentsReportCards::NEW_REPORT,
                    'started_on' => NULL,
                    'completed_on' => NULL,
                    'file_name' => NULL,
                    'file_content' => NULL,
                    'institution_class_id' => $studentsReportCardEntity->institution_class_id
                ];
                $newEntity = $this->StudentsReportCards->patchEntity($studentsReportCardEntity, $newData);

                if (!$this->StudentsReportCards->save($newEntity)) {
                    Log::write('debug', 'Error Add All Report Cards '.$reportCardId.' for Class '.$institutionClassId.' to processes ('.Time::now().')');
                    Log::write('debug', $newEntity->errors());
                }
            }
            // end
        }

        Log::write('debug', 'End Add All Report Cards '.$reportCardId.' for Class '.$institutionClassId.' to processes ('.Time::now().')');
    }

    private function triggerGenerateAllReportCardsShell($institutionId, $institutionClassId, $reportCardId, $studentId = null)
    {
        $SystemProcesses = TableRegistry::get('SystemProcesses');
        $runningProcess = $SystemProcesses->getRunningProcesses($this->registryAlias());

        foreach ($runningProcess as $key => $processData) {
            $systemProcessId = $processData['id'];
            $pId = !empty($processData['process_id']) ? $processData['process_id'] : 0;
            $createdDate = $processData['created'];

            $expiryDate = clone($createdDate);
            $expiryDate->addMinutes(30);
            $today = Time::now();

            if ($expiryDate < $today) {
                $SystemProcesses->updateProcess($systemProcessId, Time::now(), $SystemProcesses::COMPLETED);
                $SystemProcesses->killProcess($pId);
            }
        }

        if (count($runningProcess) <= self::MAX_PROCESSES) {
            $processModel = $this->registryAlias();
            $passArray = [
                'institution_id' => $institutionId,
                'institution_class_id' => $institutionClassId,
                'report_card_id' => $reportCardId
            ];
            if (!is_null($studentId)) {
                $passArray['student_id'] = $studentId;
            }
            $params = json_encode($passArray);

            $args = $processModel . " " . $params;

            $cmd = ROOT . DS . 'bin' . DS . 'cake GenerateAllReportCards '.$args;
            $logs = ROOT . DS . 'logs' . DS . 'GenerateAllReportCards.log & echo $!';
            $shellCmd = $cmd . ' >> ' . $logs;
            try {
                $pid = exec($shellCmd);
                Log::write('debug', $shellCmd);
            } catch(\Exception $ex) {
                Log::write('error', __METHOD__ . ' exception when generate all report cards : '. $ex);
            }
        }
    }

    private function addReportCardsToEmailProcesses($institutionId, $institutionClassId, $reportCardId, $studentId = null)
    {
        Log::write('debug', 'Initialize Add All Report Cards '.$reportCardId.' for Class '.$institutionClassId.' to email processes ('.Time::now().')');

        $classStudentsTable = TableRegistry::get('Institution.InstitutionClassStudents');

        $where = [];
        $where[$classStudentsTable->aliasField('institution_class_id')] = $institutionClassId;
        if (!is_null($studentId)) {
            $where[$classStudentsTable->aliasField('student_id')] = $studentId;
        }
        $classStudents = $classStudentsTable->find()
            ->select([
                $classStudentsTable->aliasField('student_id'),
                $classStudentsTable->aliasField('institution_id'),
                $classStudentsTable->aliasField('academic_period_id'),
                $classStudentsTable->aliasField('education_grade_id'),
                $classStudentsTable->aliasField('institution_class_id')
            ])
            ->innerJoin([$this->StudentsReportCards->alias() => $this->StudentsReportCards->table()],
                [
                    $this->StudentsReportCards->aliasField('student_id = ') . $classStudentsTable->aliasField('student_id'),
                    $this->StudentsReportCards->aliasField('institution_id = ') . $classStudentsTable->aliasField('institution_id'),
                    $this->StudentsReportCards->aliasField('academic_period_id = ') . $classStudentsTable->aliasField('academic_period_id'),
                    $this->StudentsReportCards->aliasField('education_grade_id = ') . $classStudentsTable->aliasField('education_grade_id'),
                    $this->StudentsReportCards->aliasField('institution_class_id = ') . $classStudentsTable->aliasField('institution_class_id'),
                    $this->StudentsReportCards->aliasField('report_card_id = ') . $reportCardId,
                    $this->StudentsReportCards->aliasField('status') => self::PUBLISHED
                ]
            )
            ->where($where)
            ->toArray();

        foreach ($classStudents as $student) {
            // Report card email processes
            $idKeys = [
                'report_card_id' => $reportCardId,
                'institution_class_id' => $student->institution_class_id,
                'student_id' => $student->student_id
            ];

            $data = [
                'status' => $this->ReportCardEmailProcesses::SENDING,
                'institution_id' => $student->institution_id,
                'education_grade_id' => $student->education_grade_id,
                'academic_period_id' => $student->academic_period_id,
                'created' => date('Y-m-d H:i:s')
            ];
            $obj = array_merge($idKeys, $data);
            $newEntity = $this->ReportCardEmailProcesses->newEntity($obj);
            $this->ReportCardEmailProcesses->save($newEntity);
            // end
        }

        Log::write('debug', 'End Add All Report Cards '.$reportCardId.' for Class '.$institutionClassId.' to email processes ('.Time::now().')');
    }

    private function triggerEmailAllReportCardsShell($institutionId, $institutionClassId, $reportCardId, $studentId = null)
    {
        $SystemProcesses = TableRegistry::get('SystemProcesses');
        $runningProcess = $SystemProcesses->getRunningProcesses($this->ReportCardEmailProcesses->registryAlias());

        // to-do: add logic to purge shell which is 30 minutes old

        if (count($runningProcess) <= self::MAX_PROCESSES) {
            $name = 'EmailAllReportCards';
            $pid = '';
            $processModel = $this->ReportCardEmailProcesses->registryAlias();
            $eventName = '';
            $passArray = [
                'institution_id' => $institutionId,
                'institution_class_id' => $institutionClassId,
                'report_card_id' => $reportCardId
            ];
            if (!is_null($studentId)) {
                $name = 'EmailReportCards';
                $passArray['student_id'] = $studentId;
            }
            $params = json_encode($passArray);
            $systemProcessId = $SystemProcesses->addProcess($name, $pid, $processModel, $eventName, $params);
            $SystemProcesses->updateProcess($systemProcessId, null, $SystemProcesses::RUNNING, 0);

            $args = '';
            $args .= !is_null($systemProcessId) ? ' '.$systemProcessId : '';

            $cmd = ROOT . DS . 'bin' . DS . 'cake EmailAllReportCards'.$args;
            $logs = ROOT . DS . 'logs' . DS . 'EmailAllReportCards.log & echo $!';
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
    
    private function checkReportCardsToBeProcess($institutionClassId, $reportCardId, $academicPeriodId  = null)
    {
        $classStudentsTable = TableRegistry::get('Institution.InstitutionClassStudents');
        $where = [];
        $where[$classStudentsTable->aliasField('institution_class_id')] = $institutionClassId;
        $classStudents = $classStudentsTable->find()
            ->select([
                $classStudentsTable->aliasField('education_grade_id'),
                $classStudentsTable->aliasField('academic_period_id')
            ])
            ->where($where)
            ->first();  
        
        if (empty($classStudents)) {
            return false;
        }   
        
        $condition = [];
        $Assessments = TableRegistry::get('Assessment.Assessments');
        $entityAssessment = $Assessments->find()
                ->where([
                    $Assessments->aliasField('academic_period_id') => $classStudents->academic_period_id,
                    $Assessments->aliasField('education_grade_id') => $classStudents->education_grade_id
                ])
                ->first();

        if (!empty($entityAssessment)) {
            $condition['assessment_id'] = $entityAssessment->id;
        }
        
        $ReportCards = TableRegistry::get('ReportCard.ReportCards');
        $entityReportCards = $ReportCards->get($reportCardId);
        
        $condition['report_card_start_date'] = $entityReportCards->start_date;
        $condition['report_card_end_date'] = $entityReportCards->end_date;
        
        if ( array_key_exists('assessment_id', $condition)
            && array_key_exists('report_card_start_date', $condition) 
            && array_key_exists('report_card_end_date', $condition)
           ) {
            
            $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');
            $entityAssessmentPeriods = $AssessmentPeriods->find()
                ->where([
                    $AssessmentPeriods->aliasField('assessment_id') => $condition['assessment_id'],
                    $AssessmentPeriods->aliasField('start_date >= ') => $condition['report_card_start_date'],
                    $AssessmentPeriods->aliasField('end_date <= ') => $condition['report_card_end_date']
                ])
                ->order([$AssessmentPeriods->aliasField('start_date')]);

            if (($entityAssessmentPeriods->count() > 0)) {
                
                 return false;
            } else {
                
                 return true;
            }
            
        }
        
         return false;
        
    }
}
