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

        $this->statusOptions = [
            self::NEW_REPORT => __('New'),
            self::IN_PROGRESS => __('In Progress'),
            self::GENERATED => __('Generated'),
            self::PUBLISHED => __('Published')
        ];
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.generateAll'] = 'generateAll';
        $events['ControllerAction.Model.downloadAll'] = 'downloadAll';
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
        if ($entity->has('InstitutionStudentsReportCards') && $entity->InstitutionStudentsReportCards['status'] == self::GENERATED) {
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

            // publish all button
            $publishButton['url'] = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentreExams', 'add'];
            $publishButton['type'] = 'button';
            $publishButton['label'] = '<i class="fa fa-share-square-o"></i>';
            $publishButton['attr'] = $toolbarAttr;
            $publishButton['attr']['title'] = __('Publish All');
            $extra['toolbarButtons']['publishAll'] = $publishButton;
            // end
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $Classes = $this->InstitutionClasses;
        $StudentReportCards = TableRegistry::get('Institution.InstitutionStudentsReportCards');
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $ReportCards = TableRegistry::get('ReportCard.ReportCards');

        // Academic Periods filter
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        //End

        // Report Cards filter
        $availableGrades = $InstitutionGrades->find()
            ->where([$InstitutionGrades->aliasField('institution_id') => $institutionId])
            ->extract('education_grade_id')
            ->toArray();

        $reportCardOptions = $ReportCards->find('list')
            ->where([
                $ReportCards->aliasField('academic_period_id') => $selectedAcademicPeriod,
                $ReportCards->aliasField('education_grade_id IN ') => $availableGrades
            ])
            ->toArray();
        $reportCardOptions = ['-1' => '-- '.__('Select Report Card').' --'] + $reportCardOptions;
        $selectedReportCard = !is_null($this->request->query('report_card_id')) ? $this->request->query('report_card_id') : -1;
        $this->controller->set(compact('reportCardOptions', 'selectedReportCard'));
        //End

        // Class filter
        $classOptions = [];
        if ($selectedReportCard != -1) {
            $reportCardEntity = $ReportCards->get($selectedReportCard);

            if (!empty($reportCardEntity)) {
                $educationGrade = $reportCardEntity->education_grade_id;
                $classOptions = $Classes->find('list')
                    ->matching('ClassGrades')
                    ->where([
                        $Classes->aliasField('academic_period_id') => $selectedAcademicPeriod,
                        $Classes->aliasField('institution_id') => $institutionId,
                        'ClassGrades.education_grade_id' => $educationGrade
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
                $StudentReportCards->aliasField('id'),
                $StudentReportCards->aliasField('report_card_id'),
                $StudentReportCards->aliasField('status')
            ])
            ->contain('Users')
            ->leftJoin([$StudentReportCards->alias() => $StudentReportCards->table()],
                [
                    $StudentReportCards->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $StudentReportCards->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $StudentReportCards->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                    $StudentReportCards->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
                    $StudentReportCards->aliasField('institution_class_id = ') . $this->aliasField('institution_class_id'),
                    $StudentReportCards->aliasField('report_card_id = ') . $selectedReportCard
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

    public function generateAll()
    {
        $params = $this->getQueryString();
        $this->triggerGenerateAllReportCardsShell($params['institution_id'], $params['institution_class_id'], $params['report_card_id']);

        $this->Alert->warning('general.notExists');
        return $this->controller->redirect($this->url('index'));
    }

    public function downloadAll()
    {
        $params = $this->getQueryString();
        $StudentReportCards = TableRegistry::get('Institution.InstitutionStudentsReportCards');

        $files = $StudentReportCards->find()
            ->contain(['Students', 'ReportCards'])
            ->where([
                $StudentReportCards->aliasField('institution_id') => $params['institution_id'],
                $StudentReportCards->aliasField('institution_class_id') => $params['institution_class_id'],
                $StudentReportCards->aliasField('report_card_id') => $params['report_card_id'],
                $StudentReportCards->aliasField('file_name IS NOT NULL'),
                $StudentReportCards->aliasField('file_content IS NOT NULL')
            ])
            ->toArray();

        if (!empty($files)) {
            $path = WWW_ROOT . 'export' . DS . 'customexcel' . DS;
            $zipName = 'ReportCards' . '_' . date('Ymd') . 'T' . date('His') . '.zip';
            $zipFilePath = $path . $zipName;

            $zip = new ZipArchive;
            $zip->open($zipFilePath, ZipArchive::CREATE);
            $fileType = 'pdf';
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
            header("Content-Length:" . filesize($zipFilePath));
            header('Content-Disposition: attachment; filename="' . $zipName . '"');
            readfile($zipFilePath);

        } else {
            $this->Alert->warning('general.notExists');
            return $this->controller->redirect($this->url('index'));
        }
    }

    private function triggerGenerateAllReportCardsShell($institutionId, $institutionClassId, $reportCardId)
    {
        $args = '';
        $args .= !is_null($institutionId) ? ' '.$institutionId : '';
        $args .= !is_null($institutionClassId) ? ' '.$institutionClassId : '';
        $args .= !is_null($reportCardId) ? ' '.$reportCardId : '';

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

    private function getFile($phpResourceFile) {
        $file = '';
        while (!feof($phpResourceFile)) {
            $file .= fread($phpResourceFile, 8192);
        }
        fclose($phpResourceFile);

        return $file;
    }
}
