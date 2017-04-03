<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class ExaminationCentresExaminationsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsToMany('ExaminationItems', [
            'className' => 'Examination.ExaminationItems',
            'joinTable' => 'examination_centres_examinations_subjects',
            'foreignKey' => ['examination_centre_id', 'examination_id'],
            'targetForeignKey' => 'examination_item_id',
            'through' => 'Examination.ExaminationCentresExaminationsSubjects',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->belongsToMany('LinkedInstitutions', [
            'className' => 'Institution.Institutions',
            'joinTable' => 'examination_centres_examinations_institutions',
            'foreignKey' => ['examination_centre_id', 'examination_id'],
            'targetForeignKey' => 'institution_id',
            'through' => 'Examination.ExaminationCentresExaminationsInstitutions',
            'dependent' => true
        ]);
        $this->belongsToMany('Invigilators', [
            'className' => 'User.Users',
            'joinTable' => 'examination_centres_examinations_invigilators',
            'foreignKey' => ['examination_centre_id', 'examination_id'],
            'targetForeignKey' => 'invigilator_id',
            'through' => 'Examination.ExaminationCentresExaminationsInvigilators',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->belongsToMany('Students', [
            'className' => 'User.Users',
            'joinTable' => 'examination_centre_examinations_students',
            'foreignKey' => ['examination_centre_id', 'examination_id'],
            'targetForeignKey' => 'student_id',
            'through' => 'Examination.ExaminationCentreStudents',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('CompositeKey');
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // Academic period filter
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;

        // Examination filter
        $examinationOptions = $this->getExaminationOptions($selectedAcademicPeriod);
        $examinationOptions = ['-1' => '-- '.__('Select Examination').' --'] + $examinationOptions;
        $selectedExamination = !is_null($this->request->query('examination_id')) ? $this->request->query('examination_id') : -1;
        $this->controller->set(compact('examinationOptions', 'selectedExamination'));
        if ($selectedExamination != -1) {
           $where[$this->aliasField('examination_id')] = $selectedExamination;
        }

        $extra['elements']['controls'] = ['name' => 'Examination.controls', 'data' => [], 'options' => [], 'order' => 1];
        $extra['auto_contain_fields'] = ['ExaminationCentres' => ['code']];
        $query->where($where);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $params = [
            'examination_centre_id' => $entity->examination_centre->id,
            'examination_id' => $entity->examination_id
        ];

        if (isset($buttons['view']['url'])) {
            $buttons['view']['url']['action'] = 'Centres';
            $buttons['view']['url'] = $this->ControllerAction->setQueryString($buttons['view']['url'], $params);
        }

        if (isset($buttons['edit']['url'])) {
            $buttons['edit']['url']['action'] = 'Centres';
            $buttons['edit']['url'] = $this->ControllerAction->setQueryString($buttons['edit']['url'], $params);
        }

        return $buttons;
    }


    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('examination_centre_id', ['type' => 'select']);
        $this->field('examination_id', ['type' => 'select']);
        $this->setFieldOrder(['examination_centre_id', 'academic_period_id', 'examination_id', 'total_registered']);
    }

    public function onGetExaminationCentreId(Event $event, Entity $entity)
    {
        return $entity->examination_centre->code_name;
    }

    private function getExaminationOptions($selectedAcademicPeriod)
    {
        $examinationOptions = $this->Examinations
            ->find('list')
            ->where([$this->Examinations->aliasField('academic_period_id') => $selectedAcademicPeriod])
            ->toArray();

        return $examinationOptions;
    }
}
