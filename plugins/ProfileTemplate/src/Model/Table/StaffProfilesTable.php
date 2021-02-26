<?php
namespace ProfileTemplate\Model\Table;

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

class StaffProfilesTable extends ControllerActionTable
{
    private $statusOptions = [];
    private $reportProcessList = [];

    // for status
    CONST NEW_REPORT = 1;
    CONST IN_PROGRESS = 2;
    CONST GENERATED = 3;
    CONST PUBLISHED = 4;

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

    public function initialize(array $config)
    {
        $this->table('institution_staff');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);

        $this->addBehavior('User.AdvancedNameSearch');

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
		
		$this->StaffTemplates = TableRegistry::get('ProfileTemplate.StaffTemplates');
        $this->StaffReportCards = TableRegistry::get('Institution.StaffReportCards');
        $this->StaffReportCardProcesses = TableRegistry::get('ReportCard.StaffReportCardProcesses');
        $this->StaffReportCardEmailProcesses = TableRegistry::get('ReportCard.StaffReportCardEmailProcesses');

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
		$events['ControllerAction.Model.downloadExcel'] = 'downloadExcel';
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

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        // check if report card request is valid
        $reportCardId = $this->request->query('staff_profile_template_id');
        $institutionId = $this->request->query('institution_id');
        $academicPeriodId = $this->request->query('academic_period_id');
        
		if (!is_null($reportCardId) && $this->StaffTemplates->exists([$this->StaffTemplates->primaryKey() => $reportCardId])) {

            $indexAttr = ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false];
            $params = [
                'staff_profile_template_id' => $reportCardId,
                'staff_id' => $entity->staff_id,
                'institution_id' => $institutionId,
                'academic_period_id' => $academicPeriodId,
            ];
			
            // Download button, status must be generated or published
			if ($this->AccessControl->check(['ProfileTemplates', 'StaffProfiles', 'downloadExcel']) && $entity->has('report_card_status') && in_array($entity->report_card_status, [self::GENERATED, self::PUBLISHED])) {
                $downloadUrl = $this->setQueryString($this->url('downloadExcel'), $params);
                $buttons['download'] = [
                    'label' => '<i class="fa kd-download"></i>'.__('Download Excel'),
                    'attr' => $indexAttr,
                    'url' => $downloadUrl
                ];
				$downloadPdfUrl = $this->setQueryString($this->url('downloadPDF'), $params);
                $buttons['downloadPdf'] = [
                    'label' => '<i class="fa kd-download"></i>'.__('Download PDF'),
                    'attr' => $indexAttr,
                    'url' => $downloadPdfUrl
                ];
            }

            // Generate button, all statuses
            if ($this->AccessControl->check(['ProfileTemplates', 'StaffProfiles', 'generate'])) {
                $generateUrl = $this->setQueryString($this->url('generate'), $params);

                $reportCard = $this->StaffTemplates
                                    ->find()
                                    ->where([
                                        $this->StaffTemplates->aliasField('id') => $reportCardId])
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
                    $indexAttr['title'] = $this->getMessage('StaffProfiles.date_closed');
                    $buttons['generate'] = [
                            'label' => '<i class="fa fa-refresh"></i>'. __('Generate'),
                            'attr' => $indexAttr,
                            'url' => 'javascript:void(0)'
                            ];
                } 
            }

            // Publish button, status must be generated
            if ($this->AccessControl->check(['ProfileTemplates', 'StaffProfiles', 'publish']) && $entity->has('report_card_status') 
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
            if ($this->AccessControl->check(['ProfileTemplates', 'StaffProfiles', 'unpublish']) 
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
            if ($this->AccessControl->check(['ProfileTemplates', 'StaffProfiles', 'email']) 
                    && $entity->has('report_card_status')
                    && ( $entity->report_card_status == self::PUBLISHED 
                            || $entity->report_card_status == '16' 
                        )
               )
               {
                if (empty($entity->email_status_id) || ($entity->has('email_status_id') && $entity->email_status_id != $this->StaffReportCardEmailProcesses::SENDING)) {
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
        $this->field('staff_id', ['type' => 'integer', 'sort' => ['field' => 'Users.first_name']]);
        $this->field('profile_name');
        $this->field('status', ['sort' => ['field' => 'report_card_status']]);
        $this->field('started_on');
        $this->field('completed_on');
        $this->field('email_status');
    }
	
	private function setupTabElements() {
		$options['type'] = 'StaffTemplates';
		$tabElements = $this->getStaffTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'Profiles');
	}

	public function getStaffTabElements($options = [])
    {
        $tabElements = [];
        $tabUrl = ['plugin' => 'ProfileTemplate', 'controller' => 'ProfileTemplates'];
        $templateUrl = ['plugin' => 'ProfileTemplate', 'controller' => 'ProfileTemplates'];
        $tabElements = [
            'Profiles' => ['text' => __('Profiles')],
            'Templates' => ['text' => __('Templates')]
        ];
		
        $tabElements['Profiles']['url'] = array_merge($tabUrl, ['action' => 'StaffProfiles']);
        $tabElements['Templates']['url'] = array_merge($tabUrl, ['action' => 'Staffs']);

		return $tabElements;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('report_queue');
        $this->setFieldOrder(['openemis_no', 'staff_id', 'profile_name', 'status', 'started_on', 'completed_on', 'report_queue', 'email_status']);
		$this->setFieldVisible(['index'], ['openemis_no', 'staff_id', 'profile_name', 'status', 'started_on', 'completed_on', 'report_queue', 'email_status']);

        // SQL Query to get the current processing list for report_queue table
        $this->reportProcessList = $this->StaffReportCardProcesses
            ->find()
            ->select([
                $this->StaffReportCardProcesses->aliasField('staff_profile_template_id'),
                $this->StaffReportCardProcesses->aliasField('staff_id'),
                $this->StaffReportCardProcesses->aliasField('institution_id'),
                $this->StaffReportCardProcesses->aliasField('academic_period_id')
            ])
            ->where([
                $this->StaffReportCardProcesses->aliasField('status') => $this->StaffReportCardProcesses::NEW_PROCESS
            ])
            ->order([
                $this->StaffReportCardProcesses->aliasField('created'),
                $this->StaffReportCardProcesses->aliasField('staff_id')
            ])
            ->hydrate(false)
            ->toArray();
		$this->setupTabElements();	
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
      
        // Academic Periods filter
        $academicPeriodOptions = $AcademicPeriod->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $AcademicPeriod->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$AcademicPeriod->aliasField('id')] = $selectedAcademicPeriod;        
		//End 

        // Report Cards filter
        $reportCardOptions = [];
		$reportCardOptions = $this->StaffTemplates->find('list')
			->where([
				$this->StaffTemplates->aliasField('academic_period_id') => $selectedAcademicPeriod
			])
			->toArray();
       

        $reportCardOptions = ['-1' => '-- '.__('Select Staff Template').' --'] + $reportCardOptions;
        $selectedReportCard = !is_null($this->request->query('staff_profile_template_id')) ? $this->request->query('staff_profile_template_id') : -1;
        $this->controller->set(compact('reportCardOptions', 'selectedReportCard'));
		//End	
		
        // Institution filter
		$Institutions = TableRegistry::get('Institutions');

		$institutionOptions = [];
		$institutionOptions = $Institutions->find('list')->toArray();
       
        $institutionOptions = ['-1' => '-- '.__('Select Institution').' --'] + $institutionOptions;
        $selectedInstitution = !is_null($this->request->query('institution_id')) ? $this->request->query('institution_id') : -1;
        $this->controller->set(compact('institutionOptions', 'selectedInstitution'));
		$where[$this->aliasField('institution_id')] = $selectedInstitution;
        //End
		
        $query
            ->select([
                'staff_profile_template_id' => $this->StaffReportCards->aliasField('staff_profile_template_id'),
                'report_card_status' => $this->StaffReportCards->aliasField('status'),
                'report_card_started_on' => $this->StaffReportCards->aliasField('started_on'),
                'report_card_completed_on' => $this->StaffReportCards->aliasField('completed_on'),
                'email_status_id' => $this->StaffReportCardEmailProcesses->aliasField('status'),
                'email_error_message' => $this->StaffReportCardEmailProcesses->aliasField('error_message')
            ])
			->innerJoin([$AcademicPeriod->alias() => $AcademicPeriod->table()],
                [
					'OR' => [[
					array(
						$this->aliasField('end_date').' IS NOT NULL',
						$this->aliasField('start_date <= ') . $AcademicPeriod->aliasField('start_date'),
						$this->aliasField('end_date >= ') . $AcademicPeriod->aliasField('start_date'),
					),
					array(
						$this->aliasField('end_date').' IS NOT NULL',
						$this->aliasField('start_date <= ') . $AcademicPeriod->aliasField('end_date'),
						$this->aliasField('end_date >= ') . $AcademicPeriod->aliasField('end_date'),
					),
					array(
						$this->aliasField('end_date').' IS NOT NULL',
						$this->aliasField('start_date >= ') . $AcademicPeriod->aliasField('start_date'),
						$this->aliasField('end_date <= ') . $AcademicPeriod->aliasField('end_date'),
					)],
					array(
						$this->aliasField('end_date').' IS NULL',
						$this->aliasField('start_date <= ') . $AcademicPeriod->aliasField('end_date'),
					)
					]
                ]
            )
            ->leftJoin([$this->StaffReportCards->alias() => $this->StaffReportCards->table()],
                [
                    $this->StaffReportCards->aliasField('staff_id = ') . $this->aliasField('staff_id'),
                    $this->StaffReportCards->aliasField('institution_id = ') . $selectedInstitution,
                    $this->StaffReportCards->aliasField('academic_period_id = ') . $selectedAcademicPeriod,
                    $this->StaffReportCards->aliasField('staff_profile_template_id = ') . $selectedReportCard
                ]
            )
            ->leftJoin([$this->StaffReportCardEmailProcesses->alias() => $this->StaffReportCardEmailProcesses->table()],
                [
                    $this->StaffReportCardEmailProcesses->aliasField('staff_id = ') . $this->aliasField('staff_id'),
                    $this->StaffReportCardEmailProcesses->aliasField('institution_id = ') . $selectedInstitution,
                    $this->StaffReportCardEmailProcesses->aliasField('academic_period_id = ') . $selectedAcademicPeriod,
                    $this->StaffReportCardEmailProcesses->aliasField('staff_profile_template_id = ') . $selectedReportCard
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

        $extra['elements']['controls'] = ['name' => 'ProfileTemplate.ReportCards/controls', 'data' => [], 'options' => [], 'order' => 1];

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
        $reportCardId = $this->request->query('staff_profile_template_id');
        $institutionId = $this->request->query('institution_id');
        $academicPeriodId = $this->request->query('academic_period_id');

        if (!is_null($reportCardId) && !is_null($institutionId)) {
            $existingReportCard = $this->StaffTemplates->exists([$this->StaffTemplates->primaryKey() => $reportCardId]);

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
                    'staff_profile_template_id' => $reportCardId
                ];
				
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
                if (!is_null($this->request->query('staff_profile_template_id'))) {
                    $reportCardId = $this->request->query('staff_profile_template_id');
                }

                $ReportCardsData = $this->StaffTemplates
                                    ->find()
                                    ->where([
                                        $this->StaffTemplates->aliasField('id') => $reportCardId])
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
                    $generateButton['attr']['title'] .= __('<br>'.$this->getMessage('StaffProfiles.date_closed'));
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
        $searchableFields[] = 'staff_id';
        $searchableFields[] = 'openemis_no';
    }

	public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder(['academic_period_id', 'openemis_no', 'staff_id', 'profile_name', 'status', 'started_on', 'completed_on', 'report_queue', 'email_status']);
		$this->setFieldVisible(['view'], ['academic_period_id', 'openemis_no', 'staff_id', 'profile_name', 'status', 'started_on', 'completed_on', 'report_queue', 'email_status']);
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $params = $this->request->query;

        $query
            ->select([
                'staff_profile_template_id' => $this->StaffReportCards->aliasField('staff_profile_template_id'),
                'report_card_status' => $this->StaffReportCards->aliasField('status'),
                'report_card_started_on' => $this->StaffReportCards->aliasField('started_on'),
                'report_card_completed_on' => $this->StaffReportCards->aliasField('completed_on'),
                'email_status_id' => $this->StaffReportCardEmailProcesses->aliasField('status'),
                'email_error_message' => $this->StaffReportCardEmailProcesses->aliasField('error_message')
            ])
            ->leftJoin([$this->StaffReportCards->alias() => $this->StaffReportCards->table()],
                [
                    $this->StaffReportCards->aliasField('staff_id = ') . $this->aliasField('staff_id'),
                    $this->StaffReportCards->aliasField('institution_id = ') . $params['institution_id'],
                    $this->StaffReportCards->aliasField('academic_period_id = ') . $params['academic_period_id'],
                ]
            )
            ->leftJoin([$this->StaffReportCardEmailProcesses->alias() => $this->StaffReportCardEmailProcesses->table()],
                [
                    $this->StaffReportCardEmailProcesses->aliasField('staff_id = ') . $this->aliasField('staff_id'),
                    $this->StaffReportCardEmailProcesses->aliasField('institution_id = ') . $params['institution_id'],
                    $this->StaffReportCardEmailProcesses->aliasField('academic_period_id = ') . $params['academic_period_id'],
                    $this->StaffReportCardEmailProcesses->aliasField('staff_profile_template_id = ') . $params['staff_profile_template_id']
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
        if ($entity->has('staff_profile_template_id')) {
            $reportCardId = $entity->staff_profile_template_id;
        } else if (!is_null($this->request->query('staff_profile_template_id'))) {
            $reportCardId = $this->request->query('staff_profile_template_id');
        }
		$academicPeriodId = $this->request->query('academic_period_id');

        $search = [
            'staff_profile_template_id' => $reportCardId,
            'staff_id' => $entity->staff_id,
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

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('user')) {
            $value = $entity->user->openemis_no;
        }
        return $value;
    }

    public function onGetProfileName(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('staff_profile_template_id')) {
            $reportCardId = $entity->staff_profile_template_id;
        } else if (!is_null($this->request->query('staff_profile_template_id'))) {
            // used if student report card record has not been created yet
            $reportCardId = $this->request->query('staff_profile_template_id');
        }

        if (!empty($reportCardId)) {
            $reportCardEntity = $this->StaffTemplates->find()->where(['id' => $reportCardId])->first();
            if (!empty($reportCardEntity)) {
                $value = $reportCardEntity->name;
            }
        }
        return $value;
    }

    public function onGetEmailStatus(Event $event, Entity $entity)
    {
        $emailStatuses = $this->StaffReportCardEmailProcesses->getEmailStatus();
        $value = '<i class="fa fa-minus"></i>';

        if ($entity->has('email_status_id')) {
            $value = $emailStatuses[$entity->email_status_id];

            if ($entity->email_status_id == $this->StaffReportCardEmailProcesses::ERROR && $entity->has('email_error_message')) {
                $value .= '&nbsp&nbsp;<i class="fa fa-exclamation-circle fa-lg table-tooltip icon-red" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="' . $entity->email_error_message . '"></i>';
            }
        }

        return $value;
    }
	
	public function downloadExcel(Event $event, ArrayObject $extra)
    {
		$model = $this->StaffReportCards;
        $ids = $this->getQueryString();
		
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
	
	public function downloadPDF(Event $event, ArrayObject $extra)
    {
		$model = $this->StaffReportCards;
        $ids = $this->getQueryString();
		
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

    public function generate(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        $hasTemplate = $this->StaffTemplates->checkIfHasTemplate($params['staff_profile_template_id']);
        
        if ($hasTemplate) {
            $this->addReportCardsToProcesses($params['institution_id'], $params['academic_period_id'], $params['staff_profile_template_id'], $params['staff_id']);
            $this->triggerGenerateAllReportCardsShell($params['institution_id'], $params['academic_period_id'], $params['staff_profile_template_id'], $params['staff_id']);
            $this->Alert->warning('StaffProfiles.generate');
        } else {
            $url = $this->url('index');
            $this->Alert->warning('StaffProfiles.noTemplate');
        }

        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function generateAll(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        $hasTemplate = $this->StaffTemplates->checkIfHasTemplate($params['staff_profile_template_id']);
        
        if ($hasTemplate) {
            $StaffReportCardProcesses = TableRegistry::get('ReportCard.StaffReportCardProcesses');
            $inProgress = $StaffReportCardProcesses->find()
                ->where([
                    $StaffReportCardProcesses->aliasField('staff_profile_template_id') => $params['staff_profile_template_id'],
                    $StaffReportCardProcesses->aliasField('staff_id') => $params['staff_id'],
                    $StaffReportCardProcesses->aliasField('academic_period_id') => $params['academic_period_id'],
                    $StaffReportCardProcesses->aliasField('institution_id') => $params['institution_id'],
                ])
                ->count();      
                        

            if (!$inProgress) {                   
                $this->addReportCardsToProcesses($params['institution_id'], $params['academic_period_id'], $params['staff_profile_template_id']);
				$this->triggerGenerateAllReportCardsShell($params['institution_id'], $params['academic_period_id'], $params['staff_profile_template_id']);
				$this->Alert->warning('StaffProfiles.generateAll');
            } else {
                $this->Alert->warning('StaffProfiles.inProgress');
            }
        } else {
            $this->Alert->warning('StaffProfiles.noTemplate');
        }

        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }
	
	public function downloadAllPdf(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();

        // only download report cards with generated or published status
        $statusArray = [self::GENERATED, self::PUBLISHED];

        $files = $this->StaffReportCards->find()
            ->contain(['StaffTemplates'])
            ->where([
                $this->StaffReportCards->aliasField('institution_id') => $params['institution_id'],
                $this->StaffReportCards->aliasField('academic_period_id') => $params['academic_period_id'],
                $this->StaffReportCards->aliasField('staff_profile_template_id') => $params['staff_profile_template_id'],
                $this->StaffReportCards->aliasField('status IN ') => $statusArray,
                $this->StaffReportCards->aliasField('file_name IS NOT NULL'),
                $this->StaffReportCards->aliasField('file_content IS NOT NULL')
            ])
            ->toArray();
            
        if (!empty($files)) {
            $path = WWW_ROOT . 'export' . DS . 'customexcel' . DS;
            $zipName = 'StaffReportCards' . '_' . date('Ymd') . 'T' . date('His') . '.zip';
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
            $this->Alert->warning('StaffProfiles.noFilesToDownload');
            return $this->controller->redirect($this->url('index'));
        }
    }
	
    public function downloadAll(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();

        // only download report cards with generated or published status
        $statusArray = [self::GENERATED, self::PUBLISHED];

        $files = $this->StaffReportCards->find()
            ->contain(['StaffTemplates'])
            ->where([
                $this->StaffReportCards->aliasField('institution_id') => $params['institution_id'],
                $this->StaffReportCards->aliasField('academic_period_id') => $params['academic_period_id'],
                $this->StaffReportCards->aliasField('staff_profile_template_id') => $params['staff_profile_template_id'],
                $this->StaffReportCards->aliasField('status IN ') => $statusArray,
                $this->StaffReportCards->aliasField('file_name IS NOT NULL'),
                $this->StaffReportCards->aliasField('file_content IS NOT NULL')
            ])
            ->toArray();
        if (!empty($files)) {
            $path = WWW_ROOT . 'export' . DS . 'customexcel' . DS;
            $zipName = 'StaffReportCards' . '_' . date('Ymd') . 'T' . date('His') . '.zip';
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
            $this->Alert->warning('StaffProfiles.noFilesToDownload');
            return $this->controller->redirect($this->url('index'));
        }
    }

    public function publish(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        $result = $this->StaffReportCards->updateAll(['status' => self::PUBLISHED], $params);
        $this->Alert->success('StaffProfiles.publish');
        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function publishAll(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();

        // only publish report cards with generated status to published status
        $result = $this->StaffReportCards->updateAll(['status' => self::PUBLISHED], [
            $params,
            'status' => self::GENERATED
        ]);

        if ($result) {
            $this->Alert->success('StaffProfiles.publishAll');
        } else {
            $this->Alert->warning('StaffProfiles.noFilesToPublish');
        }

        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function unpublish(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        $result = $this->StaffReportCards->updateAll(['status' => self::NEW_REPORT], $params);
        $this->Alert->success('StaffProfiles.unpublish');
        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function unpublishAll(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();

        // only unpublish report cards with published status to new status
        $result = $this->StaffReportCards->updateAll(['status' => self::NEW_REPORT], [
            $params,
            'status' => self::PUBLISHED
        ]);

        if ($result) {
            $this->Alert->success('StaffProfiles.unpublishAll');
        } else {
            $this->Alert->warning('StaffProfiles.noFilesToUnpublish');
        }

        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function email(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        $this->addReportCardsToEmailProcesses($params['institution_id'], $params['academic_period_id'], $params['staff_profile_template_id'], $params['staff_id']);
        $this->triggerEmailAllReportCardsShell($params['institution_id'], $params['academic_period_id'], $params['staff_profile_template_id'], $params['staff_id']);
        $this->Alert->warning('StaffProfiles.email');

        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function emailAll(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();

        $inProgress = $this->StaffReportCardEmailProcesses->find()
            ->where([
                $this->StaffReportCardEmailProcesses->aliasField('staff_profile_template_id') => $params['staff_profile_template_id'],
                $this->StaffReportCardEmailProcesses->aliasField('institution_id') => $params['institution_id'],
                $this->StaffReportCardEmailProcesses->aliasField('academic_period_id') => $params['academic_period_id'],
                $this->StaffReportCardEmailProcesses->aliasField('status') => $this->StaffReportCardEmailProcesses::SENDING
            ])
            ->count();

        if (!$inProgress) {
            $this->addReportCardsToEmailProcesses($params['institution_id'], $params['academic_period_id'], $params['staff_profile_template_id']);
            $this->triggerEmailAllReportCardsShell($params['institution_id'], $params['academic_period_id'], $params['staff_profile_template_id']);

            $this->Alert->warning('StaffProfiles.emailAll');
        } else {
            $this->Alert->warning('StaffProfiles.emailInProgress');
        }

        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    private function addReportCardsToProcesses($institutionId, $academicPeriodId, $reportCardId, $staffId = null)
    {
        Log::write('debug', 'Initialize Add All Staff Profile Report Cards '.$reportCardId.' for Institution '.$institutionId.' to processes ('.Time::now().')');

        $StaffReportCardProcesses = TableRegistry::get('ReportCard.StaffReportCardProcesses');
        $staffTable = TableRegistry::get('institution_staff');
        $where = [];
        $where[$staffTable->aliasField('institution_id')] = $institutionId;
        if (!is_null($staffId)) {
            $where[$staffTable->aliasField('staff_id')] = $staffId;
        }
        $institutionStaffs = $staffTable->find()
            ->select([
                $staffTable->aliasField('staff_id'),
                $staffTable->aliasField('institution_id'),
            ])
            ->where($where)
            ->toArray();

        foreach ($institutionStaffs as $staff) {
            // Report card processes
            $idKeys = [
                'staff_profile_template_id' => $reportCardId,
                'institution_id' => $staff->institution_id,
                'staff_id' => $staff->staff_id
            ];

            $data = [
                'status' => $StaffReportCardProcesses::NEW_PROCESS,
                'academic_period_id' => $academicPeriodId,
                'created' => date('Y-m-d H:i:s')
            ];
            $obj = array_merge($idKeys, $data);
            $newEntity = $StaffReportCardProcesses->newEntity($obj);
            $StaffReportCardProcesses->save($newEntity);
            // end

            // Report card email processes
            $emailIdKeys = $idKeys;
            if ($this->StaffReportCardEmailProcesses->exists($emailIdKeys)) {
                $reportCardEmailProcessEntity = $this->StaffReportCardEmailProcesses->find()
                    ->where($emailIdKeys)
                    ->first();
                $this->StaffReportCardEmailProcesses->delete($reportCardEmailProcessEntity);
            }
            // end

            // staff report card
            $recordIdKeys = [
                'staff_profile_template_id' => $reportCardId,
                'staff_id' => $staff->staff_id,
                'institution_id' => $staff->institution_id,
                'academic_period_id' => $academicPeriodId,
            ];
            if ($this->StaffReportCards->exists($recordIdKeys)) {
                $staffsReportCardEntity = $this->StaffReportCards->find()
                    ->where($recordIdKeys)
                    ->first();

                $newData = [
                    'status' => $this->StaffReportCards::NEW_REPORT,
                    'started_on' => NULL,
                    'completed_on' => NULL,
                    'file_name' => NULL,
                    'file_content' => NULL,
                    'staff_id' => $staff->staff_id
                ];
				
                $newEntity = $this->StaffReportCards->patchEntity($staffsReportCardEntity, $newData);

                if (!$this->StaffReportCards->save($newEntity)) {
                    Log::write('debug', 'Error Add All staff profile Report Cards '.$reportCardId.' for Class '.$institutionClassId.' to processes ('.Time::now().')');
                    Log::write('debug', $newEntity->errors());
                }
            }
            // end
        }

        Log::write('debug', 'End Add All staff profile Report Cards '.$reportCardId.' for Institution '.$institutionId.' to processes ('.Time::now().')');
    }

    private function triggerGenerateAllReportCardsShell($institutionId, $academicPeriodId, $reportCardId, $staffId = null)
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
                'staff_profile_template_id' => $reportCardId
            ];
            if (!is_null($staffId)) {
                $passArray['staff_id'] = $staffId;
            }
            $params = json_encode($passArray);

            $args = $processModel . " " . $params;

            $cmd = ROOT . DS . 'bin' . DS . 'cake GenerateAllStaffReportCards '.$args;
            $logs = ROOT . DS . 'logs' . DS . 'GenerateAllStaffReportCards.log & echo $!';
            $shellCmd = $cmd . ' >> ' . $logs;
            try {
                $pid = exec($shellCmd);
                Log::write('debug', $shellCmd);
            } catch(\Exception $ex) {
                Log::write('error', __METHOD__ . ' exception when generate all report cards : '. $ex);
            }
        }
    }

    private function addReportCardsToEmailProcesses($institutionId, $academicPeriodId, $reportCardId, $staffId = null)
    {
        Log::write('debug', 'Initialize Add All Staff Report Cards '.$reportCardId.' for Class '.$institutionClassId.' to email processes ('.Time::now().')');
		
		$staffTable = TableRegistry::get('institution_staff');
        $where = [];
        $where[$staffTable->aliasField('institution_id')] = $institutionId;
        if (!is_null($staffId)) {
            $where[$staffTable->aliasField('staff_id')] = $staffId;
        }
        $institutionStaffs = $staffTable->find()
            ->select([
                $staffTable->aliasField('staff_id'),
                $staffTable->aliasField('institution_id'),
            ])
			->innerJoin([$this->StaffReportCards->alias() => $this->StaffReportCards->table()],
                [
                    $this->StaffReportCards->aliasField('staff_id = ') . $staffTable->aliasField('staff_id'),
                    $this->StaffReportCards->aliasField('institution_id = ') . $staffTable->aliasField('institution_id'),
                    $this->StaffReportCards->aliasField('academic_period_id = ') . $academicPeriodId,
                    $this->StaffReportCards->aliasField('staff_profile_template_id = ') . $reportCardId,
                    $this->StaffReportCards->aliasField('status') => self::PUBLISHED
                ]
            )
            ->where($where)
            ->toArray();

        foreach ($institutionStaffs as $staff) {
            // Report card processes
            $idKeys = [
                'staff_profile_template_id' => $reportCardId,
                'institution_id' => $staff->institution_id,
                'staff_id' => $staff->staff_id
            ];

            $data = [
                'status' => $this->StaffReportCardEmailProcesses::SENDING,
                'academic_period_id' => $academicPeriodId,
                'created' => date('Y-m-d H:i:s')
            ];
            $obj = array_merge($idKeys, $data);
            $newEntity = $this->StaffReportCardEmailProcesses->newEntity($obj);
            $this->StaffReportCardEmailProcesses->save($newEntity);
            // end
        }

        Log::write('debug', 'End Add All Report Cards '.$reportCardId.' for Institution '.$institutionId.' to email processes ('.Time::now().')');
    }

    private function triggerEmailAllReportCardsShell($institutionId, $institutionClassId, $reportCardId, $staffId = null)
    {
        $SystemProcesses = TableRegistry::get('SystemProcesses');
        $runningProcess = $SystemProcesses->getRunningProcesses($this->StaffReportCardEmailProcesses->registryAlias());

        // to-do: add logic to purge shell which is 30 minutes old

        if (count($runningProcess) <= self::MAX_PROCESSES) {
            $name = 'EmailAllStaffReportCards';
            $pid = '';
            $processModel = $this->StaffReportCardEmailProcesses->registryAlias();
            $eventName = '';
            $passArray = [
                'institution_id' => $institutionId,
                'staff_profile_template_id' => $reportCardId
            ];
            if (!is_null($staffId)) {
                $name = 'EmailAllStaffReportCards';
                $passArray['staff_id'] = $staffId;
            }
            $params = json_encode($passArray);
            $systemProcessId = $SystemProcesses->addProcess($name, $pid, $processModel, $eventName, $params);
            $SystemProcesses->updateProcess($systemProcessId, null, $SystemProcesses::RUNNING, 0);

            $args = '';
            $args .= !is_null($systemProcessId) ? ' '.$systemProcessId : '';

            $cmd = ROOT . DS . 'bin' . DS . 'cake EmailAllStaffReportCards'.$args;
            $logs = ROOT . DS . 'logs' . DS . 'EmailAllStaffReportCards.log & echo $!';
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
}
