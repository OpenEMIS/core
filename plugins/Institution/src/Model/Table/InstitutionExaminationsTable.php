<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class InstitutionExaminationsTable extends ControllerActionTable {
    public function initialize(array $config) {
        $this->table('examinations');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->hasMany('ExaminationItems', ['className' => 'Examination.ExaminationItems', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra) {
        $extra['elements']['controls'] = ['name' => 'Institution.Examinations/controls', 'data' => [], 'options' => [], 'order' => 1];

        $this->field('description', ['visible' => 'hidden']);
        $this->setFieldOrder(['academic_period_id', 'code', 'name', 'education_grade_id']);
    }

     public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
        $institutionId = $this->Session->read('Institution.Institutions.id');

        // only show examinations for the education programmes available in the insitution
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $educationProgrammes = $InstitutionGrades->find('list', [
                    'keyField' => 'education_grade_id',
                    'valueField' => 'education_grade_id'
                ])
            ->where([$InstitutionGrades->aliasField('institution_id') => $institutionId])
            ->toArray();

        $query->where([$this->aliasField('education_grade_id IN') => $educationProgrammes]);

        // Academic Periods filter
        $periodOptions = $this->AcademicPeriods->getYearList(['withLevels' => true, 'isEditable' => true]);
        if (is_null($this->request->query('academic_period_id'))) {
            $this->request->query['academic_period_id'] = $this->AcademicPeriods->getCurrent();
        }
        $selectedPeriod = $this->queryString('academic_period_id', $periodOptions);

        $this->controller->set(compact('periodOptions', 'selectedPeriod'));

        if (!empty($selectedPeriod)) {
            $query->where([$this->aliasField('academic_period_id') => $selectedPeriod]);

            // Examination filter
            $examinationOptions = $this
                ->find('list')
                ->where([$this->aliasField('academic_period_id') => $selectedPeriod,
                    $this->aliasField('education_grade_id IN') => $educationProgrammes])
                ->toArray();
            $examinationOptions = ['-1' => __('All Examinations')] + $examinationOptions;
            $selectedExamination = $this->queryString('examination_id', $examinationOptions);

            $this->controller->set(compact('examinationOptions', 'selectedExamination'));

            if ($selectedExamination != '-1') {
                $query->where([$this->aliasField('id') => $selectedExamination]);
            }
        }
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
        $query->contain(['ExaminationItems.EducationSubjects']);
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra) {
        $this->field('description', ['visible' => 'hidden']);
        $this->field('examination_items', [
            'type' => 'element',
            'element' => 'Examination.examination_items'
        ]);

        $this->setFieldOrder(['academic_period_id', 'code', 'name', 'description', 'education_grade_id', 'examination_items']);

        $this->controller->set('examinationGradingTypeOptions', $this->getGradingTypeOptions()); //send to ctp
    }

    public function getGradingTypeOptions()
    {
        $examinationGradingType = TableRegistry::get('Examination.ExaminationGradingTypes');
        $examinationGradingTypeOptions = $examinationGradingType->find('list')->toArray();
        return $examinationGradingTypeOptions;
    }
}
