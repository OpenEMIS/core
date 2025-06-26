<?php
namespace Institution\Model\Table;

use ArrayObject;
use ZipArchive;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\ResultSet;
use Cake\Event\Event;
use Cake\I18n\FrozenTime;
use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;

/**
 *
 * This class is used to manage Institutions > General > Profiles > Institutions module
 * Ticket no : POCOR-6286
 * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
 *
 */
class InstitutionsProfileTable extends ControllerActionTable
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

    public function initialize(array $config): void
    {
        $this->setTable('institutions');
        parent::initialize($config);
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);

        $this->ReportCards = TableRegistry::getTableLocator()->get('ProfileTemplate.ProfileTemplates');
        $this->InstitutionReportCards = TableRegistry::getTableLocator()->get('Institution.InstitutionReportCards');
        $this->InstitutionReportCardProcesses = TableRegistry::getTableLocator()->get('ReportCard.InstitutionReportCardProcesses');
        $this->statusOptions = [
            self::NEW_REPORT => __('New'),
            self::IN_PROGRESS => __('In Progress'),
            self::GENERATED => __('Generated'),
            self::PUBLISHED => __('Published')
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

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        unset($buttons['view']);
        // check if report card request is valid
        $reportCardId = $this->request->getQuery('report_card_id');
        $academicPeriodId = $this->request->getQuery('academic_period_id');
        if (!is_null($reportCardId) && $this->ReportCards->exists([$this->ReportCards->getPrimaryKey() => $reportCardId])) {

            $indexAttr = ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false];
            $params = [
                'report_card_id' => $reportCardId,
                'institution_id' => $entity->id,
                'academic_period_id' => $academicPeriodId,
            ];
            $queryStringParams = $this->getQueryString();
            $params = array_merge($params, $queryStringParams);
            $queryString = $this->paramsEncode($params);
            // Download button, status must be generated or published
            if ($this->AccessControl->check(['Institutions', 'InstitutionProfiles', 'downloadExcel']) && $entity->has('report_card_status') && in_array($entity->report_card_status, [self::GENERATED, self::PUBLISHED])) {
                //START:POCOR-6667
                $viewPdfUrl = $this->url('viewPDF');
                $viewPdfUrl['1'] = $queryString;
                $buttons['viewPdf'] = [
                    'label' => '<i class="fa fa-eye"></i>'.__('View PDF'),
                    'attr' => $indexAttr,
                    'url' => $viewPdfUrl
                ];
                //START:POCOR-6667
                $downloadPdfUrl =$this->url('downloadPDF');
                $downloadPdfUrl['1'] = $queryString;
                $buttons['downloadPdf'] = [
                    'label' => '<i class="fa kd-download"></i>'.__('Download PDF'),
                    'attr' => $indexAttr,
                    'url' => $downloadPdfUrl
                ];
                //START:POCOR-6793
                $downloadUrl =$this->url('downloadExcel');
                $downloadUrl['1'] = $queryString;
                $buttons['download'] = [
                    'label' => '<i class="fa kd-download"></i>'.__('Download Excel'),
                    'attr' => $indexAttr,
                    'url' => $downloadUrl
                ];
            }

            // Generate button, all statuses
            if ($this->AccessControl->check(['Institutions', 'InstitutionProfiles', 'generate'])) {
                $generateUrl =$this->url('generate');
                $generateUrl['1'] = $queryString;

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

                if ((!empty($generateStartDate) && !empty($generateEndDate))
                    && ($date >= $generateStartDate && $date <= $generateEndDate)) {
                            $buttons['generate'] = [
                            'label' => '<i class="fa fa-refresh"></i>'. __('Generate'),
                            'attr' => $indexAttr,
                            'url' => $generateUrl
                            ];
                } else {
                    $indexAttr['title'] = $this->getMessage('Profiles.date_closed');
                    $buttons['generate'] = [
                            'label' => '<i class="fa fa-refresh"></i>'. __('Generate'),
                            'attr' => $indexAttr,
                            'url' => 'javascript:void(0)'
                            ];
                }
            }
        }
        return $buttons;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('institution_name', ['sort' => ['field' => 'name']]);
        $this->field('institution_code', ['sort' => ['field' => 'code']]);
        $this->field('profile_name');
        $this->field('status', ['sort' => ['field' => 'report_card_status']]);
        $this->field('started_on');
        $this->field('completed_on');
        $this->fields['next_institution_class_id']['visible'] = false;
        $this->fields['academic_period_id']['visible'] = false;
        $this->fields['student_status_id']['visible'] = false;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('report_queue');
        $this->setFieldOrder(['institution_name', 'institution_code', 'profile_name', 'status', 'started_on', 'completed_on', 'report_queue']);
        $this->setFieldVisible(['index'], ['institution_name', 'institution_code', 'profile_name', 'status', 'started_on', 'completed_on', 'report_queue']);

        // SQL Query to get the current processing list for report_queue table
        $this->reportProcessList = $this->InstitutionReportCardProcesses
            ->find()
            ->select([
                $this->InstitutionReportCardProcesses->aliasField('report_card_id'),
                $this->InstitutionReportCardProcesses->aliasField('institution_id'),
                $this->InstitutionReportCardProcesses->aliasField('academic_period_id')
            ])
            ->where([
                $this->InstitutionReportCardProcesses->aliasField('status') => $this->InstitutionReportCardProcesses::NEW_PROCESS
            ])
            ->order([
                $this->InstitutionReportCardProcesses->aliasField('created'),
            ])
            ->enableHydration(false)
            ->toArray();

            // Start POCOR-5188
            $is_manual_exist = $this->getManualUrl('Institutions','Generate Institutions Profile','Profiles');
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

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $AcademicPeriod = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $session = $this->request->getSession();
        $institutionId = $this->getInstitutionId();
        // Academic Periods filter
        $academicPeriodOptions = $AcademicPeriod->getYearList(['isEditable' => true]);

        $selectedAcademicPeriod = !is_null($this->request->getQuery('academic_period_id')) ? $this->request->getQuery('academic_period_id') : $AcademicPeriod->getCurrent();

        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        //$where[$this->InstitutionReportCards->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        //End

        $ProfileTemplates = TableRegistry::getTableLocator()->get('ProfileTemplate.ProfileTemplates');

        // Report Cards filter
        $reportCardOptions = [];
        $reportCardOptions = $ProfileTemplates->find('list')
            ->where([
                $ProfileTemplates->aliasField('academic_period_id') => $selectedAcademicPeriod
            ])
            ->toArray();


        $reportCardOptions = ['-1' => '-- '.__('Select Profile').' --'] + $reportCardOptions; //POCOR-6653 - updated filter name as per client's requirement

        $selectedReportCard = !is_null($this->request->getQuery('report_card_id')) ? $this->request->getQuery('report_card_id') : -1;
        $this->controller->set(compact('reportCardOptions', 'selectedReportCard'));
        //End

        $query
            ->select([
                'institution_name' => $this->aliasField('name'),
                'institution_code' => $this->aliasField('code'),
                'report_card_id' => $this->InstitutionReportCards->aliasField('report_card_id'),
                'report_card_status' => $this->InstitutionReportCards->aliasField('status'),
                'report_card_started_on' => $this->InstitutionReportCards->aliasField('started_on'),
                'report_card_completed_on' => $this->InstitutionReportCards->aliasField('completed_on'),
            ])
            ->leftJoin([$this->InstitutionReportCards->getAlias() => $this->InstitutionReportCards->getTable()],
                [
                    $this->InstitutionReportCards->aliasField('institution_id = ') . $this->aliasField('id'),
                    $this->InstitutionReportCards->aliasField('academic_period_id = ') . $selectedAcademicPeriod,
                    $this->InstitutionReportCards->aliasField('report_card_id = ') . $selectedReportCard
                ]
            )
            //->autoFields(true)
            ->order([
                $this->aliasField('name'),
            ])
            ->where([$this->aliasField('id') => $institutionId])
            ->all();
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $extra['elements']['controls'] = ['name' => 'Institution.ReportCards/controls', 'data' => [

                'encodedQueryString' => $encodedQueryString], 'options' => [], 'order' => 1];

        // sort
        $sortList = ['report_card_status', 'institution_name', 'institution_code'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;

    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'institution_name';
        $searchableFields[] = 'institution_code';
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
            $startedOnValue = new FrozenTime($entity->report_card_started_on);
            $value = $this->formatDateTime($startedOnValue);
        }

        return $value;
    }

    public function onGetCompletedOn(Event $event, Entity $entity)
    {
        $value = '';

        if ($entity->has('report_card_completed_on')) {
            $completedOnValue = new FrozenTime($entity->report_card_completed_on);
            $value = $this->formatDateTime($completedOnValue);
        }

        return $value;
    }

    public function onGetReportQueue(Event $event, Entity $entity)
    {
        if ($entity->has('report_card_id')) {
            $reportCardId = $entity->report_card_id;
        } else if (!is_null($this->request->getQuery('report_card_id'))) {
            $reportCardId = $this->request->getQuery('report_card_id');
        }

        $academicPeriodId = $this->request->getQuery('academic_period_id');

        $search = [
            'report_card_id' => $reportCardId,
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

    public function onGetProfileName(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('report_card_id')) {
            $reportCardId = $entity->report_card_id;
        } else if (!is_null($this->request->getQuery('report_card_id'))) {
            // used if student report card record has not been created yet
            $reportCardId = $this->request->getQuery('report_card_id');
        }

        if (!empty($reportCardId)) {
            $reportCardEntity = $this->ReportCards->find()->where(['id' => $reportCardId])->first();
            if (!empty($reportCardEntity)) {
                $value = $reportCardEntity->name;
            }
        }
        return $value;
    }

    public function downloadExcel(Event $event, ArrayObject $extra)
    {
        $model = $this->InstitutionReportCards;
        $ids = $this->getQueryString();
        foreach ($ids as $key => $value) {
            if($key == 'id'){
                unset($ids[$key]);
            }
        }
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

    public function downloadPDF(Event $event, ArrayObject $extra)
    {
        $model = $this->InstitutionReportCards;
        $ids = $this->getQueryString();
        foreach ($ids as $key => $value) {
            if($key == 'id'){
                unset($ids[$key]);
            }
        }
        if ($model->exists($ids)) {
            $data = $model->get($ids);
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

    public function viewPDF(Event $event, ArrayObject $extra)
    {
        $model = $this->InstitutionReportCards;
        $ids = $this->getQueryString();
        unset($ids['id']);

        $data = $model->find('all')->where($ids)->first();
//        die('<die>' . print_r($data,true));
        if (!empty($data)) {
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
        exit();
    }

    public function generate(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        $hasTemplate = $this->ReportCards->checkIfHasTemplate($params['report_card_id']);

        if ($hasTemplate) {
            $this->addReportCardsToProcesses($params['academic_period_id'], $params['report_card_id'], $params['institution_id']);
            $this->triggerGenerateAllReportCardsShell($params['academic_period_id'], $params['report_card_id'], $params['institution_id']);
            $this->Alert->warning('ReportCardStatuses.generateProfile');
        }

        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function generateAll(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        $hasTemplate = $this->ReportCards->checkIfHasTemplate($params['report_card_id']);

        if ($hasTemplate) {
            $InstitutionReportCardProcesses = TableRegistry::get('ReportCard.InstitutionReportCardProcesses');
            $inProgress = $InstitutionReportCardProcesses->find()
                ->where([
                    $InstitutionReportCardProcesses->aliasField('report_card_id') => $params['report_card_id'],
                    $InstitutionReportCardProcesses->aliasField('institution_id') => $params['institution_id'],
                    $InstitutionReportCardProcesses->aliasField('academic_period_id') => $params['academic_period_id']
                ])
                ->count();


            if (!$inProgress) {
                $this->addReportCardsToProcesses($params['academic_period_id'], $params['report_card_id']);
                $this->triggerGenerateAllReportCardsShell($params['academic_period_id'], $params['report_card_id']);
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

    public function downloadAllPdf(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();

        // only download report cards with generated or published status
        $statusArray = [self::GENERATED, self::PUBLISHED];

        $files = $this->InstitutionReportCards->find()
            ->contain(['ProfileTemplates'])
            ->where([
                $this->InstitutionReportCards->aliasField('report_card_id') => $params['report_card_id'],
                $this->InstitutionReportCards->aliasField('academic_period_id') => $params['academic_period_id'],
                $this->InstitutionReportCards->aliasField('status IN ') => $statusArray,
                $this->InstitutionReportCards->aliasField('file_name IS NOT NULL'),
                $this->InstitutionReportCards->aliasField('file_content IS NOT NULL')
            ])
            ->toArray();

        if (!empty($files)) {
            $path = WWW_ROOT . 'export' . DS . 'customexcel' . DS;
            $zipName = 'InstitutionReportCards' . '_' . date('Ymd') . 'T' . date('His') . '.zip';
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

    public function downloadAll(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();

        // only download report cards with generated or published status
        $statusArray = [self::GENERATED, self::PUBLISHED];

        $files = $this->InstitutionReportCards->find()
            ->contain(['ProfileTemplates'])
            ->where([
                $this->InstitutionReportCards->aliasField('report_card_id') => $params['report_card_id'],
                $this->InstitutionReportCards->aliasField('academic_period_id') => $params['academic_period_id'],
                $this->InstitutionReportCards->aliasField('status IN ') => $statusArray,
                $this->InstitutionReportCards->aliasField('file_name IS NOT NULL'),
                $this->InstitutionReportCards->aliasField('file_content IS NOT NULL')
            ])
            ->toArray();

        if (!empty($files)) {
            $path = WWW_ROOT . 'export' . DS . 'customexcel' . DS;
            $zipName = 'InstitutionReportCards' . '_' . date('Ymd') . 'T' . date('His') . '.zip';
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

    public function publish(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        $result = $this->InstitutionReportCards->updateAll(['status' => self::PUBLISHED], $params);
        $this->Alert->success('ReportCardStatuses.publish');
        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function publishAll(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();

        // only publish report cards with generated status to published status
        $result = $this->InstitutionReportCards->updateAll(['status' => self::PUBLISHED], [
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
        $result = $this->InstitutionReportCards->updateAll(['status' => self::NEW_REPORT], $params);
        $this->Alert->success('ReportCardStatuses.unpublish');
        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function unpublishAll(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        // only unpublish report cards with published status to new status
        $result = $this->InstitutionReportCards->updateAll(['status' => self::NEW_REPORT], [
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

    private function addReportCardsToProcesses($academicPeriodId, $reportCardId, $institutionId = null)
    {
        Log::write('debug', 'Initialize Add All Institution Report Cards '.$reportCardId.' for Institution '.$institutionId.' to processes ('.FrozenTime::now().')');

        $InstitutionReportCardProcesses = TableRegistry::get('ReportCard.InstitutionReportCardProcesses');
        $InstitutionTable = TableRegistry::get('Institution.Institutions');
        $where = [];
        if (!is_null($institutionId)) {
            $where[$InstitutionTable->aliasField('id')] = $institutionId;
        }
        $institutionData = $InstitutionTable->find()
            ->select([
                $InstitutionTable->aliasField('id'),
            ])
            ->where($where)
            ->toArray();

        foreach ($institutionData as $institution) {
            // Report card processes
            $idKeys = [
                'report_card_id' => $reportCardId,
                'institution_id' => $institution->id,
            ];

            $data = [
                'status' => $InstitutionReportCardProcesses::NEW_PROCESS,
                'academic_period_id' => $academicPeriodId,
                'created' => date('Y-m-d H:i:s')
            ];
            $obj = array_merge($idKeys, $data);
            $newEntity = $InstitutionReportCardProcesses->newEntity($obj);
            $InstitutionReportCardProcesses->save($newEntity);
            // end

            // institution report card
            $recordIdKeys = [
                'report_card_id' => $reportCardId,
                'institution_id' => $institution->id,
                'academic_period_id' => $academicPeriodId,
            ];
            if ($this->InstitutionReportCards->exists($recordIdKeys)) {
                $institutionsReportCardEntity = $this->InstitutionReportCards->find()
                    ->where($recordIdKeys)
                    ->first();

                $newData = [
                    'status' => $this->InstitutionReportCards::NEW_REPORT,
                    'started_on' => NULL,
                    'completed_on' => NULL,
                    'file_name' => NULL,
                    'file_content' => NULL,
                    'institution_id' => $institution->id
                ];

                $newEntity = $this->InstitutionReportCards->patchEntity($institutionsReportCardEntity, $newData);

                if (!$this->InstitutionReportCards->save($newEntity)) {
                    Log::write('debug', 'Error Add All institution profile Report Cards '.$reportCardId.' for Institution '.$institution->id.' to processes ('.FrozenTime::now().')');
                    Log::write('debug', $newEntity->errors());
                }
            }
            // end
        }

        Log::write('debug', 'End Add All institution profile Report Cards '.$reportCardId.' for Institution '.$institutionId.' to processes ('.FrozenTime::now().')');
    }

    private function triggerGenerateAllReportCardsShell($academicPeriodId, $reportCardId, $institutionId = null)
    {
        $SystemProcesses = TableRegistry::get('SystemProcesses');
        $runningProcess = $SystemProcesses->getRunningProcesses($this->getRegistryAlias());

        foreach ($runningProcess as $key => $processData) {
            $systemProcessId = $processData['id'];
            $pId = !empty($processData['process_id']) ? $processData['process_id'] : 0;
            $createdDate = $processData['created'];

            $expiryDate = clone($createdDate);
            $expiryDate->addMinutes(30);
            $today = FrozenTime::now();

            if ($expiryDate < $today) {
                $SystemProcesses->updateProcess($systemProcessId, FrozenTime::now(), $SystemProcesses::COMPLETED);
                $SystemProcesses->killProcess($pId);
            }
        }

        if (count($runningProcess) <= self::MAX_PROCESSES) {
            $processModel = $this->getRegistryAlias();
            $passArray = [
                'institution_id' => $institutionId,
                'report_card_id' => $reportCardId
            ];

            $params = json_encode($passArray);

            $args = $processModel . " " . $params;

            $cmd = ROOT . DS . 'bin' . DS . 'cake GenerateAllInstitutionReportCards '.$args;
            $logs = ROOT . DS . 'logs' . DS . 'GenerateAllInstitutionReportCards.log & echo $!';
            $shellCmd = $cmd . ' >> ' . $logs;
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

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'institution_name') {
            return __('Institution Name');
        } else if ($field == 'institution_code') {
            return  __('Institution Code');
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
