<?php
namespace Staff\Model\Table;

use ArrayObject;

use Cake\Chronos\Date;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Session;
use Cake\ORM\TableRegistry;

use App\Model\Table\ControllerActionTable;

class AppraisalsTable extends ControllerActionTable {

    public function initialize(array $config) {
        $this->table('staff_appraisals');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('StaffAppraisalTypes', ['className' => 'Staff.StaffAppraisalTypes']);

        $this->belongsToMany('Competencies', [
            'className' => 'Staff.Competencies',
            'joinTable' => 'staff_appraisal_competencies',
            'foreignKey' => 'staff_appraisal_id',
            'targetForeignKey' => 'competency_id',
            'through' => 'Staff.StaffAppraisalsCompetencies',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('AcademicPeriod.AcademicPeriod');
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('from', [
                'ruleCompareDate' => [
                    'rule' => ['compareDate', 'to', false]
                ]
            ])

            ;
    }

    private function setupTabElements() {
        $tabElements = $this->controller->getProfessionalDevelopmentTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('modified_user_id',    ['visible' => ['index' => true, 'view' => true], 'after' => 'final_rating']);
        $this->field('modified',            ['visible' => ['index' => true, 'view' => true], 'after' => 'modified_user_id']);

        $this->setupTabElements();
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('comment', ['visible' => false]);
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('competency_set_id', ['visible' => false]);

        $this->setFieldOrder(['staff_appraisal_type_id', 'title', 'from', 'to', 'final_rating']);
    }

    public function viewEditBeforeQuery(Event $event, Query $query)
    {
        $query->contain(['Competencies']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data)
    {
        $entity['final_rating'] = $this->getFinalRating($entity, $data);
    }

    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data)
    {
        $entity['final_rating'] = $this->getFinalRating($entity, $data);
    }

    private function getFinalRating(Entity $entity, ArrayObject $data)
    {
        $finalRating = 0.00;

        if (isset($data[$this->alias]['competencies'])) {
            $competenciesRating = $data[$this->alias]['competencies'];
            foreach ($competenciesRating as $key => $value) {
                $finalRating = $finalRating + $value['_joinData']['rating'];
            }
        }

        return $finalRating;
    }

    public function addEditOnChangeAcademicPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $dateAttr = ['from' => Date::now(), 'to' => Date::now()];
        $academicPeriodId = $data[$this->alias]['academic_period_id'];

        if (!empty($academicPeriodId)) {
            $dateAttr['from'] = $this->AcademicPeriods->get($academicPeriodId)->start_date;
            $dateAttr['to'] = $this->AcademicPeriods->get($academicPeriodId)->end_date;

            // add restriction, only can choose within the selected academic period.
            $this->fields['from']['date_options']['startDate'] = $dateAttr['from']->format('d-m-Y');
            $this->fields['from']['date_options']['endDate'] = $dateAttr['to']->format('d-m-Y');
            $this->fields['to']['date_options']['startDate'] = $dateAttr['from']->format('d-m-Y');
            $this->fields['to']['date_options']['endDate'] = $dateAttr['to']->format('d-m-Y');
        }

        // will change the 'from' and 'to' timing to the academic period start and end date.
        $this->fields['from']['value'] = $dateAttr['from'];
        $this->fields['to']['value'] = $dateAttr['to'];

        // turn off the today button on the date picker
        $this->fields['from']['date_options']['todayBtn'] = false;
        $this->fields['to']['date_options']['todayBtn'] = false;
    }

    public function addEditOnChangeCompetencySet(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (array_key_exists($this->alias(), $data)) {
            $data[$this->alias()]['competencies'] = [];

            if (array_key_exists('competency_set_id', $data[$this->alias()]) && !empty($data[$this->alias()]['competency_set_id'])) {
                $competencySetId = $data[$this->alias()]['competency_set_id'];

                $CompetencySets = TableRegistry::get('Staff.CompetencySets');
                $competencySetResults = $CompetencySets
                    ->find()
                    ->contain(['Competencies'])
                    ->where([$CompetencySets->aliasField('id') => $competencySetId])
                    ->first();

                foreach ($competencySetResults->competencies as $key => $obj) {
                    $data[$this->alias()]['competencies'][] = [
                        'id' => $obj['id'],
                        'name' => $obj['name'],
                        'min' => $obj['min'],
                        'max' => $obj['max'],
                        '_joinData' => ['rating' => 0]
                    ];
                }
            }
        }
    }

    // to rename the field header
    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'staff_appraisal_type_id') {
            return __('Type');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetModifiedUserId(Event $event, Entity $entity)
    {
        if($this->action == 'index') {
            $entity['modified_user'] = !empty($entity['modified_user']) ? $entity['modified_user'] : $entity['created_user'];
        }
    }

    public function onGetModified(Event $event, Entity $entity)
    {
        if($this->action == 'index') {
            $entity['modified'] = date('d-M-Y', strtotime(!empty($entity['modified']) ? $entity['modified'] : $entity['created']));
        }
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable'=>true]);

            $attr['options'] = $academicPeriodOptions;
            $attr['onChangeReload'] = 'changeAcademicPeriod';
        } else if ($action == 'view') {
            $attr['visible'] = false;
        }

        return $attr;
    }

    public function onUpdateFieldStaffAppraisalTypeId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $typeOptions = $this->getStaffAppraisalTypeIdOptions();
            $attr['options'] = $typeOptions;
        }

        return $attr;
    }

    public function onUpdateFieldCompetencySetId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $competencySetOptions = $this->getCompetencySetOptions();

            $attr['options'] = $competencySetOptions;
            $attr['onChangeReload'] = 'changeCompetencySet';
        }

        return $attr;
    }

    public function onGetCustomRatingElement(Event $event, $action, $entity, $attr, $options=[])
    {
        $tableHeaders = [__('Competency'), __('Rating')];
        $tableCells = [];

        if ($action == 'view') {
            $staffAppraisalId = $entity->id;

            $StaffAppraisalsCompetencies = TableRegistry::get('Staff.StaffAppraisalsCompetencies');

            $competencyItems = $StaffAppraisalsCompetencies
                ->find()
                ->contain(['Competencies'])
                ->where([$StaffAppraisalsCompetencies->aliasField('staff_appraisal_id') => $staffAppraisalId])
                ->toArray();

            foreach ($competencyItems as $key => $obj) {
                $rowData = [];
                $rowData[] = $obj->competency->name;
                $rowData[] = $obj->rating;

                $tableCells[] = $rowData;
            }

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
            $attr['finalRating'] = $entity->final_rating;

            if ($entity->has('competency_set_id')) {
                return $event->subject()->renderElement('Staff.Staff/competency_table', ['attr' => $attr]);
            }
        } else if ($action == 'edit') {
            $form = $event->subject()->Form;
            $cellCount = 0;

            $arrayCompetencies = [];
            if ($this->request->is(['get'])) {
                if ($entity->has('competency_set_id')) {
                    $competencySetId = $entity->competency_set_id;

                    $StaffAppraisalsCompetencies = TableRegistry::get('Staff.StaffAppraisalsCompetencies');
                    $CompetencySetsCompetencies = TableRegistry::get('Staff.CompetencySetsCompetencies');
                    $leftJoinConditions = [
                        $StaffAppraisalsCompetencies->aliasField('competency_id = ') . $CompetencySetsCompetencies->aliasField('competency_id')
                    ];
                    if (isset($entity->id)) {
                        $leftJoinConditions[$StaffAppraisalsCompetencies->aliasField('staff_appraisal_id')] = $entity->id;
                    }

                    $competencyResults = $CompetencySetsCompetencies
                        ->find()
                        ->select([
                            'id' => $CompetencySetsCompetencies->aliasField('competency_id'),
                            'name' => 'Competencies.name',
                            'min' => 'Competencies.min',
                            'max' => 'Competencies.max',
                            'rating' => $StaffAppraisalsCompetencies->aliasField('rating')
                        ])
                        ->contain(['Competencies'])
                        ->leftJoin(
                            [$StaffAppraisalsCompetencies->alias() => $StaffAppraisalsCompetencies->table()],
                            $leftJoinConditions
                        )
                        ->where([$CompetencySetsCompetencies->aliasField('competency_set_id') => $competencySetId])
                        ->order([$CompetencySetsCompetencies->aliasField('competency_id')])
                        ->toArray();

                    foreach ($competencyResults as $key => $competencyObj) {
                        $arrayCompetencies[] = [
                            'id' => $competencyObj->id,
                            'name' => $competencyObj->name,
                            'min' => $competencyObj->min,
                            'max' => $competencyObj->max,
                            'rating' => $competencyObj->rating
                        ];
                    }
                }
            } else if ($this->request->is(['post', 'put'])) {
                $requestData = $this->request->data;
                if (array_key_exists('competencies', $requestData[$this->alias()])) {
                    foreach ($requestData[$this->alias()]['competencies'] as $key => $obj) {
                        $arrayCompetencies[] = [
                            'id' => $obj['id'],
                            'name' => $obj['name'],
                            'min' => $obj['min'],
                            'max' => $obj['max'],
                            'rating' => $obj['_joinData']['rating']
                        ];
                    }
                }
            }

            foreach ($arrayCompetencies as $key => $obj) {
                $fieldPrefix = $attr['model'] . '.competencies.' . $cellCount++;
                $joinDataPrefix = $fieldPrefix . '._joinData';
                $rating = !empty($obj['rating']) ? $obj['rating'] : '';

                $cellData = "";
                $cellData .= $form->input($joinDataPrefix.".rating", ['label' => false, 'type' => 'number', 'value' => $rating, 'min' => $obj['min'], 'max' => $obj['max'], 'step' => 0.1, 'onchange' => "jsTable.computeTotalForMoney('finalRating');$('.finalRatingInput').val($('.finalRating').html());", 'computeType' => 'finalRating']);
                $cellData .= $form->hidden($fieldPrefix.".id", ['value' => $obj['id']]);
                $cellData .= $form->hidden($fieldPrefix.".name", ['value' => $obj['name']]);
                $cellData .= $form->hidden($fieldPrefix.".min", ['value' => $obj['min']]);
                $cellData .= $form->hidden($fieldPrefix.".max", ['value' => $obj['max']]);

                $rowData = [];
                $rowData[] = $obj['name'];
                $rowData[] = $cellData;

                $tableCells[] = $rowData;
            }

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;

            if ($entity->has('competency_set_id')) {
                return $event->subject()->renderElement('Staff.Staff/competency_table', ['attr' => $attr]);
            }
        }
    }

    public function onUpdateFieldFinalRating(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['attr']['value'] = 0;
        }

        return $attr;
    }

    public function setupFields(Entity $entity)
    {
        $this->field('academic_period_id');
        $this->field('staff_appraisal_type_id', ['type' => 'select']);
        $this->field('competency_set_id');
        $this->field('rating', ['type' => 'custom_rating']);
        $this->field('final_rating', ['type' => 'hidden']);

        $this->setFieldOrder(['title', 'academic_period_id', 'from', 'to', 'staff_appraisal_type_id', 'competency_set_id', 'final_rating', 'comment']);
    }

    public function getStaffAppraisalTypeIdOptions()
    {
        // type only self if choose to appraise himself, if appraise other staff will be only supervisor or peer.
        // $session = $this->request->session();
        // $loginUserId = $session->read('Auth.User.id');
        // $staffId = $session->read('Staff.Staff.id');
        // pr($loginUserId);
        // pr($staffId);

        $staffAppraisalType = TableRegistry::get('Staff.StaffAppraisalTypes');
        $typeOptions = $staffAppraisalType
            ->find('list')
            ->toArray();
        return $typeOptions;
    }

    public function getCompetencySetOptions()
    {

        $competencySets = TableRegistry::get('Staff.CompetencySets');
        $competencySetOptions = $competencySets
            ->find('list')
            ->toArray();
        return $competencySetOptions;
    }
}
