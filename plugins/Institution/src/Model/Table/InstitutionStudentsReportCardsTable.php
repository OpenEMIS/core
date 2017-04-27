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
        $this->toggle('remove', false);
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

        } else if ($type == 'SUBJECT_TEACHER') {
            $ReportCardsComments = TableRegistry::get('Institution.InstitutionStudentsReportCardsComments');

            $query
                ->select(['comments' => $ReportCardsComments->aliasField('comments')])
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
