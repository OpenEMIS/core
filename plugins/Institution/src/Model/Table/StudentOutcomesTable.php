<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;

class StudentOutcomesTable extends ControllerActionTable
{
    private $classId = null;
    private $institutionId = null;
    private $academicPeriodId = null;
    private $outcomeTemplateId = null;
    private $outcomePeriodId = null;
    private $subjectId = null;
    private $studentId = null;

    public function initialize(array $config)
    {
        $this->table('institution_classes');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('SecondaryStaff', ['className' => 'User.Users', 'foreignKey' => 'secondary_staff_id']);
        $this->belongsTo('InstitutionShifts', ['className' => 'Institution.InstitutionShifts']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);

        $this->hasMany('ClassGrades', ['className' => 'Institution.InstitutionClassGrades']);
        $this->hasMany('ClassStudents', ['className' => 'Institution.InstitutionClassStudents']);
        $this->hasMany('SubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents']);

        $this->belongsToMany('EducationGrades', [
            'className' => 'Education.EducationGrades',
            'through' => 'Institution.InstitutionClassGrades',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'education_grade_id'
        ]);
        $this->belongsToMany('Students', [
            'className' => 'User.Users',
            'through' => 'Institution.InstitutionClassStudents',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'student_id'
        ]);
        $this->belongsToMany('InstitutionSubjects', [
            'className' => 'Institution.InstitutionSubjects',
            'through' => 'Institution.InstitutionClassSubjects',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'institution_subject_id'
        ]);

        $this->toggle('add', false);
        $this->toggle('remove', false);
        $this->toggle('search', false);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $params = [
            'class_id' => $entity->institution_class_id,
            'institution_id' => $entity->institution_id,
            'academic_period_id' => $entity->academic_period_id,
            'outcome_template_id' => $entity->outcome_template_id
        ];

        if (isset($buttons['view']['url'])) {
            $url = $buttons['view']['url'];
            $buttons['view']['url'] = $this->setQueryString($url, $params);
        }

        if (isset($buttons['edit']['url'])) {
            $url = $buttons['edit']['url'];
            unset($url[1]);
            $buttons['edit']['url'] = $this->setQueryString($url, $params);
        }

        return $buttons;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('class_number', ['type' => 'hidden']);
        $this->field('staff_id', ['type' => 'hidden']);
        $this->field('secondary_staff_id', ['type' => 'hidden']);
        $this->field('institution_shift_id', ['type' => 'hidden']);
        $this->field('modified_user_id', ['type' => 'hidden']);
        $this->field('modified', ['type' => 'hidden']);
        $this->field('created_user_id', ['type' => 'hidden']);
        $this->field('created', ['type' => 'hidden']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('outcome_template');
        $this->field('education_grade');

        $this->setFieldOrder(['name', 'academic_period_id', 'education_grade', 'outcome_template', 'total_male_students', 'total_female_students']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $ClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
        $Outcomes = TableRegistry::get('Outcome.OutcomeTemplates');
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');

        $query
            ->select([
                'institution_class_id' => $this->aliasField('id'),
                'education_grade_id' => $Outcomes->aliasField('education_grade_id'),
                'outcome_template_id' => $Outcomes->aliasField('id'),
                'outcome_template' => $query->func()->concat([
                    $Outcomes->aliasField('code') => 'literal',
                    " - ",
                    $Outcomes->aliasField('name') => 'literal',
                ])
            ])
            ->innerJoin([$ClassGrades->alias() => $ClassGrades->table()], [
                $ClassGrades->aliasField('institution_class_id = ') . $this->aliasField('id')
            ])
            ->innerJoin([$Outcomes->alias() => $Outcomes->table()], [
                $Outcomes->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                $Outcomes->aliasField('education_grade_id = ') . $ClassGrades->aliasField('education_grade_id')
            ])
            ->innerJoin([$EducationGrades->alias() => $EducationGrades->table()], [
                $EducationGrades->aliasField('id = ') . $Outcomes->aliasField('education_grade_id')
            ])
            ->innerJoin([$EducationProgrammes->alias() => $EducationProgrammes->table()], [
                $EducationProgrammes->aliasField('id = ') . $EducationGrades->aliasField('education_programme_id')
            ])
            ->group([
                $ClassGrades->aliasField('institution_class_id'),
                $Outcomes->aliasField('id')
            ])
            ->autoFields(true);

        $extra['options']['order'] = [
            $EducationProgrammes->aliasField('order') => 'asc',
            $EducationGrades->aliasField('order') => 'asc',
            $Outcomes->aliasField('code') => 'asc',
            $Outcomes->aliasField('name') => 'asc',
            $this->aliasField('name') => 'asc'
        ];

        // Academic period filter
        $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedPeriod = !is_null($this->request->query('period')) ? $this->request->query('period') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('periodOptions', 'selectedPeriod'));
        $query->where([$this->aliasField('academic_period_id') => $selectedPeriod]);
        // End

        // Outcome template filter
        $outcomeOptions = $Outcomes
            ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
            ->where([$Outcomes->aliasField('academic_period_id') => $selectedPeriod])
            ->toArray();
        if (!empty($outcomeOptions)) {
            $outcomeOptions = ['0' => '-- '.__('All Outcomes').' --'] + $outcomeOptions;
        }

        $selectedOutcome = !is_null($this->request->query('outcome')) ? $this->request->query('outcome') : 0;
        $this->controller->set(compact('outcomeOptions', 'selectedOutcome'));
        if (!empty($selectedOutcome)){
            $query->where([$Outcomes->aliasField('id') => $selectedOutcome]);
        }
        // End

        $extra['elements']['controls'] = ['name' => 'Institution.StudentOutcomes/controls', 'data' => [], 'options' => [], 'order' => 1];
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'name') {
            return __('Class Name');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetEducationGrade(Event $event, Entity $entity)
    {
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $grade = $EducationGrades->get($entity->education_grade_id);

        return $grade->programme_grade_name;
    }
}
