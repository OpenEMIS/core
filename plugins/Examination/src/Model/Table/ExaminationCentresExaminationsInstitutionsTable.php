<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Utility\Security;
use App\Model\Table\ControllerActionTable;

class ExaminationCentresExaminationsInstitutionsTable extends ControllerActionTable
{
    private $queryString;
    private $examCentreId;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('ExaminationCentresExaminations', [
            'className' => 'Examination.ExaminationCentresExaminations',
            'foreignKey' => ['examination_centre_id', 'examination_id']
        ]);

        $this->addBehavior('CompositeKey');
        $this->toggle('edit', false);
        $this->toggle('search', false);
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);
        return $validator
            ->requirePresence('institutions');
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
        $Navigation->substituteCrumb('Exam Centre Linked Institutions', 'Exam Centres', $overviewUrl);
        $Navigation->addCrumb('Linked Institutions');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getExamCentresTab();
        $this->examCentreId = $this->ControllerAction->getQueryString('examination_centre_id');

        // Set the header of the page
        $examCentreName = $this->ExaminationCentres->get($this->examCentreId)->name;
        $this->controller->set('contentHeader', $examCentreName. ' - ' .__('Linked Institutions'));

        $this->fields['examination_id']['type'] = 'integer';
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        if (is_null($this->examCentreId)) {
            $event->stopPropagation();
            $this->Alert->error('general.notExists', ['reset' => 'override']);
            $this->controller->redirect(['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentres', 'index']);
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('linked_institution', ['sort' => ['field' => 'Institutions.name']]);
        $this->setFieldOrder(['linked_institution', 'examination_id']);
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
        $extra['auto_contain_fields'] = ['Institutions' => ['code']];
        $query->where([$where]);

        // sort
        $sortList = ['Institutions.name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('linked_institution', ['type' => 'integer']);
        $this->setFieldOrder(['linked_institution', 'examination_id']);
    }

    public function onGetLinkedInstitution(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('institution')) {
            $value = $entity->institution->code_name;
        }

        return $value;
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id');
        $this->field('institutions');
        $this->field('examination_centre_id');
        $this->field('examination_id');
        $this->setFieldOrder(['academic_period_id', 'examination_centre_id', 'examination_id', 'institutions']);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        $examCentre = $this->ExaminationCentres->get($this->examCentreId, ['contain' => ['AcademicPeriods']]);
        $academicPeriodId = $examCentre->academic_period->name;

        $attr['type'] = 'readonly';
        $attr['attr']['value'] = $academicPeriodId;
        return $attr;
    }

    public function onUpdateFieldExaminationCentreId(Event $event, array $attr, $action, Request $request)
    {
        $examCentre = $this->ExaminationCentres->get($this->examCentreId)->code_name;
        $attr['type'] = 'readonly';
        $attr['attr']['value'] = $examCentre;
        return $attr;
    }

    public function onUpdateFieldExaminationId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $examinationOptions = $this->ExaminationCentresExaminations
                ->find('list', [
                    'keyField' => 'examination_id',
                    'valueField' => 'examination.code_name'
                ])
                ->contain('Examinations')
                ->where([$this->ExaminationCentresExaminations->aliasField('examination_centre_id') => $this->examCentreId])
                ->toArray();

            $attr['options'] = $examinationOptions;
            $attr['onChangeReload'] = true;
            $attr['type'] = 'select';
            return $attr;
        }
    }

    public function onUpdateFieldInstitutions(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $institutionOptions = $this->Institutions
                ->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'code_name'
                ]);

            if (!empty($request->data[$this->alias()]['examination_id'])) {
                $Institutions = TableRegistry::get('Institution.Institutions');
                $examinationId = $request->data[$this->alias()]['examination_id'];
                $educationGradeId = $this->Examinations->get($examinationId)->education_grade_id;

                $institutionOptions
                    ->innerJoinWith('InstitutionGrades', function ($q) use ($educationGradeId) {
                        return $q->where(['InstitutionGrades.education_grade_id' => $educationGradeId]);
                    })
                    ->notMatching('ExaminationCentresExaminations', function ($q) use ($examinationId) {
                        return $q->where([
                            'ExaminationCentresExaminations.examination_id' => $examinationId,
                            'ExaminationCentresExaminations.examination_centre_id' => $this->examCentreId
                        ]);
                    })
                    ->where([$this->Institutions->aliasField('classification') => $Institutions::ACADEMIC]);
            }

            $attr['options'] = $institutionOptions->toArray();
            $attr['type'] = 'chosenSelect';
            $attr['fieldName'] = $this->alias().'.institutions';
            return $attr;
        }
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        $requestData[$this->alias()]['institution_id'] = 0;
        $requestData[$this->alias()]['examination_centre_id'] = $this->examCentreId;
    }

    public function addBeforeSave(Event $event, $entity, $requestData, $extra)
    {
        $process = function ($model, $entity) use ($requestData) {
            if (isset($requestData[$model->alias()]['institutions']) && !empty($requestData[$model->alias()]['institutions'])) {
                $institutions = $requestData[$model->alias()]['institutions'];
                $newEntities = [];
                if (is_array($institutions)) {
                    foreach ($institutions as $institutionId) {
                        $requestData[$model->alias()]['institution_id'] = $institutionId;
                        $newEntities[] = $model->newEntity($requestData->getArrayCopy());
                    }
                }

                return $model->saveMany($newEntities);
            }
        };

        return $process;
    }
}
