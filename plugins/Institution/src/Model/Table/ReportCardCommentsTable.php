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

        $this->field('male_students', ['type' => 'integer']);
        $this->field('female_students', ['type' => 'integer']);
        $this->field('report_card');
        // $this->field('education_grade_id');
        $this->field('subjects');
        $this->setFieldOrder(['academic_period_id', 'name', 'report_card', 'education_grade_id', 'subjects', 'male_students', 'female_students']);
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

        $ReportCards = TableRegistry::get('ReportCards.ReportCards');
        $reportCardOptions = $ReportCards->find('list')
            ->where([
                $ReportCards->aliasField('academic_period_id') => $selectedAcademicPeriod,
                $ReportCards->aliasField('education_grade_id IN ') => $availableGrades
            ])
            ->toArray();
        $reportCardOptions = ['0' => __('All Report Cards')] + $reportCardOptions;
        $selectedReportCard = !is_null($this->request->query('report_card_id')) ? $this->request->query('report_card_id') : 0;
        $this->controller->set(compact('reportCardOptions', 'selectedReportCard'));
        if ($selectedReportCard != 0) {
             $where[$ReportCards->aliasField('id')] = $selectedReportCard;
        }
        //End

        $query
            ->select([
                'report_card_id' => 'ReportCards.id',
                'report_card_code' => 'ReportCards.code',
                'report_card_name' => 'ReportCards.name',
                'report_card_education_grade' => 'ReportCards.education_grade_id'
            ])
            ->innerJoin([$this->ClassGrades->alias() => $this->ClassGrades->table()], [
                $this->ClassGrades->aliasField('institution_class_id = ') . $this->aliasField('id')
            ])
            ->innerJoin([$ReportCards->alias() => $ReportCards->table()], [
                $ReportCards->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                $ReportCards->aliasField('education_grade_id = ') . $this->ClassGrades->aliasField('education_grade_id')
            ])
            ->autoFields(true)
            ->where($where);
    }

    public function onGetReportCard(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('report_card_code') && $entity->has('report_card_name')) {
            $value = $entity->report_card_code . ' - ' . $entity->report_card_name;
        }
        return $value;
    }

    public function onGetFemaleStudents(Event $event, Entity $entity)
    {
        $gender_id = 2; // female
        $ClassStudents = $this->ClassStudents;
        $count = $ClassStudents
            ->find()
            ->contain('Users')
            ->where([
                'Users.gender_id' => $gender_id,
                $ClassStudents->aliasField('institution_class_id') => $entity->id,
                $ClassStudents->aliasField('education_grade_id') => $entity->report_card_education_grade,
                $ClassStudents->aliasField('student_status_id') .' > 0'
            ])
            ->count();
        return $count;
    }

    public function onGetMaleStudents(Event $event, Entity $entity)
    {
        $gender_id = 1; // male
        $ClassStudents = $this->ClassStudents;
        $count = $ClassStudents
            ->find()
            ->contain('Users')
            ->where([
                'Users.gender_id' => $gender_id,
                $ClassStudents->aliasField('institution_class_id') => $entity->id,
                $ClassStudents->aliasField('education_grade_id') => $entity->report_card_education_grade,
                $ClassStudents->aliasField('student_status_id') .' > 0'
            ])
            ->count();
        return $count;
    }

    public function onGetSubjects(Event $event, Entity $entity)
    {
        $ReportCardSubjects = TableRegistry::get('ReportCards.ReportCardSubjects');
        $subjectCount = $ReportCardSubjects->find()
            ->where([$ReportCardSubjects->aliasField('report_card_id') => $entity->report_card_id])
            ->count();
        return $subjectCount;
    }
}
