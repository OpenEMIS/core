<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Log\Log;
use ZipArchive;
use App\Model\Table\ControllerActionTable;

class ReportCardStatusesTable extends ControllerActionTable
{
    private $statusOptions = [];

    // for status
    CONST NEW_REPORT = 1;
    CONST IN_PROGRESS = 2;
    CONST GENERATED = 3;
    CONST PUBLISHED = 4;

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
        $this->hasMany('InstitutionClassGrades', ['className' => 'Institution.InstitutionClassGrades']);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        // $this->toggle('view', false);
        $this->toggle('remove', false);

        $this->StudentsReportCards = TableRegistry::get('Institution.InstitutionStudentsReportCards');

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
        $events['ControllerAction.Model.generateAll'] = 'generateAll';
        $events['ControllerAction.Model.downloadAll'] = 'downloadAll';
        $events['ControllerAction.Model.publishAll'] = 'publishAll';
        $events['ControllerAction.Model.unpublishAll'] = 'unpublishAll';
        $events['ControllerAction.Model.publish'] = 'publish';
        $events['ControllerAction.Model.unpublish'] = 'unpublish';
        return $events;
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        if (isset($buttons['view'])) {
            unset($buttons['view']);
        }

        $indexAttr = ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false];
        $params = [
            'report_card_id' => $this->request->query('report_card_id'),
            'student_id' => $entity->student_id,
            'institution_id' => $entity->institution_id,
            'academic_period_id' => $entity->academic_period_id,
            'education_grade_id' => $entity->education_grade_id,
            'institution_class_id' => $entity->institution_class_id
        ];

        // Generate button
        $url = [
            'plugin' => 'CustomExcel',
            'controller' => 'CustomExcels',
            'action' => 'export',
            'ReportCards'
        ];
        $generateUrl = $this->setQueryString($url, $params);
        $buttons['generate'] = [
            'label' => '<i class="fa fa-tasks"></i>'.__('Generate'),
            'attr' => $indexAttr,
            'url' => $generateUrl
        ];
        // end

        // Download button
        // status must be generated or published
        if ($entity->has('InstitutionStudentsReportCards') && in_array($entity->InstitutionStudentsReportCards['status'], [self::GENERATED, self::PUBLISHED])) {
            $downloadParams = $params;
            if (isset($downloadParams['institution_class_id'])) {
                unset($downloadParams['institution_class_id']);
            }
            $downloadUrl = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'InstitutionStudentsReportCards',
                '0' => 'download',
                '1' => $this->paramsEncode($downloadParams)
            ];
            $buttons['download'] = [
                'label' => '<i class="fa kd-download"></i>'.__('Download'),
                'attr' => $indexAttr,
                'url' => $downloadUrl
            ];
        }
        // end

        // Publish button
        // status must be generated
        if ($entity->has('InstitutionStudentsReportCards') && $entity->InstitutionStudentsReportCards['status'] == self::GENERATED) {
            $url = $this->url('publish');
            $publishUrl = $this->setQueryString($url, $params);
            $buttons['publish'] = [
                'label' => '<i class="fa fa-share-square-o"></i>'.__('Publish'),
                'attr' => $indexAttr,
                'url' => $publishUrl
            ];
        }
        // end

        // Unpublish button
        // status must be published
        if ($entity->has('InstitutionStudentsReportCards') && $entity->InstitutionStudentsReportCards['status'] == self::PUBLISHED) {
            $url = $this->url('unpublish');
            $unpublishUrl = $this->setQueryString($url, $params);
            $buttons['unpublish'] = [
                'label' => '<i class="fa fa-lock"></i>'.__('Unpublish'),
                'attr' => $indexAttr,
                'url' => $unpublishUrl
            ];
        }
        // end

        return $buttons;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('status');
        $this->field('openemis_no');
        $this->field('student_id', ['type' => 'integer']);
        $this->field('report_card');
        $this->fields['student_status_id']['visible'] = false;
        $this->setFieldOrder(['status', 'openemis_no', 'student_id', 'academic_period_id', 'report_card']);

        $reportCardId = $this->request->query('report_card_id');
        $classId = $this->request->query('class_id');

        if (!is_null($reportCardId) && !is_null($classId)) {
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

            // Generate all button
            $url = $this->url('generateAll');
            $generateButton['url'] = $this->setQueryString($url, $params);
            $generateButton['type'] = 'button';
            $generateButton['label'] = '<i class="fa fa-tasks"></i>';
            $generateButton['attr'] = $toolbarAttr;
            $generateButton['attr']['title'] = __('Generate All');
            $extra['toolbarButtons']['generateAll'] = $generateButton;
            // end

            // Download all button
            $url = $this->url('downloadAll');
            $downloadButton['url'] = $this->setQueryString($url, $params);
            $downloadButton['type'] = 'button';
            $downloadButton['label'] = '<i class="fa kd-download"></i>';
            $downloadButton['attr'] = $toolbarAttr;
            $downloadButton['attr']['title'] = __('Download All');
            $extra['toolbarButtons']['downloadAll'] = $downloadButton;
            // end

            // Publish all button
            $url = $this->url('publishAll');
            $publishButton['url'] = $this->setQueryString($url, $params);
            $publishButton['type'] = 'button';
            $publishButton['label'] = '<i class="fa fa-share-square-o"></i>';
            $publishButton['attr'] = $toolbarAttr;
            $publishButton['attr']['title'] = __('Publish All');
            $extra['toolbarButtons']['publishAll'] = $publishButton;
            // end

            // Unpublish all button
            $url = $this->url('unpublishAll');
            $unpublishButton['url'] = $this->setQueryString($url, $params);
            $unpublishButton['type'] = 'button';
            $unpublishButton['label'] = '<i class="fa fa-lock"></i>';
            $unpublishButton['attr'] = $toolbarAttr;
            $unpublishButton['attr']['title'] = __('Unpublish All');
            $extra['toolbarButtons']['unpublishAll'] = $unpublishButton;
            // end
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $Classes = $this->InstitutionClasses;
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $ReportCards = TableRegistry::get('ReportCard.ReportCards');

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
            $reportCardOptions = $ReportCards->find('list')
                ->where([
                    $ReportCards->aliasField('academic_period_id') => $selectedAcademicPeriod,
                    $ReportCards->aliasField('education_grade_id IN ') => $availableGrades
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
        if ($selectedReportCard != -1) {
            $reportCardEntity = $ReportCards->find()->where(['id' => $selectedReportCard])->first();
            if (!empty($reportCardEntity)) {
                $classOptions = $Classes->find('list')
                    ->matching('ClassGrades')
                    ->where([
                        $Classes->aliasField('academic_period_id') => $selectedAcademicPeriod,
                        $Classes->aliasField('institution_id') => $institutionId,
                        'ClassGrades.education_grade_id' => $reportCardEntity->education_grade_id
                    ])
                    ->toArray();
            }
        }

        $classOptions = ['-1' => '-- '.__('Select Class').' --'] + $classOptions;
        $selectedClass = !is_null($this->request->query('class_id')) ? $this->request->query('class_id') : -1;
        $this->controller->set(compact('classOptions', 'selectedClass'));
        $where[$this->aliasField('institution_class_id')] = $selectedClass;
        //End

        $query
            ->select([
                $this->StudentsReportCards->aliasField('id'),
                $this->StudentsReportCards->aliasField('report_card_id'),
                $this->StudentsReportCards->aliasField('status')
            ])
            ->contain('Users')
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
            ->autoFields(true)
            ->where($where);

        $extra['elements']['controls'] = ['name' => 'Institution.ReportCards/controls', 'data' => [], 'options' => [], 'order' => 1];
    }

    public function onGetStatus(Event $event, Entity $entity)
    {
        if ($entity->has('InstitutionStudentsReportCards') && !empty($entity->InstitutionStudentsReportCards['status'])) {
            $status = $entity->InstitutionStudentsReportCards['status'];
            $value = $this->statusOptions[$status];
        } else {
            $value = $this->statusOptions[self::NEW_REPORT];
        }
        return $value;
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
        if (!is_null($this->request->query('report_card_id'))) {
            $ReportCards = TableRegistry::get('ReportCard.ReportCards');
            $entity = $ReportCards->get($this->request->query('report_card_id'));

            if (!empty($entity)) {
                $value = $entity->code_name;
            }
        }
        return $value;
    }

    public function generateAll(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        $this->triggerGenerateAllReportCardsShell($params['institution_id'], $params['institution_class_id'], $params['report_card_id']);

        $event->stopPropagation();
        $this->Alert->warning('ReportCardStatuses.generateAll');
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
            $filePath = $path . $zipName;

            $zip = new ZipArchive;
            $zip->open($filePath, ZipArchive::CREATE);
            $fileType = 'xlsx';
            foreach ($files as $file) {
              $fileName = $file->report_card->code . '_' . $file->student->openemis_no . '_' . $file->student->name . '.' . $fileType;
              $zip->addFromString($fileName,  $this->getFile($file->file_content));
            }
            $zip->close();

            header("Pragma: public", true);
            header("Expires: 0"); // set expiration time
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-Type: application/force-download");
            header("Content-Type: application/zip");
            header("Content-Length: ".filesize($filePath));
            header("Content-Disposition: attachment; filename=".$zipName);
            readfile($filePath);

        } else {
            $event->stopPropagation();
            $this->Alert->error('ReportCardStatuses.noFilesToDownload');
            return $this->controller->redirect($this->url('index'));
        }
    }

    public function publish(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        $result = $this->StudentsReportCards->updateAll(['status' => self::PUBLISHED], $params);
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
            $this->Alert->error('ReportCardStatuses.noFilesToPublish');
        }

        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    public function unpublish(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        $result = $this->StudentsReportCards->updateAll(['status' => self::NEW_REPORT], $params);
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
            $this->Alert->error('ReportCardStatuses.noFilesToUnpublish');
        }

        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    }

    private function triggerGenerateAllReportCardsShell($institutionId, $institutionClassId, $reportCardId)
    {
        $args = '';
        $args .= !is_null($institutionId) ? ' '.$institutionId : '';
        $args .= !is_null($institutionClassId) ? ' '.$institutionClassId : '';
        $args .= !is_null($reportCardId) ? ' '.$reportCardId : '';

        $cmd = ROOT . DS . 'bin' . DS . 'cake GenerateAllReportCards'.$args;
        $logs = ROOT . DS . 'logs' . DS . 'GenerateAllReportCards.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;

        try {
            $pid = exec($shellCmd);
            Log::write('debug', $shellCmd);
        } catch(\Exception $ex) {
            Log::write('error', __METHOD__ . ' exception when generate all report cards : '. $ex);
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
