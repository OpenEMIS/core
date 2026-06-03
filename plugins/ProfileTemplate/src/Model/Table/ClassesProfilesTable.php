<?php
namespace ProfileTemplate\Model\Table;

use ArrayObject;
use ZipArchive;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\ResultSet;
use Cake\Event\EventInterface;
use Cake\I18n\Time;
use Cake\Log\Log;
use Cake\Http\ServerRequest;

use App\Model\Table\ControllerActionTable;
/**
 *
 * This class is used to generate report from profile tabs
 * We can generate/download report and trigger event from this class
 * @author Anubhav Jain <anubhav.jain@mail.valuecoders.com>
 *
 */
class ClassesProfilesTable extends ControllerActionTable
{
    private $statusOptions = [];
    private $reportProcessList = [];
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
        ini_set('memory_limit', '2G');
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
        $areaId = $this->request->getQuery('area_id');//POCOR-7382
        $institution_id = $this->request->getQuery('institution_id');//POCOR-7382
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
				'institution_id' => $entity->id ?? $this->getQueryString('institution_id'),//POCOR-8393
				'academic_period_id' => $academicPeriodId,
                'institution_class_id' => $entity->institution_class_id,
                'area_id' => $areaId//POCOR-7382
            ];

            // Download button, status must be generated or published
            if ($this->AccessControl->check(['Profiles', 'ClassesProfiles', 'downloadExcel']) && $entity->has('report_card_status') && in_array($entity->report_card_status, [self::GENERATED, self::PUBLISHED])) {
                //START:POCOR-6667
                $viewPdfUrl = $this->setQueryString($this->url('viewPDF'), $params);
                $buttons['viewPdf'] = [
                    'label' => '<i class="fa fa-eye"></i>'.__('View PDF'),
                    'attr' => $indexAttr,
                    'url' => $viewPdfUrl
                ];
                //END:POCOR-6667
				$downloadPdfUrl = $this->setQueryString($this->url('downloadPDF'), $params);
                $buttons['downloadPdf'] = [
                    'label' => '<i class="fa kd-download"></i>'.__('Download PDF'),
                    'attr' => $indexAttr,
                    'url' => $downloadPdfUrl
                ];
                $downloadUrl = $this->setQueryString($this->url('downloadExcel'), $params);
                $buttons['download'] = [
                    'label' => '<i class="fa kd-download"></i>'.__('Download Excel'),
                    'attr' => $indexAttr,
                    'url' => $downloadUrl
                ];
            }
            // Generate button, all statuses
            if ($this->AccessControl->check(['ProfileTemplates', 'ClassesProfiles', 'generate'])) {
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
            if ($this->AccessControl->check(['ProfileTemplates', 'ClassesProfiles', 'publish']) && $entity->has('report_card_status')
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
            if ($this->AccessControl->check(['ProfileTemplates', 'ClassesProfiles', 'unpublish'])
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
        $this->fields['institution_class_id']['visible'] = false;
        $this->fields['next_institution_class_id']['visible'] = false;
        $this->fields['academic_period_id']['visible'] = false;
        $this->fields['student_status_id']['visible'] = false;

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Classes','Profiles');
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
		// End POCOR-5188
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
            'Profiles' => ['text' => __('Profile')],
            'Templates' => ['text' => __('Templates')]
        ];

        $tabElements['Profiles']['url'] = array_merge($tabUrl, ['action' => 'ClassProfiles']);
        $tabElements['Templates']['url'] = array_merge($tabUrl, ['action' => 'Classes']);

		return $tabElements;
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('report_queue');
        $this->setFieldOrder(['class_name', 'institution_name', 'profile_name', 'status', 'started_on', 'completed_on', 'report_queue']);
		$this->setFieldVisible(['index'], ['class_name', 'institution_name', 'profile_name', 'status', 'started_on', 'completed_on', 'report_queue']);

        // SQL Query to get the current processing list for report_queue table
        $this->reportProcessList = $this->ClassProfileProcesses
            ->find()
            ->select([
                $this->ClassProfileProcesses->aliasField('class_profile_template_id'),
                $this->ClassProfileProcesses->aliasField('institution_id'),
                $this->ClassProfileProcesses->aliasField('academic_period_id'),
                $this->ClassProfileProcesses->aliasField('institution_class_id')
            ])
            // ->where([
            //     $this->ClassProfileProcesses->aliasField('status') => $this->ClassProfileProcesses::NEW_PROCESS
            // ])//POCOR-8393
            ->order([
                $this->ClassProfileProcesses->aliasField('created'),
            ])
            ->enableHydration(false)
            ->toArray();
            // echo "<pre>";print_r($this->reportProcessList);die;
		$this->setupTabElements();
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $serverRequest = $this->request;
		$AcademicPeriod = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        // Academic Periods filter
        $academicPeriodOptions = $AcademicPeriod->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($serverRequest->getQuery('academic_period_id')) ? $this->request->getQuery('academic_period_id') : $AcademicPeriod->getCurrent();
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
        $selectedReportCard = !is_null($serverRequest->getQuery('class_profile_template_id')) ? $serverRequest->getQuery('class_profile_template_id') : -1;
        $this->controller->set(compact('reportCardOptions', 'selectedReportCard'));
		//End
	    // Area Level filter
        $AreaLevel = TableRegistry::getTableLocator()->get('Area.AreaLevels');
        $areaLevelOptions = [];
        $areaLevelOptions = $AreaLevel->find('list')->toArray();
        $areaLevelOptions = ['-1' => '-- '.__('Select Area Level').' --'] + $areaLevelOptions;
        $selectedAreaLevel = !is_null($serverRequest->getQuery('area_level_id')) ? $serverRequest->getQuery('area_level_id') : -1;
        $this->controller->set(compact('areaLevelOptions', 'selectedAreaLevel'));
        //End
        // Area filter
        $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
        $areaOptions = [];
        if($selectedAreaLevel != -1){
            $areaOptions = $Areas->find('list')
                            ->where([
                                $Areas->aliasField('area_level_id') => $selectedAreaLevel
                            ])
                             ->toArray();
        } else{
            $areaOptions = $Areas->find('list')
             ->toArray();
        }
        $areaOptions = ['-1' => __('--Select Area--')] + $areaOptions;
        $selectedArea = !is_null($serverRequest->getQuery('area_id')) ? $serverRequest->getQuery('area_id') : -1;
        $this->controller->set(compact('areaOptions', 'selectedArea'));
        //End
        foreach($areaOptions AS $key => $areaOptionsData){
            $areaKey[$key] = $key;
        }
        // Institution filter
        $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $institutionOptions = [];
        if($selectedArea == -1){
            $institutionOptions = $Institutions->find('list')
                                ->where([
                                    $Institutions->aliasField('institution_status_id !=') => 2 //POCOR-6329
                                ])
                                ->order([$Institutions->aliasField('name') =>'ASC']) //POCOR-7641
                                ->toArray();
        }else{
            //POCOR-6822 Anubhav's code starts
            $areaIds = [];
            $allgetArea = $this->getChildren($selectedArea, $areaIds);
            $selectedArea1[]= $selectedArea;
            if(!empty($allgetArea)){
                $allselectedAreas = array_merge($selectedArea1, $allgetArea);
            }else{
                $allselectedAreas = $selectedArea1;
            }//POCOR-6822 Anubhav's code ends

            $institutionOptions = $Institutions->find('list')
                                ->where([ $Institutions->aliasField('area_id IN') => $allselectedAreas,
                                    $Institutions->aliasField('institution_status_id !=') => 2 //POCOR-6329
                                ])
                                ->order([$Institutions->aliasField('name') =>'ASC']) //POCOR-7641
                                ->toArray();
        }

        if(!empty($institutionOptions)){
            foreach($institutionOptions AS $institutionOptionsDataKey => $institutionOptionsData){
                $institutionOptionsKey[$institutionOptionsDataKey] = $institutionOptionsDataKey;
            }
        }

        $institutionOptions = ['-1' => '-- '.__('All Institution').' --'] + $institutionOptions;
        $selectedInstitution = !is_null($serverRequest->getQuery('institution_id')) ? $serverRequest->getQuery('institution_id') : -1;
        $this->controller->set(compact('institutionOptions', 'selectedInstitution'));

        if($selectedInstitution != -1){
            $where[$this->aliasField('id')] = $selectedInstitution;
        }
        if(!empty($institutionOptionsKey)){
            $where[$this->aliasField('id IN ')] = $institutionOptionsKey;
        }
        //End
        $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        $query
            ->select([
                'institution_class_id' => $InstitutionClasses->aliasField('id'),
                'class_name' => $InstitutionClasses->aliasField('name'),
                'institution_name' => $this->aliasField('name'),
                'institution_id' => $InstitutionClasses->aliasField('institution_id'),//POCOR-8393
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
            // ->autoFields(true)
            ->order([
                $this->aliasField('name'),
            ])
            ->all();

        $extra['elements']['controls'] = ['name' => 'Institution.ReportCards/Classcontrols', 'data' => [], 'options' => [], 'order' => 1];

        // sort
        $sortList = ['report_card_status', 'class_name', 'institution_name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;
    }

    public function getChildren($id, $idArray) {
        $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
        $result = $Areas->find()
                            ->where([
                                $Areas->aliasField('parent_id') => $id
                            ])
                             ->toArray();
        foreach ($result as $key => $value) {
            $idArray[] = $value['id'];
           $idArray = $this->getChildren($value['id'], $idArray);
        }
        return $idArray;
    }

    public function indexAfterAction(EventInterface $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $serverRequest = $this->request;
        $reportCardId = $serverRequest->getQuery('class_profile_template_id');
        $academicPeriodId = $serverRequest->getQuery('academic_period_id');
        $institutionId = $serverRequest->getQuery('institution_id');

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
        $params = $this->request->query;
		if(!empty($params['class_profile_template_id'])) {
            $InstitutionClasses = TableRegistry::getTableLocator()->get('institution_classes');
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
                ->innerJoin([$InstitutionClasses->alias() => $InstitutionClasses->table()],
                    [
                        $InstitutionClasses->aliasField('institution_id = ') . $this->aliasField('id'),
                        $InstitutionClasses->aliasField('academic_period_id = ') . $selectedAcademicPeriod,
                    ]
                )
				->leftJoin([$this->ClassProfiles->alias() => $this->ClassProfiles->table()],
					[
						$this->ClassProfiles->aliasField('institution_id = ') . $this->aliasField('id'),
						$this->ClassProfiles->aliasField('academic_period_id = ') . $params['academic_period_id'],
						$this->ClassProfiles->aliasField('class_profile_template_id = ') . $params['class_profile_template_id']
					]
				)
				->autoFields(true);
		} else {
			$query
				->select([
					'institution_name' => $this->aliasField('name'),
				])
				->autoFields(true);
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
            $startedOnValue = new Time($entity->report_card_started_on);
            $value = $startedOnValue->format('Y-m-d H:i:s'); //POCOR-9593: direct format — formatDateTime() not available on this branch
        }
        return $value;
    }

    public function onGetCompletedOn(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('report_card_completed_on') && !empty($entity->report_card_completed_on)) {
            $completedOnValue = new Time($entity->report_card_completed_on);
            $value = $completedOnValue->format('Y-m-d H:i:s'); //POCOR-9593: direct format — formatDateTime() not available on this branch
        }
        return $value;
    }

    public function onGetReportQueue(EventInterface $event, Entity $entity)
    {
        if ($entity->has('class_profile_template_id')) {
            $reportCardId = $entity->class_profile_template_id;
        } else if (!is_null($this->request->getQuery('class_profile_template_id'))) {
            $reportCardId = $this->request->getQuery('class_profile_template_id');
        }

		$academicPeriodId = $this->request->getQuery('academic_period_id');
        $institution_id = $entity->institution_id ?? $this->getQueryString('institution_id');//POCOR-8393
        $search = [
            'class_profile_template_id' => $reportCardId,
            'institution_class_id' => $entity->institution_class_id,
            'institution_id' => $institution_id,
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
        } else if (!is_null($this->request->getQuery['class_profile_template_id'])) {
            // used if student report card record has not been created yet
            $reportCardId = $this->request->getQuery['class_profile_template_id'];
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
        $clean = array_filter(
            $ids,
            fn($v) => !is_null($v)
        );
// keep only keys that exist as columns in the table schema
        $fields = $model->getSchema()->columns();
        $clean  = array_intersect_key($clean, array_flip($fields));
        if ($model->exists($clean)) {
            $data = $model->find()
                ->where($clean)
                ->first();   // similar to get(), returns first entity or null
            if (!empty($data)) {
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
        }
        exit();
    }

	public function downloadPDF(EventInterface $event, ArrayObject $extra)
    {
		$model = $this->ClassProfiles;
        $ids = $this->getQueryString();
        $clean = array_filter(
            $ids,
            fn($v) => !is_null($v)
        );
        $fields = $model->getSchema()->columns();
        $clean  = array_intersect_key($clean, array_flip($fields));
        if ($model->exists($clean)) {
            $data = $model->find()
                ->where($clean)
                ->first();   // similar to get(), returns first entity or null
            if (!empty($data)) {
                $fileName = $data->file_name;
                $fileNameData = explode(".", $fileName);
                $fileName = $fileNameData[0] . '.pdf';
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
        $clean = array_filter(
            $ids,
            fn($v) => !is_null($v)
        );
        $fields = $model->getSchema()->columns();
        $clean  = array_intersect_key($clean, array_flip($fields));
        if ($model->exists($clean)) {
            $data = $model->find()
                ->where($clean)
                ->first();   // similar to get(), returns first entity or null
            if(!empty($data)){
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
            header('Content-Disposition: inline; filename="' . $filename . '"');

            echo $file;
            }
        }
        exit();
    }

    public function generate(EventInterface $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        $hasTemplate = $this->ReportCards->checkIfHasTemplate($params['class_profile_template_id']);

        if ($hasTemplate) {
            $this->addReportCardsToProcesses($params['academic_period_id'], $params['class_profile_template_id'], $params['institution_id'], $params['institution_class_id']);
            $this->triggerGenerateAllReportCardsShell($params['academic_period_id'], $params['class_profile_template_id'], $params['institution_id'], $params['institution_class_id'], $params['area_id']);//POCOR-7382 add area_id
            $this->Alert->warning('ReportCardStatuses.generate');
        } else {
            $url = $this->url('index');
            $this->Alert->warning('ReportCardStatuses.noTemplate');
        }

        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function generateAll(EventInterface $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        $hasTemplate = $this->ReportCards->checkIfHasTemplate($params['class_profile_template_id']);

        if ($hasTemplate) {
            $InstitutionReportCardProcesses = TableRegistry::getTableLocator()->get('ReportCard.ClassProfileProcesses');
            //POCOR-8393 start
            $query = $InstitutionReportCardProcesses->find()
                ->where([
                    $InstitutionReportCardProcesses->aliasField('class_profile_template_id') => $params['class_profile_template_id'],
                    // $InstitutionReportCardProcesses->aliasField('institution_id') => $params['institution_id'],
                    // $InstitutionReportCardProcesses->aliasField('institution_class_id') => $params['institution_class_id'],
                    $InstitutionReportCardProcesses->aliasField('academic_period_id') => $params['academic_period_id']
                ]);
            if (isset($params['institution_id'])) {
                    $query->where([$InstitutionReportCardProcesses->aliasField('institution_id') => $params['institution_id']]);
            }
            $inProgress = $query->count();
            //POCOR-8393 end
            if (!$inProgress) {
                $this->addReportCardsToProcesses($params['academic_period_id'], $params['class_profile_template_id'], $params['institution_id'], $params['institution_class_id']);
				$this->triggerGenerateAllReportCardsShell($params['academic_period_id'], $params['class_profile_template_id'], $params['institution_id'], $params['institution_class_id'], $params['area_id']);//POCOR-7382 add area_id
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
            exit();
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
            exit();
        } else {
            $event->stopPropagation();
            $this->Alert->warning('ReportCardStatuses.noFilesToDownload');
            return $this->controller->redirect($this->url('index'));
        }
    }

    public function publish(EventInterface $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        unset($params['area_id']); //POCOR-7663
        $result = $this->ClassProfiles->updateAll(['status' => self::PUBLISHED], $params);
        $this->Alert->success('ReportCardStatuses.publish');
        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function publishAll(EventInterface $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        if(empty($params['institution_id'])){
            unset($params['institution_id']); //POCOR-7663
        }

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
        Log::write('debug', 'Initialize Add All Class Report Cards '.$reportCardId.' for Institution '.$institutionId.' to processes ('.Time::now().')');

        $ClassProfileProcesses = TableRegistry::getTableLocator()->get('ReportCard.ClassProfileProcesses');
        $InstitutionTable = TableRegistry::getTableLocator()->get('Institution.Institutions');
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
            ->innerJoin([$InstitutionClasses->getAlias() => $InstitutionClasses->getTable()],
                [
                    $InstitutionClasses->aliasField('institution_id = ') . $InstitutionTable->aliasField('id'),
                    $InstitutionClasses->aliasField('academic_period_id = ') . $academicPeriodId,
                ]
            )
			->where($where)
			->toArray();

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
                    Log::write('debug', 'Error Add All Class profile Report Cards '.$reportCardId.' for Class '.$institution->institution_class_id.' of Institution'.$institution->id.' to processes ('.Time::now().')');
                    Log::write('debug', $newEntity->errors());
                }
            }
            // end
        }

        Log::write('debug', 'End Add All Class profile Report Cards '.$reportCardId.' for Class '.$institution->institution_class_id.' of Institution'.$institution->id.' to processes ('.Time::now().')');
    }

    private function triggerGenerateAllReportCardsShell($academicPeriodId, $reportCardId, $institutionId = null, $institutionClassId = null, $areaId =null)
    {
        $SystemProcesses = TableRegistry::getTableLocator()->get('SystemProcesses');
        $runningProcess = $SystemProcesses->getRunningProcesses($this->getRegistryAlias());

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
            $processModel = $this->getRegistryAlias();
            $passArray = [
                'institution_id' => $institutionId,
                'class_profile_template_id' => $reportCardId,
                'institution_class_id' => $institutionClassId
            ];

            $params = json_encode($passArray);

            $args = $processModel . " " . $params. ' '.$areaId;//POCOR-7382 add $areaId

            $cmd = ROOT . DS . 'bin' . DS . 'cake GenerateAllClassProfiles '.$args;
            $logs = ROOT . DS . 'logs' . DS . 'GenerateAllClassProfiles.log & echo $!';
            $shellCmd = $cmd . ' >> ' . $logs;
            // echo "<pre>";print_r($shellCmd );die;
            try {
                $pid = exec($shellCmd);
                Log::write('debug', $shellCmd);
            } catch(\Exception $ex) {
                Log::write('error', __METHOD__ . ' exception when generate all report cards : '. $ex);
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
        if ($field == 'institution_name') {
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
