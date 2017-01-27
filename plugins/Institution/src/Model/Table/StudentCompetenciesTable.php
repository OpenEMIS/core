<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class StudentCompetenciesTable extends ControllerActionTable {
    public function initialize(array $config) {
        $this->table('institution_classes');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('InstitutionShifts', ['className' => 'Institution.InstitutionShifts']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);

        $this->hasMany('ClassGrades', ['className' => 'Institution.InstitutionClassGrades', 'dependent' => true]);
        $this->hasMany('ClassStudents', ['className' => 'Institution.InstitutionClassStudents', 'dependent' => true]);
        $this->hasMany('SubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents', 'dependent' => true]);

        $this->behaviors()->get('ControllerAction')->config('actions.add', false);
        $this->behaviors()->get('ControllerAction')->config('actions.search', false);
        
        $this->toggle('remove', false);
    }

    public function beforeAction(Event $event, ArrayObject $extra) {
        $this->field('class_number', ['visible' => false]);
        $this->field('staff_id', ['visible' => false]);
        $this->field('institution_shift_id', ['visible' => false]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra) {
        $extra['elements']['controls'] = ['name' => 'Institution.Competencies/controls', 'data' => [], 'options' => [], 'order' => 1];

        $this->field('competency_template');
        $this->field('education_grade');

        $this->setFieldOrder(['name', 'assessment', 'academic_period_id', 'education_grade', 'subjects', 'male_students', 'female_students']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');

        $Classes = TableRegistry::get('Institution.InstitutionClasses');
        $ClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
        $Competencies = TableRegistry::get('Competency.Templates');
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');

        $query
            ->select([
                'institution_class_id' => $ClassGrades->aliasField('institution_class_id'),
                'education_grade_id' => $Competencies->aliasField('education_grade_id'),
                'competency_template_id' => $Competencies->aliasField('id'),
                'competency_template' => $query->func()->concat([
                    $Competencies->aliasField('code') => 'literal',
                    " - ",
                    $Competencies->aliasField('name') => 'literal'
                ])
            ])
            ->innerJoin(
                [$ClassGrades->alias() => $ClassGrades->table()],
                [$ClassGrades->aliasField('institution_class_id = ') . $this->aliasField('id')]
            )
            ->innerJoin(
                [$Competencies->alias() => $Competencies->table()],
                [
                    $Competencies->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                    $Competencies->aliasField('education_grade_id = ') . $ClassGrades->aliasField('education_grade_id')
                ]
            )
            ->innerJoin(
                [$EducationGrades->alias() => $EducationGrades->table()],
                [$EducationGrades->aliasField('id = ') . $Competencies->aliasField('education_grade_id')]
            )
            ->innerJoin(
                [$EducationProgrammes->alias() => $EducationProgrammes->table()],
                [$EducationProgrammes->aliasField('id = ') . $EducationGrades->aliasField('education_programme_id')]
            )
            ->group([
                $ClassGrades->aliasField('institution_class_id'),
                $Competencies->aliasField('id')
            ])
            ->autoFields(true)
            ;

        $extra['options']['order'] = [
            $EducationProgrammes->aliasField('order') => 'asc',
            $EducationGrades->aliasField('order') => 'asc',
            $Competencies->aliasField('code') => 'asc',
            $Competencies->aliasField('name') => 'asc',
            $this->aliasField('name') => 'asc'
        ];

        // Academic Periods
        $periodOptions = $this->AcademicPeriods->getYearList(['withLevels' => true, 'isEditable' => true]);
        if (is_null($this->request->query('period'))) {
            // default to current Academic Period
            $this->request->query['period'] = $this->AcademicPeriods->getCurrent();
        }

        $selectedPeriod = $this->queryString('period', $periodOptions);
        
        $this->controller->set(compact('periodOptions', 'selectedPeriod'));
        // End

        if (!empty($selectedPeriod)) {
            $query->where([$this->aliasField('academic_period_id') => $selectedPeriod]);

            // Competencies
            $competencyOptions = $Competencies
                ->find('list')
                ->where([$Competencies->aliasField('academic_period_id') => $selectedPeriod])
                ->toArray();
            $competencyOptions = ['-1' => __('All Competencies')] + $competencyOptions;
            $selectedCompetency = $this->queryString('competency', $competencyOptions);
            $this->controller->set(compact('competencyOptions', 'selectedCompetency'));

            if ($selectedCompetency != '-1') {
                $query->where([$Competencies->aliasField('id') => $selectedCompetency]);
            }
        }
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true) {
        if ($field == 'name') {
            return __('Class Name');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetEducationGrade(Event $event, Entity $entity) {
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $grade = $EducationGrades->get($entity->education_grade_id);

        return $grade->programme_grade_name;
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (isset($buttons['view']['url'])) {
            $url = [
                'plugin' => $this->controller->plugin,
                'controller' => $this->controller->name,
                'action' => 'StudentCompetencyResults/viewResults'
            ];

            $buttons['view']['url'] = $this->setQueryString($url, [
                'class_id' => $entity->institution_class_id,
                'competency_template_id' => $entity->competency_template_id,
                'institution_id' => $entity->institution_id,
                'academic_period_id' => $entity->academic_period_id
            ]);
        }

        if (isset($buttons['edit']['url'])) {
            $url = [
                'plugin' => $this->controller->plugin,
                'controller' => $this->controller->name,
                'action' => 'StudentCompetencyResults/add'
            ];

            $buttons['edit']['url'] = $this->setQueryString($url, [
                'class_id' => $entity->institution_class_id,
                'competency_template_id' => $entity->competency_template_id,
                'institution_id' => $entity->institution_id,
                'academic_period_id' => $entity->academic_period_id
            ]);
        }

        return $buttons;
    }
}
