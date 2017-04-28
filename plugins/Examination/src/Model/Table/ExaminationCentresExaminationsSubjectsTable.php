<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class ExaminationCentresExaminationsSubjectsTable extends ControllerActionTable
{
    private $queryString;
    private $examCentreId;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('ExaminationItems', ['className' => 'Examination.ExaminationItems']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('ExaminationCentresExaminations', [
            'className' => 'Examination.ExaminationCentresExaminations',
            'foreignKey' => ['examination_centre_id', 'examination_id']
        ]);
        $this->belongsToMany('ExaminationCentresExaminationsStudents', [
            'className' => 'Examination.ExaminationCentresExaminationsStudents',
            'joinTable' => 'examination_centres_examinations_subjects_students',
            'foreignKey' => ['examination_centre_id', 'examination_item_id'],
            'targetForeignKey' => ['examination_centre_id', 'examination_id', 'student_id'],
            'through' => 'Examination.ExaminationCentresExaminationsSubjectsStudents',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('CompositeKey');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'ExamResults' => ['index']
        ]);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('search', false);
        $this->toggle('remove', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        return $events;
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
        $this->queryString = $request->query['queryString'];
        $indexUrl = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentres'];
        $overviewUrl = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentres', 'view', 'queryString' => $this->queryString];

        $Navigation->substituteCrumb('Examination', 'Examination', $indexUrl);
        $Navigation->substituteCrumb('Exam Centre Subjects', 'Exam Centres', $overviewUrl);
        $Navigation->addCrumb('Subjects');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getExamCentresTab();
        $this->examCentreId = $this->ControllerAction->getQueryString('examination_centre_id');

        // Set the header of the page
        if (isset($this->examCentreId)) {
            $examCentreName = $this->ExaminationCentres->get($this->examCentreId)->name;
            $this->controller->set('contentHeader', $examCentreName. ' - ' .__('Subjects'));
        }

        $this->field('code', ['sort' => ['field' => 'ExaminationItems.code']]);
        $this->field('name', ['sort' => ['field' => 'ExaminationItems.name']]);
        $this->field('education_subject_id', ['sort' => ['field' => 'EducationSubjects.name']]);
        $this->field('examination_date', ['type' => 'date', 'sort' => ['field' => 'ExaminationItems.examination_date']]);
        $this->field('examination_id', ['type' => 'select']);
        $this->setFieldOrder(['code', 'name', 'education_subject_id', 'examination_date', 'examination_id']);
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        if (is_null($this->examCentreId)) {
            $event->stopPropagation();
            $this->Alert->error('general.notExists', ['reset' => 'override']);
            $this->controller->redirect(['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentres', 'index']);
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // set queryString for page refresh
        $this->controller->set('queryString', $this->queryString);

        // Examination filter
        $ExaminationCentresExaminations = $this->ExaminationCentresExaminations;
        $examinationOptions = $this->ExaminationCentresExaminations
            ->find('list', [
                'keyField' => 'examination_id',
                'valueField' => 'examination.code_name'
            ])
            ->contain('Examinations')
            ->where([$ExaminationCentresExaminations->aliasField('examination_centre_id') => $this->examCentreId])
            ->toArray();

        $examinationOptions = ['-1' => '-- '.__('Select Examination').' --'] + $examinationOptions;
        $selectedExamination = !is_null($this->request->query('examination_id')) ? $this->request->query('examination_id') : -1;
        $this->controller->set(compact('examinationOptions', 'selectedExamination'));
        if ($selectedExamination != -1) {
           $where[$this->aliasField('examination_id')] = $selectedExamination;
        }

        // exam centre controls
        $extra['elements']['controls'] = ['name' => 'Examination.ExaminationCentres/controls', 'data' => [], 'options' => [], 'order' => 1];

        $where[$this->aliasField('examination_centre_id')] = $this->examCentreId;
        $extra['auto_contain_fields'] = ['ExaminationItems' => ['code', 'examination_date']];

        $query
            ->contain('EducationSubjects')
            ->where([$where]);

        // sorting columns
        $sortList = ['ExaminationItems.code', 'ExaminationItems.name', 'ExaminationItems.examination_date', 'EducationSubjects.name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('weight');
        $this->field('start_time', ['type' => 'time']);
        $this->field('end_time', ['type' => 'time']);
        $this->field('examination_grading_type_id');
        $this->setFieldOrder(['code', 'name', 'education_subject_id',  'examination_date', 'start_time', 'end_time', 'weight', 'examination_grading_type_id', 'examination_id']);
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain('ExaminationItems.ExaminationGradingTypes');
    }

    public function onGetName(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('examination_item')) {
            $value = $entity->examination_item->name;
        }

        return $value;
    }

    public function onGetCode(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('examination_item')) {
            $value = $entity->examination_item->code;
        }

        return $value;
    }

    public function onGetExaminationDate(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('examination_item')) {
            $value = $entity->examination_item->examination_date;
        }

        return $value;
    }

    public function onGetWeight(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('examination_item')) {
            $value = $entity->examination_item->weight;
        }

        return $value;
    }

    public function onGetStartTime(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('examination_item')) {
            $value = $entity->examination_item->start_time;
        }

        return $value;
    }

    public function onGetEndTime(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('examination_item')) {
            $value = $entity->examination_item->end_time;
        }

        return $value;
    }

    public function onGetExaminationGradingTypeId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('examination_item') && $entity->examination_item->has('examination_grading_type')) {
            $value = $entity->examination_item->examination_grading_type->code_name;
        }

        return $value;
    }

    public function onGetEducationSubjectId(Event $event, Entity $entity)
    {
        $value = ' ';
        if ($entity->has('education_subject')) {
            $value = $entity->education_subject->name;
        }

        return $value;
    }

    public function getExaminationCentreSubjects($examinationCentreId, $examinationId)
    {
        $subjectList = $this
            ->find('list', [
                'keyField' => 'examination_item_id',
                'valueField' => 'education_subject_id'
            ])
            ->where([
                $this->aliasField('examination_centre_id') => $examinationCentreId,
                $this->aliasField('examination_id') => $examinationId
            ])
            ->toArray();
        return $subjectList;
    }
}
