<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class InstitutionStudentsReportCardsTable extends ControllerActionTable
{
    private $statusOptions = [];

    // for status
    CONST NEW_REPORT = 1;
    CONST IN_PROGRESS = 2;
    CONST GENERATED = 3;
    CONST PUBLISHED = 4;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('ReportCards', ['className' => 'ReportCard.ReportCards']);
        $this->belongsTo('Students', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsToMany('ReportCardSubjects', [
            'className' => 'ReportCard.ReportCardSubjects',
            'joinTable' => 'institution_students_report_cards_comments',
            'foreignKey' => ['report_card_id', 'student_id', 'institution_id', 'academic_period_id', 'education_grade_id'],
            'targetForeignKey' => ['report_card_id', 'education_subject_id'],
            'through' => 'Institution.InstitutionStudentsReportCardsComments',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('CompositeKey');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'ReportCardComments' => ['index', 'add']
        ]);

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

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['file_name']['visible'] = false;
        $this->fields['file_content']['visible'] = false;
        $this->fields['principal_comments']['visible'] = false;
        $this->fields['homeroom_teacher_comments']['visible'] = false;

        $this->field('student_id', ['type' => 'select']);
        $this->field('report_card_id', ['type' => 'select']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('openemis_no');
        $this->setFieldOrder(['status', 'openemis_no', 'student_id', 'report_card_id', 'file_content', 'institution_class_id']);

        $toolbarAttr = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];

        // generate all button
        // fa-tasks
        //fa-refresh
        // fa-play
        $generateButton['url'] = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentreExams', 'add'];
        $generateButton['type'] = 'button';
        $generateButton['label'] = '<i class="fa fa-tasks"></i>';
        $generateButton['attr'] = $toolbarAttr;
        $generateButton['attr']['title'] = __('Generate All');
        $extra['toolbarButtons']['generateAll'] = $generateButton;

        // download all button
        $downloadButton['url'] = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentreExams', 'add'];
        $downloadButton['type'] = 'button';
        $downloadButton['label'] = '<i class="fa kd-download"></i>';
        $downloadButton['attr'] = $toolbarAttr;
        $downloadButton['attr']['title'] = __('Download All');
        $extra['toolbarButtons']['downloadAll'] = $downloadButton;

        // publish all button
        //fa-share-square-o
        //fa-upload
        $publishButton['url'] = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentreExams', 'add'];
        $publishButton['type'] = 'button';
        $publishButton['label'] = '<i class="fa fa-share-square-o"></i>';
        $publishButton['attr'] = $toolbarAttr;
        $publishButton['attr']['title'] = __('Publish All');
        $extra['toolbarButtons']['publishAll'] = $publishButton;
    }

     public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
     {
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $extra['elements']['controls'] = ['name' => 'Institution.ReportCards/controls', 'data' => [], 'options' => [], 'order' => 1];

        // Academic Periods filter
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        //End

        // Report Cards filter
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $availableGrades = $InstitutionGrades->find()
            ->where([$InstitutionGrades->aliasField('institution_id') => $institutionId])
            ->extract('education_grade_id')
            ->toArray();

        $ReportCards = TableRegistry::get('ReportCard.ReportCards');
        $reportCardOptions = $ReportCards->find('list')
            ->where([
                $ReportCards->aliasField('academic_period_id') => $selectedAcademicPeriod,
                $ReportCards->aliasField('education_grade_id IN ') => $availableGrades
            ])
            ->toArray();
        $reportCardOptions = ['0' => __('All Report Cards')] + $reportCardOptions;
        $selectedReportCard = !is_null($this->request->query('report_card_id')) ? $this->request->query('report_card_id') : 0;
        $this->controller->set(compact('reportCardOptions', 'selectedReportCard'));
        if (!empty($selectedReportCard)) {
             $where[$ReportCards->aliasField('id')] = $selectedReportCard;
        }
        //End

        // Class filter
        //End

        $query
            ->contain('Students')
            ->where($where);
    }

    public function onGetStatus(Event $event, Entity $entity)
    {
        return $this->statusOptions[$entity->status];
    }

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('student')) {
            $value = $entity->student->openemis_no;
        }
        return $value;
    }

    public function findComments(Query $query, array $options)
    {
        $academicPeriodId = $options['academic_period_id'];
        $institutionId = $options['institution_id'];
        $classId = $options['institution_class_id'];
        $educationGradeId = $options['education_grade_id'];
        $reportCardId = $options['report_card_id'];
        $educationSubjectId = $options['education_subject_id'];
        $type = $options['type'];

        $Students = $this->Students;
        $StudentStatuses = $this->InstitutionClasses->ClassStudents->StudentStatuses;

        $query
            ->select([
                $this->aliasField('student_id'),
                $Students->aliasField('openemis_no'),
                $Students->aliasField('first_name'),
                $Students->aliasField('middle_name'),
                $Students->aliasField('third_name'),
                $Students->aliasField('last_name'),
                $Students->aliasField('preferred_name'),
                $StudentStatuses->aliasField('name')
            ])
            ->matching('Students')
            ->matching('InstitutionClasses.ClassStudents.StudentStatuses')
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('institution_class_id') => $classId,
                $this->aliasField('education_grade_id') => $educationGradeId,
                $this->aliasField('report_card_id') => $reportCardId
            ])
            ->group([
                $this->aliasField('student_id'),
                $this->aliasField('academic_period_id'),
                $this->aliasField('report_card_id')
            ])
            ->order([
                $Students->aliasField('first_name'), $Students->aliasField('last_name')
            ]);


        if ($type == 'PRINCIPAL') {
            $query->select(['comments' => $this->aliasfield('principal_comments')]);

        } else if ($type == 'HOMEROOM_TEACHER') {
            $query->select(['comments' => $this->aliasfield('homeroom_teacher_comments')]);

        } else if ($type == 'TEACHER') {
            $ReportCardsComments = TableRegistry::get('Institution.InstitutionStudentsReportCardsComments');

            $query
                ->select([
                    'comments' => $ReportCardsComments->aliasField('comments'),
                    'comment_code' => $ReportCardsComments->aliasField('report_card_comment_code_id'),
                ])
                ->leftJoin([$ReportCardsComments->alias() => $ReportCardsComments->table()], [
                    $ReportCardsComments->aliasField('report_card_id = ') . $this->aliasField('report_card_id'),
                    $ReportCardsComments->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $ReportCardsComments->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $ReportCardsComments->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                    $ReportCardsComments->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
                    $ReportCardsComments->aliasField('education_subject_id') => $educationSubjectId
                ]);
        }

        return $query;
    }
}
