<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Chronos\Date;
use Cake\Chronos\Chronos;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Session;
use Cake\ORM\TableRegistry;

use App\Model\Table\ControllerActionTable;

class StaffAppraisalsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('staff_appraisals');
        parent::initialize($config);

        // for file upload
        $this->addBehavior('ControllerAction.FileUpload', [
            // 'name' => 'file_name',
            // 'content' => 'file_content',
            'size' => '2MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);

        // setting this up to be overridden in viewAfterAction(), this code is required for file download
        $this->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            true
        );

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('StaffAppraisalTypes', ['className' => 'Staff.StaffAppraisalTypes']);
        $this->belongsTo('CompetencySets', ['className' => 'Staff.CompetencySets']);

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

        // POCOR-4047 to get staff profile data
        $this->addBehavior('Institution.StaffProfile');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('from', [
                'ruleCompareDate' => [
                    'rule' => ['compareDate', 'to', false]
                ]
            ])
            ->allowEmpty('file_content')
        ;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }

    private function setupTabElements()
    {
        $options['type'] = 'staff';
        $userId = $this->request->query('user_id');
        if (!is_null($userId)) {
            $options['user_id'] = $userId;
        }

        $tabElements = $this->controller->getProfessionalDevelopmentTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $session = $this->request->session();
        $staffId = $session->read('Staff.Staff.id');

        $this->field('modified_user_id',    ['visible' => ['index' => true, 'view' => true], 'after' => 'final_rating']);
        $this->field('modified',            ['visible' => ['index' => true, 'view' => true], 'after' => 'modified_user_id']);
        $this->field('staff_id',            ['type' => 'hidden', 'value' => $staffId]);

        if (!empty($this->paramsPass(0))) {

            $staffAppraisalId = $this->paramsDecode($this->paramsPass(0));

            $loginUserId = $this->Auth->user('id');
            $createdUserId = $this->get($staffAppraisalId)->created_user_id;

            // if not admin and not his own appraisal remove and edit button will be remove
            if (!$this->AccessControl->isAdmin()) {
                if ($loginUserId != $createdUserId) {
                    $this->toggle('remove', false);
                    $this->toggle('edit', false);
                }
            }
        }

        $this->setupTabElements();
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('comment', ['visible' => false]);
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('competency_set_id', ['visible' => false]);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);

        $this->setFieldOrder(['staff_appraisal_type_id', 'title', 'from', 'to', 'final_rating']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->session();
        $staffId = $session->read('Staff.Staff.id');
        $query = $query->where([$this->aliasField('staff_id') => $staffId]);

        //Add controls filter to index page
        $academicPeriodList = $this->AcademicPeriods->getYearList(['isEditable'=>true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period')) ? $this->request->query('academic_period') : $this->AcademicPeriods->getCurrent();

        $extra['elements']['controls'] = ['name' => 'Institution.StaffAppraisals/controls', 'data' => [], 'options' => [], 'order' => 1];
        $this->controller->set(compact('academicPeriodList', 'selectedAcademicPeriod'));
        $query->where([$this->aliasField('academic_period_id') => $selectedAcademicPeriod]);
    }

    public function viewEditBeforeQuery(Event $event, Query $query)
    {
        $query->contain(['Competencies']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // determine if download button is shown
        $showFunc = function() use ($entity) {
            $filename = $entity->file_content;
            return !empty($filename);
        };
        $this->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            $showFunc
        );
        // End

        $this->setupFields($entity);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $entity['final_rating'] = $this->getFinalRating($entity);
    }

    private function getFinalRating(Entity $entity)
    {
        $finalRating = 0.00;

        if (isset($entity->competencies)) {
            $competenciesRating = $entity->competencies;
            foreach ($competenciesRating as $key => $obj) {
                $finalRating = $finalRating + $obj->_joinData->rating;
            }
        }

        return $finalRating;
    }

    public function addEditOnChangeCompetencySet(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (array_key_exists($this->alias(), $data)) {
            $data[$this->alias()]['competencies'] = [];

            if (array_key_exists('competency_set_id', $data[$this->alias()]) && !empty($data[$this->alias()]['competency_set_id'])) {
                $competencySetId = $data[$this->alias()]['competency_set_id'];

                $CompetencySets = $this->CompetencySets;
                $competencySetResults = $CompetencySets
                    ->find()
                    ->contain(['Competencies'])
                    ->where([$CompetencySets->aliasField('id') => $competencySetId])
                    ->first();

                if (!empty($competencySetResults)) {
                    foreach ($competencySetResults->competencies as $key => $obj) {
                        $data[$this->alias()]['competencies'][] = [
                            'id' => $obj['id'],
                            'name' => $obj['name'],
                            'min' => $obj['min'],
                            'max' => $obj['max'],
                            'default' => $obj['default'],
                            'visible' => $obj['visible'],
                            '_joinData' => ['rating' => 0]
                        ];
                    }
                }
            }
        }
    }

    // to rename the field header
    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'staff_appraisal_type_id') {
            return __('Type');
        } elseif ($field == 'file_content') {
            return __('Attachment');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetModifiedUserId(Event $event, Entity $entity)
    {
        if ($this->action == 'index') {
            $entity['modified_user'] = !empty($entity['modified_user']) ? $entity['modified_user'] : $entity['created_user'];
        }
    }

    public function onGetModified(Event $event, Entity $entity)
    {
        if ($this->action == 'index') {
            $entity['modified'] = date('d-M-Y', strtotime(!empty($entity['modified']) ? $entity['modified'] : $entity['created']));
        }
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable'=>true]);

            $attr['options'] = $academicPeriodOptions;
            $attr['onChangeReload'] = true;
        } else if ($action == 'view') {
            $attr['visible'] = false;
        }

        return $attr;
    }

    public function onUpdateFieldFrom(Event $event, array $attr, $action, $request)
    {
        $dateAttr = ['from' => Date::now(), 'to' => Date::now()];
        $requestData = $request->data;
        $fromDate = '';

        if ($action == 'add') {
            if (!empty($requestData[$this->alias()]['academic_period_id'])) {
                $academicPeriodId = $requestData[$this->alias()]['academic_period_id'];
                $fromDate = new Chronos($requestData[$this->alias()]['from']);
            } else {
                $attr['value'] = $dateAttr['from'];
            }
        } else if ($action == 'edit') {
            $staffAppraisalId = $this->paramsDecode($this->paramsPass(0));
            $academicPeriodId = !empty($requestData[$this->alias()]['academic_period_id']) ? $requestData[$this->alias()]['academic_period_id'] : $this->get($staffAppraisalId)->academic_period_id;
            $fromDate = $this->get($staffAppraisalId)->from;
        }

        if (!empty($fromDate && $academicPeriodId)) {
            $dateAttr['from'] = $this->AcademicPeriods->get($academicPeriodId)->start_date;
            $dateAttr['to'] = $this->AcademicPeriods->get($academicPeriodId)->end_date;

            // will compare the date, if the form-date is within the academic period will not change when reload.
            if ($fromDate->between($dateAttr['from'], $dateAttr['to'])) {
                $attr['value'] = $fromDate;
            } else {
                $attr['value'] = $dateAttr['from'];
            }

            // add restriction to from-date-picker, only within selected academic period
            $attr['date_options']['startDate'] = $dateAttr['from']->format('d-m-Y');
            $attr['date_options']['endDate'] = $dateAttr['to']->format('d-m-Y');
        }

        // remove the from-today-date-picker
        $attr['date_options']['todayBtn'] = false;

        return $attr;
    }

    public function onUpdateFieldTo(Event $event, array $attr, $action, $request)
    {
        $dateAttr = ['from' => Date::now(), 'to' => Date::now()];
        $requestData = $request->data;
        $toDate = '';

        if ($action == 'add') {
            if (!empty($requestData[$this->alias()]['academic_period_id'])) {
                $academicPeriodId = $requestData[$this->alias()]['academic_period_id'];
                $toDate = new Chronos($requestData[$this->alias()]['to']);
            } else {
                $attr['value'] = $dateAttr['to'];
            }
        } else if ($action == 'edit') {
            $staffAppraisalId = $this->paramsDecode($this->paramsPass(0));
            $academicPeriodId = !empty($requestData[$this->alias()]['academic_period_id']) ? $requestData[$this->alias()]['academic_period_id'] : $this->get($staffAppraisalId)->academic_period_id;
            $toDate = $this->get($staffAppraisalId)->to;
        }

        if (!empty($toDate && $academicPeriodId)) {
            $dateAttr['from'] = $this->AcademicPeriods->get($academicPeriodId)->start_date;
            $dateAttr['to'] = $this->AcademicPeriods->get($academicPeriodId)->end_date;

            // will compare the date, if the form-date is within the academic period will not change when reload.
            if ($toDate->between($dateAttr['from'], $dateAttr['to'])) {
                $attr['value'] = $toDate;
            } else {
                $attr['value'] = $dateAttr['to'];
            }

            // add restriction to from-date-picker, only within selected academic period
            $attr['date_options']['startDate'] = $dateAttr['from']->format('d-m-Y');
            $attr['date_options']['endDate'] = $dateAttr['to']->format('d-m-Y');
        }

        // remove the from-today-date-picker
        $attr['date_options']['todayBtn'] = false;

        return $attr;
    }

    public function onUpdateFieldStaffAppraisalTypeId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            // type only self if choose to appraise himself, if appraise other staff will be only supervisor or peer.
            $staffAppraisalType = $this->StaffAppraisalTypes;
            $session = $this->request->session();
            $loginUserId = $this->Auth->user('id');
            $staffId = $session->read('Staff.Staff.id');

            if (!$this->AccessControl->isAdmin()) {
                if ($loginUserId == $staffId) {
                    $typeId = $this->StaffAppraisalTypes->getIdByCode('SELF');

                    $attr['type'] = 'readOnly';
                    $attr['attr']['value'] = 'Self';
                    $attr['value'] = $typeId;
                } else {
                    $typeOptions = $staffAppraisalType
                        ->find('list')
                        ->where([[$staffAppraisalType -> aliasField('code != ') => 'SELF']])
                        ->toArray();
                    $attr['options'] = $typeOptions;
                }
            }

        return $attr;
        }
    }

    public function onUpdateFieldCompetencySetId(Event $event, array $attr, $action, $request)
    {
        $CompetencySetsCompetencies = TableRegistry::get('Staff.CompetencySetsCompetencies');

        if ($action == 'add' || $action == 'edit') {
            $competencySetOptionsArray = $this->CompetencySets
                ->find('CompetencySetsOptions')
                ->toArray()
                ;

            $attr['options'] = [];
            if (!empty($competencySetOptionsArray)) {
                // if competency set doesnt have any competency will not be shown on the list.
                foreach ($competencySetOptionsArray as $key => $obj) {
                    $competencyCount = $CompetencySetsCompetencies
                        ->find()
                        ->where([$CompetencySetsCompetencies->aliasField('competency_set_id') => $key])
                        ->count();

                    if ($competencyCount > 0) {
                        $attr['options'][$key] = $obj;
                    }
                }
            }

            $attr['onChangeReload'] = 'changeCompetencySet';
        }

        return $attr;
    }

    public function onGetCustomRatingElement(Event $event, $action, $entity, $attr, $options=[])
    {
        $staffAppraisalId = $entity->id;

        if ($action == 'view') {
            $tableHeaders = [
                $this->getMessage('Staff.Appraisal.competencies_goals'),
                $this->getMessage('Staff.Appraisal.rating')
            ];
            $tableCells = [];

            $competencyItems = $this
                ->find()
                ->contain(['Competencies'])
                ->where([$this->aliasField('id') => $entity->id])
                ->first()->competencies;

            foreach ($competencyItems as $key => $obj) {
                $rowData = [];
                $rowData[] = $obj->name;
                $rowData[] = $obj->_joinData->rating;

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
            $tableCells = [];
            $cellCount = 0;

            $arrayCompetencies = [];
            if ($this->request->is(['get'])) {
                if ($entity->has('competency_set_id')) {
                    $competencySetId = $entity->competency_set_id;

                    $competencies = TableRegistry::get('Staff.Competencies');
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
                            'default' => 'Competencies.default',
                            'visible' => 'Competencies.visible',
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
                        $arrayCompetencies[$competencyObj->id] = [
                            'id' => $competencyObj->id,
                            'name' => $competencyObj->name,
                            'min' => $competencyObj->min,
                            'max' => $competencyObj->max,
                            'default' => $competencyObj->default,
                            'visible' => $competencyObj->visible,
                            'rating' => $competencyObj->rating
                        ];
                    }

                    $missingCompetency = $this->getMissingCompetency($competencySetId, $staffAppraisalId);
                    if (!empty($missingCompetency)) {
                        foreach ($missingCompetency as $key => $obj) {
                            $arrayCompetencies[$obj['competency_id']] = [
                                'id' => $obj['competency_id'],
                                'name' => $competencies->get($obj['competency_id'])->name,
                                'min' => $competencies->get($obj['competency_id'])->min,
                                'max' => $competencies->get($obj['competency_id'])->max,
                                'default' => $competencies->get($obj['competency_id'])->default,
                                'visible' => $competencies->get($obj['competency_id'])->visible,
                                'rating' => 'Deleted'
                            ];
                        }
                        sort($arrayCompetencies);
                    }
                }
            } else if ($this->request->is(['post', 'put'])) {
                $requestData = $this->request->data;
                if (array_key_exists('competencies', $requestData[$this->alias()])) {
                    foreach ($requestData[$this->alias()]['competencies'] as $key => $obj) {
                        $arrayCompetencies[$obj['id']] = [
                            'id' => $obj['id'],
                            'name' => $obj['name'],
                            'min' => $obj['min'],
                            'max' => $obj['max'],
                            'default' => $obj['default'],
                            'visible' => $obj['visible'],
                            'rating' => $obj['_joinData']['rating']
                        ];
                    }
                }
            }

            $ngModel = [];
            $totalNgModel = '';
            if (!empty($arrayCompetencies)) {
                foreach ($arrayCompetencies as $key => $obj) {
                    $fieldPrefix = $attr['model'] . '.competencies.' . $cellCount++;
                    $joinDataPrefix = $fieldPrefix . '._joinData';
                    $rating = !empty($obj['rating']) ? $obj['rating'] : 0;
                    $ngModel[] = 'rating_' . $key;

                    $form->unlockField($joinDataPrefix.".rating");
                    $cellData = "";
                    if ($rating === 'Deleted') {
                        $cellData = $this->getMessage('Staff.Appraisal.deleted_competencies');
                    } else {
                        $cellData .= '<div class="slider-wrapper input-slider"><slider ng-model="rating_'.$key.'" value='.$rating.' min='.$obj['min'].' step="0.5" max='.$obj['max'].'></div>';
                        $cellData .= $form->hidden($joinDataPrefix.".rating", [
                            'label' => false,
                            'type' => 'number',
                            'value' => '{{rating_'.$key.'}}'
                        ]);
                        $cellData .= $form->hidden($fieldPrefix.".id", ['value' => $obj['id']]);
                        $cellData .= $form->hidden($fieldPrefix.".name", ['value' => $obj['name']]);
                        $cellData .= $form->hidden($fieldPrefix.".min", ['value' => $obj['min']]);
                        $cellData .= $form->hidden($fieldPrefix.".max", ['value' => $obj['max']]);
                        $cellData .= $form->hidden($fieldPrefix.".default", ['value' => $obj['default']]);
                        $cellData .= $form->hidden($fieldPrefix.".visible", ['value' => $obj['visible']]);
                    }

                    $rowData = [];
                    $rowData[] = $obj['name'];
                    $rowData[] = $cellData;
                    $rowData[] = '{{rating_'.$key.' | number : 1}}'; // "| number : 1"  means the digit after decimal

                    $tableCells[] = $rowData;
                }

                // angular to sum all the rating '{{rating_1+rating_2+.....}}'
                for ($i=0; $i < count($ngModel) ; $i++) {
                    if ($i < count($ngModel) - 1) {
                        $totalNgModel .= $ngModel[$i] . ' + ';
                    } else {
                        $totalNgModel .= $ngModel[$i];
                    }
                }

                $attr['tableCells'] = $tableCells;
                $attr['finalRating'] = '{{'.$totalNgModel.' | number : 1}}'; // "| number : 1"  means the digit after decimal

                if ($entity->has('competency_set_id')) {
                    return $event->subject()->renderElement('Staff.Staff/competency_table', ['attr' => $attr]);
                }
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

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        $session = $this->request->session();
        $loginUserId = $this->Auth->user('id');
        $createdUserId = $entity->created_user_id;

        // if not admin and not his own appraisal remove and edit button will be remove
        if (!$this->AccessControl->isAdmin()) {
            if ($loginUserId != $createdUserId) {
                unset($buttons['edit']);//remove edit action from the action button
                unset($buttons['remove']);// remove delete action from the action button
            }
        }

        return $buttons;
    }

    public function setupFields(Entity $entity)
    {
        $this->field('academic_period_id');
        $this->field('from');
        $this->field('to');
        $this->field('staff_appraisal_type_id', ['type' => 'select']);
        $this->field('competency_set_id');
        $this->field('rating', ['type' => 'custom_rating']);
        $this->field('final_rating', ['type' => 'hidden']);
        $this->field('file_name', [
            'type' => 'hidden',
            'visible' => ['view' => false, 'edit' => true]
        ]);
        $this->field('file_content', ['visible' => ['view' => false, 'edit' => true]]);

        $this->setFieldOrder(['title', 'academic_period_id', 'from', 'to', 'staff_appraisal_type_id', 'competency_set_id', 'rating', 'final_rating', 'comment', 'file_name', 'file_content']);
    }

    public function getMissingCompetency($competencySetId, $staffAppraisalId)
    {
        $StaffAppraisalsCompetencies = TableRegistry::get('Staff.StaffAppraisalsCompetencies');
        $CompetencySetsCompetencies = TableRegistry::get('Staff.CompetencySetsCompetencies');

        $oldSetData = $StaffAppraisalsCompetencies
            ->find()
            ->where([$StaffAppraisalsCompetencies->aliasField('staff_appraisal_id') => $staffAppraisalId])
            ->toArray()
            ;

        $newSetData = $CompetencySetsCompetencies
            ->find()
            ->select([$CompetencySetsCompetencies->aliasField('competency_id')])
            ->where([$CompetencySetsCompetencies->aliasField('competency_set_id') => $competencySetId])
            ->toArray()
            ;

        $newSet = [];
        foreach ($newSetData as $key => $obj) {
            $newSet[$obj['competency_id']] = $obj['rating'];
        }

        $missingCompetency = [];
        foreach ($oldSetData as $key => $obj) {
           if ((!empty($oldSetData)) && (!array_key_exists($obj['competency_id'], $newSet))) {
                $missingCompetency[$key] = $obj;
            }
        }

        return $missingCompetency;
    }
}
