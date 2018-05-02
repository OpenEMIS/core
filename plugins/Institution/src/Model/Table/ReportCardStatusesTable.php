<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\ResultSet;
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

        $this->addBehavior('User.AdvancedNameSearch');

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);

        $this->ReportCards = TableRegistry::get('ReportCard.ReportCards');
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
        $events['ControllerAction.Model.generate'] = 'generate';
        $events['ControllerAction.Model.generateAll'] = 'generateAll';
        $events['ControllerAction.Model.downloadAll'] = 'downloadAll';
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
                    'label' => '<i class="fa kd-download"></i>'.__('Download'),
                    'attr' => $indexAttr,
                    'url' => $downloadUrl
                ];
            }

            $params['institution_class_id'] = $entity->institution_class_id;

            // Generate button, all statuses
            if ($this->AccessControl->check(['Institutions', 'ReportCardStatuses', 'generate'])) {
                $generateUrl = $this->setQueryString($this->url('generate'), $params);
                $buttons['generate'] = [
                    'label' => '<i class="fa fa-refresh"></i>'.__('Generate'),
                    'attr' => $indexAttr,
                    'url' => $generateUrl
                ];
            }

            // Publish button, status must be generated
            if ($this->AccessControl->check(['Institutions', 'ReportCardStatuses', 'publish']) && $entity->has('report_card_status') && $entity->report_card_status == self::GENERATED) {
                $publishUrl = $this->setQueryString($this->url('publish'), $params);
                $buttons['publish'] = [
                    'label' => '<i class="fa kd-publish"></i>'.__('Publish'),
                    'attr' => $indexAttr,
                    'url' => $publishUrl
                ];
            }

            // Unpublish button, status must be published
            if ($this->AccessControl->check(['Institutions', 'ReportCardStatuses', 'unpublish']) && $entity->has('report_card_status') && $entity->report_card_status == self::PUBLISHED) {
                $unpublishUrl = $this->setQueryString($this->url('unpublish'), $params);
                $buttons['unpublish'] = [
                    'label' => '<i class="fa kd-unpublish"></i>'.__('Unpublish'),
                    'attr' => $indexAttr,
                    'url' => $unpublishUrl
                ];
            }
        }
        return $buttons;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('status', ['sort' => ['field' => 'report_card_status']]);
        $this->field('openemis_no', ['sort' => ['field' => 'Users.openemis_no']]);
        $this->field('student_id', ['type' => 'integer', 'sort' => ['field' => 'Users.first_name']]);
        $this->field('report_card');
        $this->fields['student_status_id']['visible'] = false;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder(['status', 'openemis_no', 'student_id', 'academic_period_id', 'report_card']);
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

        $classOptions = ['-1' => '-- '.__('Select Class').' --'] + $classOptions;
        $this->controller->set(compact('classOptions', 'selectedClass'));
        $where[$this->aliasField('institution_class_id')] = $selectedClass;
        //End

        $query
            ->select([
                'report_card_id' => $this->StudentsReportCards->aliasField('report_card_id'),
                'report_card_status' => $this->StudentsReportCards->aliasField('status')
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
            ->autoFields(true)
            ->where($where);

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
                    $downloadButton['url'] = $this->setQueryString($this->url('downloadAll'), $params);
                    $downloadButton['type'] = 'button';
                    $downloadButton['label'] = '<i class="fa kd-download"></i>';
                    $downloadButton['attr'] = $toolbarAttr;
                    $downloadButton['attr']['title'] = __('Download All');
                    $extra['toolbarButtons']['downloadAll'] = $downloadButton;
                }

                // Generate all button
                $generateButton['url'] = $this->setQueryString($this->url('generateAll'), $params);
                $generateButton['type'] = 'button';
                $generateButton['label'] = '<i class="fa fa-refresh"></i>';
                $generateButton['attr'] = $toolbarAttr;
                $generateButton['attr']['title'] = __('Generate All');
                $extra['toolbarButtons']['generateAll'] = $generateButton;

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

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'student_id';
        $searchableFields[] = 'openemis_no';
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('institution_class_id', ['type' => 'integer']);
        $this->setFieldOrder(['academic_period_id', 'status', 'openemis_no', 'student_id',  'report_card', 'institution_class_id']);
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->select([
                'report_card_id' => $this->StudentsReportCards->aliasField('report_card_id'),
                'report_card_status' => $this->StudentsReportCards->aliasField('status')
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

    public function generate(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        $hasTemplate = $this->ReportCards->checkIfHasTemplate($params['report_card_id']);

        if ($hasTemplate) {
            $url = [
                'plugin' => 'CustomExcel',
                'controller' => 'CustomExcels',
                'action' => 'export',
                'ReportCards'
            ];
            $url = $this->setQueryString($url, $params);
            $this->Alert->success('ReportCardStatuses.generate');
        } else {
            $url = $this->url('index');
            $this->Alert->warning('ReportCardStatuses.noTemplate');
        }

        $event->stopPropagation();
        return $this->controller->redirect($url);
    }

    public function generateAll(Event $event, ArrayObject $extra)
    {
        $params = $this->getQueryString();
        $hasTemplate = $this->ReportCards->checkIfHasTemplate($params['report_card_id']);

        if ($hasTemplate) {
            $ReportCardProcesses = TableRegistry::get('ReportCard.ReportCardProcesses');
            $inProgress = $ReportCardProcesses->find()
                ->where([
                    $ReportCardProcesses->aliasField('report_card_id') => $params['report_card_id'],
                    $ReportCardProcesses->aliasField('institution_class_id') => $params['institution_class_id']
                ])
                ->count();

            if (!$inProgress) {
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
