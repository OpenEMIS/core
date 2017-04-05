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
        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->requirePresence('examination_centres', 'create');

        return $validator;
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
        $this->field('examination_id', ['type' => 'select']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        // link examinations button
        $linkExamButton = $extra['toolbarButtons']['add'];
        $linkExamButton['attr']['title'] = __('Link Examination');
        $linkExamButton['label'] = '<i class="fa fa-link"></i>';
        $extra['toolbarButtons']['linkExam'] = $linkExamButton;

        // add examination centre button
        if (isset($extra['toolbarButtons']['add'])) {
            $extra['toolbarButtons']['add']['url']['action'] = 'Centres';
            $extra['toolbarButtons']['add']['attr']['title'] = __('Add Examination Centre');
        }

        $this->fields['examination_centre_id']['type'] = 'string';
        $this->setFieldOrder(['examination_centre_id', 'academic_period_id', 'examination_id', 'total_registered']);
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

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id');
        $this->field('examination_centres');
        $this->fields['total_registered']['visible'] = false;
        $this->setFieldOrder(['academic_period_id', 'examination_id', 'examination_centres']);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $attr['onChangeReload'] = true;
        $attr['default'] = $this->AcademicPeriods->getCurrent();
        $attr['type'] = 'select';
        return $attr;
    }

    public function onUpdateFieldExaminationId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            if (isset($request->data[$this->alias()]['academic_period_id'])) {
                $academicPeriodId = $request->data[$this->alias()]['academic_period_id'];
                $examOptions = $this->getExaminationOptions($academicPeriodId);
                $attr['options'] = $examOptions;
            }

            $attr['onChangeReload'] = true;
            return $attr;
        }
    }

    public function onUpdateFieldExaminationCentres(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $examinationId = isset($request->data[$this->alias()]['examination_id']) ? $request->data[$this->alias()]['examination_id'] : 0;

            $examCentreOptions = $this->ExaminationCentres
                ->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'code_name'
                ])
                ->notMatching('Examinations', function ($q) use ($examinationId) {
                    return $q->where(['Examinations.id' => $examinationId]);
                })
                ->order([$this->ExaminationCentres->aliasField('code')])
                ->toArray();

            $attr['type'] = 'chosenSelect';
            $attr['options'] = $examCentreOptions;
            return $attr;
        }
    }

    public function onGetExaminationCentreId(Event $event, Entity $entity)
    {
        return $entity->examination_centre->code_name;
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        $requestData[$this->alias()]['examination_centre_id'] = 0;
    }

    public function addBeforeSave(Event $event, $entity, $requestData, $extra)
    {
        $process = function ($model, $entity) use ($requestData) {
            if (array_key_exists('examination_centres', $requestData[$this->alias()])) {
                if (is_array($requestData[$this->alias()]['examination_centres']['_ids'])) {
                    $examCentreIds = $requestData[$model->alias()]['examination_centres']['_ids'];
                    $newEntities = [];
                    foreach ($examCentreIds as $centreId) {
                        $requestData[$model->alias()]['examination_centre_id'] = $centreId;
                        $newEntities[] = $model->newEntity($requestData->getArrayCopy());
                    }
                    return $model->saveMany($newEntities);
                }
            }
        };

        return $process;
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
