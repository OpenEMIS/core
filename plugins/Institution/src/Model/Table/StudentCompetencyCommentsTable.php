<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use App\Model\Table\ControllerActionTable;

class StudentCompetencyCommentsTable extends ControllerActionTable
{
    private $classId = null;
    private $institutionId = null;
    private $academicPeriodId = null;
    private $competencyTemplateId = null;

    public function initialize(array $config)
    {
        $this->table('institution_classes');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('InstitutionShifts', ['className' => 'Institution.InstitutionShifts']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);

        $this->hasMany('ClassGrades', ['className' => 'Institution.InstitutionClassGrades', 'dependent' => true]);
        $this->hasMany('ClassStudents', ['className' => 'Institution.InstitutionClassStudents', 'dependent' => true]);
        $this->hasMany('SubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents', 'dependent' => true]);

        $this->belongsToMany('EducationGrades', [
            'className' => 'Education.EducationGrades',
            'through' => 'Institution.InstitutionClassGrades',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'education_grade_id',
            'dependent' => true
        ]);
        $this->belongsToMany('Students', [
            'className' => 'User.Users',
            'through' => 'Institution.InstitutionClassStudents',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'student_id',
        ]);
        $this->belongsToMany('InstitutionSubjects', [
            'className' => 'Institution.InstitutionSubjects',
            'through' => 'Institution.InstitutionClassSubjects',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'institution_subject_id'
        ]);

        $this->toggle('index', false);
        $this->toggle('add', false);
        $this->toggle('remove', false);
        $this->toggle('search', false);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        // set breadcrumbs
        $session = $this->request->session();
        $institutionId = !empty($this->request->param('institutionId')) ? $this->ControllerAction->paramsDecode($this->request->param('institutionId'))['id'] : $session->read('Institution.Institutions.id');
        $indexUrl = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'StudentCompetencies',
            'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId])
        ];
        $oldCrumbTitle = Inflector::humanize(Inflector::underscore($this->request->param('action')));
        $this->Navigation->substituteCrumb($oldCrumbTitle, 'Student Competencies', $indexUrl);

        $this->classId = $this->getQueryString('class_id');
        $this->institutionId = $this->getQueryString('institution_id');
        $this->academicPeriodId = $this->getQueryString('academic_period_id');
        $this->competencyTemplateId = $this->getQueryString('competency_template_id');

        $this->field('name');
        $this->field('academic_period_id');
        $this->field('competency_template');
        $this->field('students', ['type' => 'custom_students']);
        $this->field('class_number', ['visible' => false]);
        $this->field('staff_id', ['type' => 'hidden']);
        $this->field('institution_shift_id', ['type' => 'hidden']);
        $this->field('capacity', ['type' => 'hidden']);
        $this->field('modified_user_id', ['type' => 'hidden']);
        $this->field('modified', ['type' => 'hidden']);
        $this->field('created_user_id', ['type' => 'hidden']);
        $this->field('created', ['type' => 'hidden']);
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        // set up tabs
        $tabElements = $this->controller->getCompetencyTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'StudentCompetencyComments');

        if (isset($extra['toolbarButtons']['back'])) {
            $extra['toolbarButtons']['back']['url']['action'] = 'StudentCompetencies';
        }
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain(['AcademicPeriods'])
            ->where([
                $this->aliasField('id') => $this->classId,
                $this->aliasField('institution_id') => $this->institutionId,
                $this->aliasField('academic_period_id') => $this->academicPeriodId
            ]);
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'name') {
            return __('Class Name');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetCompetencyTemplate(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            $CompetencyTemplates = TableRegistry::get('Competency.CompetencyTemplates');
            $competencyEntity = $CompetencyTemplates->find()
                ->where([
                    $CompetencyTemplates->aliasField('id') => $this->competencyTemplateId,
                    $CompetencyTemplates->aliasField('academic_period_id') => $this->academicPeriodId
                ])
                ->first();
            return $competencyEntity->code_name;
        }
    }

    public function onGetCustomStudentsElement(Event $event, $action, $entity, $attr, $options = [])
    {
        $tableHeaders = [__('OpenEMIS ID'), __('Student Name'), __('Student Status')];
        $tableCells = [];
        $colOffset = 3; // 0 -> OpenEMIS ID, 1 -> Student Name, 2 -> Student Status

        $CompetencyPeriods = TableRegistry::get('Competency.CompetencyPeriods');
        $competencyPeriodEntity = $CompetencyPeriods->find()
            ->where([
                $CompetencyPeriods->aliasField('academic_period_id') => $this->academicPeriodId,
                $CompetencyPeriods->aliasField('competency_template_id') => $this->competencyTemplateId
            ])
            ->toArray();
        foreach ($competencyPeriodEntity as $colKey => $period) {
            $tableHeaders[$colKey + $colOffset] = $period['name'];
        }

        if (!is_null($this->classId)) {
            $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
            $PeriodComments = TableRegistry::get('Institution.InstitutionCompetencyPeriodComments');
            $Users = $ClassStudents->Users;
            $StudentStatuses = $ClassStudents->StudentStatuses;

            $students = $ClassStudents->find()
                ->select([
                    $PeriodComments->aliasField('comments'),
                    $PeriodComments->aliasField('competency_period_id'),
                    $ClassStudents->aliasField('student_id'),
                    $ClassStudents->aliasField('student_status_id'),
                    $StudentStatuses->aliasField('name'),
                    $Users->aliasField('openemis_no'),
                    $Users->aliasField('first_name'),
                    $Users->aliasField('middle_name'),
                    $Users->aliasField('third_name'),
                    $Users->aliasField('last_name'),
                    $Users->aliasField('preferred_name')
                ])
                ->matching('Users')
                ->matching('StudentStatuses')
                ->leftJoin(
                    [$PeriodComments->alias() => $PeriodComments->table()],
                    [
                        $PeriodComments->aliasField('student_id = ') . $ClassStudents->aliasField('student_id'),
                        $PeriodComments->aliasField('institution_id') => $this->institutionId,
                        $PeriodComments->aliasField('academic_period_id') => $this->academicPeriodId,
                        $PeriodComments->aliasField('competency_template_id') => $this->competencyTemplateId
                    ]
                )
                ->where([$ClassStudents->aliasField('institution_class_id') => $this->classId])
                ->order([
                    $Users->aliasField('first_name'),
                    $Users->aliasField('last_name')
                ])
                ->toArray();

            $studentId = null;
            $currentStudentId = null;
            $resultObj = null;
            $rowData = [];
            $rowCount = 0;

            foreach ($students as $rowKey => $studentObj) {
                $currentStudentId = $studentObj->student_id;
                $savedPeriodId = $studentObj->{$PeriodComments->alias()}['competency_period_id'];
                $savedComments = $studentObj->{$PeriodComments->alias()}['comments'];
                if (!is_null($savedComments)) {
                    $resultObj[$currentStudentId][$savedPeriodId] = $savedComments;
                }

                $userObj = $studentObj->_matchingData['Users'];
                $studentStatusObj = $studentObj->_matchingData['StudentStatuses'];

                if ($studentId != $currentStudentId) {
                    if ($studentId != null) {
                        $tableCells[$rowCount] = $rowData;
                        $rowCount++;
                    }

                    $rowData = [];
                    $rowData[] = $userObj->openemis_no;
                    $rowData[] = $userObj->name;
                    $rowData[] = $studentStatusObj->name;

                    $studentId = $currentStudentId;
                }

                $cellValue = '';
                if (!is_null($competencyPeriodEntity)) {
                    foreach ($competencyPeriodEntity as $colKey => $periodObj) {
                        $competencyPeriodId = $periodObj['id'];
                        $cellValue = '';
                        if (isset($resultObj[$currentStudentId][$competencyPeriodId]) && !is_null($resultObj[$currentStudentId][$competencyPeriodId])) {
                            $cellValue = $resultObj[$currentStudentId][$competencyPeriodId];
                        }
                        $rowData[$colKey+$colOffset] = $cellValue;
                    }
                }
            }

            if (!empty($rowData)) {
                $tableCells[$rowCount] = $rowData;
            }
        }

        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;
        return $event->subject()->renderElement('Institution.StudentCompetencies/students', ['attr' => $attr]);
    }
}
