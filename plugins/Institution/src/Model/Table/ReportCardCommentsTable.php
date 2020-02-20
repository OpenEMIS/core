<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class ReportCardCommentsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_classes');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);

        //$this->belongsTo('SecondaryStaff', ['className' => 'User.Users', 'foreignKey' => 'secondary_staff_id']);

        $this->hasMany('ClassesSecondaryStaff', ['className' => 'Institution.InstitutionClassesSecondaryStaff', 'saveStrategy' => 'replace', 'foreignKey' => 'institution_class_id']);

        $this->belongsTo('InstitutionShifts', ['className' => 'Institution.InstitutionShifts']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->hasMany('ClassGrades', ['className' => 'Institution.InstitutionClassGrades']);
        $this->hasMany('ClassStudents', ['className' => 'Institution.InstitutionClassStudents']);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['class_number']['visible'] = false;
        $this->fields['institution_shift_id']['visible'] = false;
        $this->fields['staff_id']['visible'] = false;
        $this->fields['capacity']['visible'] = false;

        $this->field('subjects', ['type' => 'integer']);
        $this->field('report_card');
        $this->field('education_grade');
        $this->setFieldOrder(['name', 'report_card', 'academic_period_id', 'education_grade', 'subjects', 'total_male_students', 'total_female_students']);
    }

     public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
     {
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $ClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
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
        if (!empty($availableGrades)) {
            $reportCardOptions = $ReportCards->find('list')
                ->where([
                    $ReportCards->aliasField('academic_period_id') => $selectedAcademicPeriod,
                    $ReportCards->aliasField('education_grade_id IN ') => $availableGrades,
                    // only show record if at least one comment type is needed
                    'OR' => [
                        $ReportCards->aliasField('principal_comments_required') => 1,
                        $ReportCards->aliasField('homeroom_teacher_comments_required') => 1,
                        $ReportCards->aliasField('teacher_comments_required') => 1
                    ]
                ])
                ->toArray();
            $reportCardOptions = ['0' => __('All Report Cards')] + $reportCardOptions;
            $selectedReportCard = !is_null($this->request->query('report_card_id')) ? $this->request->query('report_card_id') : 0;
            $this->controller->set(compact('reportCardOptions', 'selectedReportCard'));
            if (!empty($selectedReportCard)) {
                 $where[$ReportCards->aliasField('id')] = $selectedReportCard;
            }
        } else {
            $this->Alert->warning('ReportCardComments.noProgrammes');
        }
        //End

        $query
            ->select([
                'name' => $this->aliasField('name'),
                'total_male_students' => $this->aliasField('total_male_students'),
                'total_female_students' => $this->aliasField('total_female_students'),
                'institution_class_id' => $ClassGrades->aliasField('institution_class_id'),
                'institution_id' => $this->aliasField('institution_id'),
                'education_grade_id' => $ReportCards->aliasField('education_grade_id'),
                'academic_period_id' => $ReportCards->aliasField('academic_period_id'),
                'report_card_id' => $ReportCards->aliasField('id'),
                'report_card' => $query->func()->concat([
                    $ReportCards->aliasField('code') => 'literal',
                    " - ",
                    $ReportCards->aliasField('name') => 'literal'
                ])
            ])
            ->innerJoin(
                [$ClassGrades->alias() => $ClassGrades->table()],
                [$ClassGrades->aliasField('institution_class_id = ') . $this->aliasField('id')]
            )
            ->innerJoin(
                [$ReportCards->alias() => $ReportCards->table()],
                [
                    $ReportCards->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                    $ReportCards->aliasField('education_grade_id = ') . $ClassGrades->aliasField('education_grade_id')
                ]
            )
            ->innerJoin(
                [$EducationGrades->alias() => $EducationGrades->table()],
                [$EducationGrades->aliasField('id = ') . $ReportCards->aliasField('education_grade_id')]
            )
            ->innerJoin(
                [$EducationProgrammes->alias() => $EducationProgrammes->table()],
                [$EducationProgrammes->aliasField('id = ') . $EducationGrades->aliasField('education_programme_id')]
            )
            ->where([
                $where,
                // only show record if at least one comment type is needed
                'OR' => [
                    $ReportCards->aliasField('principal_comments_required') => 1,
                    $ReportCards->aliasField('homeroom_teacher_comments_required') => 1,
                    $ReportCards->aliasField('teacher_comments_required') => 1
                ]
            ])
            ->group([
                $ClassGrades->aliasField('institution_class_id'),
                $ReportCards->aliasField('id')
            ]);

        if (is_null($this->request->query('sort'))) {
            $query->order([
                $EducationProgrammes->aliasField('order'),
                $EducationGrades->aliasField('order'),
                $this->aliasField('name'),
                $ReportCards->aliasField('code'),
                $ReportCards->aliasField('name')
            ]);
        }

        $extra['elements']['controls'] = ['name' => 'Institution.ReportCards/controls', 'data' => [], 'options' => [], 'order' => 1];
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'name') {
            return __('Class Name');
        } else if ($field == 'total_male_students') {
            return  __('Male Students');
        } else if ($field == 'total_female_students') {
            return  __('Female Students');
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


    public function onGetSubjects(Event $event, Entity $entity)
    {
        $ReportCardSubjects = TableRegistry::get('ReportCard.ReportCardSubjects');
        $count = $ReportCardSubjects
            ->find('matchingClassSubjects', [
                'report_card_id' => $entity->report_card_id,
                'institution_class_id' => $entity->institution_class_id
            ])
            ->count();
        return $count;
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        if (isset($buttons['view']['url'])) {
            $url = [
                'plugin' => $this->controller->plugin,
                'controller' => $this->controller->name,
                'action' => 'Comments'
            ];

            $params = [
                'institution_class_id' => $entity->institution_class_id,
                'report_card_id' => $entity->report_card_id,
                'institution_id' => $entity->institution_id
            ];

            $buttons['view']['url'] = $this->setQueryString($url, $params);
        }

        return $buttons;
    }
}
