<?php

namespace Education\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Traits\HtmlTrait;
use App\Model\Table\ControllerActionTable;
use Cake\Http\ServerRequest;

class EducationProgrammesTable extends ControllerActionTable {

    use HtmlTrait;

    private $_contain = ['EducationNextProgrammes._joinData'];
    private $_fieldOrder = ['code', 'name', 'duration', 'visible', 'education_field_of_study_id','education_cycle_id', 'education_certification_id' ,'same_grade_promotion'];//POCOR-4746

    public function initialize(array $config): void {
        parent::initialize($config);
        $this->belongsTo('EducationCycles', ['className' => 'Education.EducationCycles']);
        $this->belongsTo('EducationCertifications', ['className' => 'Education.EducationCertifications']);
        $this->belongsTo('EducationFieldOfStudies', ['className' => 'Education.EducationFieldOfStudies']);
        $this->hasMany('EducationGrades', ['className' => 'Education.EducationGrades']);

        $this->belongsToMany('EducationNextProgrammes', [
            'className' => 'Education.EducationNextProgrammes',
            'joinTable' => 'education_programmes_next_programmes',
            'foreignKey' => 'education_programme_id',
            'targetForeignKey' => 'next_programme_id',
            'through' => 'Education.EducationProgrammesNextProgrammes',
            'dependent' => true,
        ]);

        if ($this->behaviors()->has('Reorder')) {
            $reorderBehavior = $this->behaviors()->get('Reorder');
        	$reorderBehavior->setConfig('filter', 'education_cycle_id');
        }
        if ($this->behaviors()->has('ControllerAction')) {
            $controllerActionBehavior = $this->behaviors()->get('ControllerAction');
            $controllerActionBehavior->setConfig(['actions' => ['reorder' => false]]);
        }

        $this->setDeleteStrategy('restrict');
    }

    /*public function validationDefault(Validator $validator): Validator {
        $validator = parent::validationDefault($validator);
        if (isset($this->action) && $this->action == 'add') {
            return $validator
                        ->add('code', 'ruleUnique', [
                            //'rule' => 'validateUnique',
                            'rule' => 'educationProgrammesCode',
                            'provider' => 'table'
            ]);
        } else {
            return $validator;
        }
    }*/

    public function beforeAction(Event $event, ArrayObject $extra) {
        if ($this->action != 'index') {
            $this->field('next_programmes', ['type' => 'custom_next_programme', 'valueClass' => 'table-full-width','after'=>'same_grade_promotion']);
            $this->field('same_grade_promotion');//POCOR-4746
            $this->_fieldOrder[] =['next_programmes'];
        }
    }

    public function afterAction(Event $event, ArrayObject $extra) {
        $this->setFieldOrder($this->_fieldOrder);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra) {
        $this->fields['education_field_of_study_id']['sort'] = ['field' => 'EducationFieldOfStudies.name'];
        $this->fields['education_cycle_id']['sort'] = ['field' => 'EducationCycles.name'];
        $this->fields['education_certification_id']['sort'] = ['field' => 'EducationCertifications.name'];
        $this->field('same_grade_promotion',['visible'=>'hidden']);//POCOR-4746
        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Education Programmes','Education');
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
			];

			$helpBtn['url'] = $is_manual_exist['url'];
			$helpBtn['type'] = 'button';
			$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
			$helpBtn['attr'] = $btnAttr;
			$helpBtn['attr']['title'] = __('Help');
			$extra['toolbarButtons']['help'] = $helpBtn;
		}
		// End POCOR-5188
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options): void
    {
        // Skip if triggered internally or no authenticated user
        if (!empty($options['skip_callbacks']) || empty($this->Auth) || empty($this->Auth->user())) {
            return;
        }

        $eventKey = $entity->isNew()
            ? 'education_programme_create'
            : 'education_programme_update';

        $this->triggerWebhookCommand($entity, $eventKey);
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options): void
    {
        // Always perform related child cleanup
        $this->deleteChildProgrammes($entity);

        // Skip webhook if no authenticated user
        if (empty($this->Auth) || empty($this->Auth->user())) {
            return;
        }

        $this->triggerWebhookCommand($entity, 'education_programme_delete');
    }

    /**
     * Shared webhook trigger for Education Programme events.
     */
    private function triggerWebhookCommand(Entity $entity, string $eventKey): void
    {
        $body = [
            'programme_id'        => $entity->id,
            'programme_name'      => $entity->name ?? null,
            'education_cycle_id'  => $entity->education_cycle_id ?? null,
        ];

        // Add metadata for delete tracking if applicable
        if ($eventKey === 'education_programme_delete') {
            $body['deleted_at'] = date('Y-m-d H:i:s');
            $body['deleted_by'] = $this->Auth->user()['openemis_no']
                ?? $this->Auth->user()['username']
                ?? 'system';
        }

        $Webhooks = TableRegistry::getTableLocator()->get('Configuration.ConfigWebhooks');
        $Webhooks->triggerCommand($eventKey, $body);
    }

    /**
     * Delete child entries from EducationProgrammesNextProgrammes when a programme is removed.
     */
    private function deleteChildProgrammes(Entity $entity): void
    {
        $EducationProgrammesNextProgrammes = TableRegistry::getTableLocator()
            ->get('Education.EducationProgrammesNextProgrammes');

        $EducationProgrammesNextProgrammes->deleteAll([
            $EducationProgrammesNextProgrammes->aliasField('next_programme_id') => $entity->id,
        ]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
        $serverRequest = $this->request;
        /*list($academicPeriodOptions, $selectedAcademicPeriod, $levelOptions, $selectedLevel, $cycleOptions, $selectedCycle) = array_values($this->getSelectOptions());
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod','levelOptions', 'selectedLevel', 'cycleOptions', 'selectedCycle'));
        $extra['elements']['controls'] = ['name' => 'Education.controls', 'data' => [], 'options' => [], 'order' => 1];
        $query->where([$this->aliasField('education_cycle_id') => $selectedCycle]);*/

        // Academic period filter
        $EducationSystems = TableRegistry::get('Education.EducationSystems');
        $academicPeriodOptions = $this->EducationCycles->EducationLevels->EducationSystems->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($serverRequest->getQuery('academic_period_id')) ? $serverRequest->getQuery('academic_period_id') : $this->EducationCycles->EducationLevels->EducationSystems->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$EducationSystems->aliasField('academic_period_id')] = $selectedAcademicPeriod;

        //level filter
        $levelOptions = $this->EducationCycles->EducationLevels->getEducationLevelOptions($selectedAcademicPeriod);
        if (!empty($levelOptions)) {
            $selectedLevel = !empty($serverRequest->getQuery('level')) ? $serverRequest->getQuery('level') : key($levelOptions);
        } else{
            $levelOptions = ['0' => '-- '.__('No Education Level').' --'] + $levelOptions;
            $selectedLevel = !empty($serverRequest->getQuery('level')) ?$serverRequest->getQuery('level') : 0;
        }

        $this->controller->set(compact('levelOptions', 'selectedLevel'));

        $cycleOptions = $this->EducationCycles
                ->find('list')
                ->find('visible')
                ->find('order')
                ->where([$this->EducationCycles->aliasField('education_level_id') => $selectedLevel])
                ->toArray();
        $selectedCycle = !is_null($serverRequest->getQuery('cycle')) ? $serverRequest->getQuery('cycle') : key($cycleOptions);

        $cycleOptions = $cycleOptions;
        if (!empty($cycleOptions)) {
            $selectedCycle = !empty($serverRequest->getQuery('cycle')) ? $serverRequest->getQuery('cycle') : key($cycleOptions);
        } else{
            $cycleOptions = ['0' => '-- '.__('No Education Cycle').' --'] + $cycleOptions;
            $selectedCycle = !empty($serverRequest->getQuery('cycle')) ? $serverRequest->getQuery('cycle') : 0;
        }

        $this->controller->set(compact('cycleOptions', 'selectedCycle'));
        $extra['elements']['controls'] = ['name' => 'Education.controls', 'data' => [], 'options' => [], 'order' => 1];
        $query->where([$this->aliasField('education_cycle_id') => $selectedCycle])
                        ->order([$this->aliasField('order') => 'ASC']);



        $sortList = ['order','code', 'name', 'EducationFieldOfStudies.name', 'EducationCycles.name', 'EducationCertifications.name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra) {
        $this->field('education_cycle_id');
        $this->fields['education_field_of_study_id']['type'] = 'select';
        $this->fields['education_certification_id']['type'] = 'select';
        $this->fields['same_grade_promotion']['type'] = 'select';//POCOR-4746
    }

    public function onUpdateFieldEducationCycleId(Event $event, array $attr, $action, ServerRequest $request) {
        //POCOR-5908 starts
        list(,,,, $cycleOptions, $selectedCycle) = array_values($this->getSelectOptions());
        //POCOR-5908 ends
        $attr['options'] = $cycleOptions;
        if ($action == 'add') { //POCOR-8644 add select option
            $attr['type'] = 'select';
            //$attr['default'] =   $selectedCycle;
            $attr['options'] = ['' => '-- ' . __('Select') . ' --'] + $cycleOptions;
            $attr['onChangeReload'] = true;
        }

        return $attr;
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra) {
        $query->where([$this->aliasField('education_cycle_id') => $entity->education_cycle_id]);
    }

    public function findWithCycle(Query $query, array $options) {
        return $query
                        ->contain(['EducationCycles'])
                        ->order(['EducationCycles.order' => 'ASC', $this->aliasField('order') => 'ASC']);
    }

    public function findAvailableProgrammes(Query $query, array $options) {
        $EducationCycles = TableRegistry::get('Education.EducationCycles');
        $EducationLevels = TableRegistry::get('Education.EducationLevels');

        return $query
                        ->find('visible')
                        ->innerJoin(
                                [$EducationCycles->getAlias() => $EducationCycles->getTable()], [
                            $EducationCycles->aliasField('id =') . $this->aliasField('education_cycle_id'),
                            $EducationCycles->aliasField('visible') => 1
                                ]
                        )
                        ->innerJoin(
                                [$EducationLevels->getAlias() => $EducationLevels->getTable()], [
                            $EducationLevels->aliasField('id =') . $EducationCycles->aliasField('education_level_id'),
                            $EducationLevels->aliasField('visible') => 1
                                ]
                        )
                        ->order([
                            $EducationLevels->aliasField('order') => 'ASC',
                            $EducationCycles->aliasField('order') => 'ASC',
                            $this->aliasField('order') => 'ASC'
        ]);
    }

    public function getSelectOptions() {
        // Academic period filter
        $EducationSystems = TableRegistry::get('Education.EducationSystems');
        $academicPeriodOptions = $this->EducationCycles->EducationLevels->EducationSystems->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->getQuery('academic_period_id')) ? $this->request->getQuery('academic_period_id') : $this->EducationCycles->EducationLevels->EducationSystems->AcademicPeriods->getCurrent();
        $where[$EducationSystems->aliasField('academic_period_id')] = $selectedAcademicPeriod;

        $levelOptions = $this->EducationCycles->EducationLevels->getLevelOptions($selectedAcademicPeriod);

        // POCOR-5973 starts
        $selectedLevel = !is_null($this->request->getQuery('level')) ? (array)$this->request->getQuery('level') : array_keys($levelOptions);

        // POCOR-8735 -- Check Conditions for where
        if (!empty($selectedLevel)) {
            $cycleOptions = $this->EducationCycles
                ->find('list')
                ->find('visible')
                ->find('order')
                ->where([$this->EducationCycles->aliasField('education_level_id') . ' IN' => $selectedLevel])
                ->toArray();
        } else {
            $cycleOptions = [];
        }
        // POCOR-5973 ends

        $selectedCycle = !is_null($this->request->getQuery('cycle')) ? $this->request->getQuery('cycle') : key($cycleOptions);

        return compact('academicPeriodOptions', 'selectedAcademicPeriod', 'levelOptions', 'selectedLevel', 'cycleOptions', 'selectedCycle');
    }

//    public function onGetCustomNextProgrammeElement(Event $event, $action, $entity, $attr, $options = []) {
//        $EducationProgrammesNextProgrammes = TableRegistry::get('Education.EducationProgrammesNextProgrammes');
//        if ($action == 'index') {
//            $value = $EducationProgrammesNextProgrammes
//                    ->find()
//                    ->where([$EducationProgrammesNextProgrammes->aliasField('education_programme_id') => $entity->id])
//                    ->count();
//            $attr['value'] = $value;
//        } else if ($action == 'view') {
//            $tableHeaders = [__('Cycle - (Programme)')];
//            $tableCells = [];
//
//            $educationNextProgrammes = $entity->extractOriginal(['education_next_programmes']);
//            foreach ($educationNextProgrammes['education_next_programmes'] as $key => $obj) {
//                if (!is_null($obj->_joinData)) {
//                    $programe = $this->find()->where([$this->aliasField('id') => $obj->_joinData->next_programme_id])->contain(['EducationCycles'])->first();
//                    $rowData = [];
//                    $rowData[] = $programe->cycle_programme_name;
//                    $tableCells[] = $rowData;
//                }
//            }
//
//            $attr['tableHeaders'] = $tableHeaders;
//            $attr['tableCells'] = $tableCells;
//        }else if($this->request->getParam('pass')[0] == 'add') //POCOR-8644  start
//        {
//            $cycleId = $this->request->getData()['EducationProgrammes']['education_cycle_id'];
//            if (!empty($cycleId)) {
//                $AcademicPeriod = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
//                $academic_period_id = $AcademicPeriod->AcademicPeriods->getCurrent();
//                $form = $event->getSubject()->Form;
//                $nextProgrammeOptions = [];
//                $EducationSystems = TableRegistry::get('Education.EducationSystems');
//                $educationCycles = TableRegistry::get('Education.EducationCycles');
//                $cycleRecord = $educationCycles->find()
//                    ->select([
//                        'education_level_id' => 'EducationCycles.education_level_id',
//                        'cycle_order' => $educationCycles->aliasField('order'), // Escaping the 'order' column
//                        'level_order' => $educationCycles->aliasField('EducationLevels.order'), // Escaping the 'order' column
//                        'level_id' => 'EducationLevels.id'
//                    ])
//                    ->contain(['EducationLevels.EducationSystems'])
//                    ->where(['EducationCycles.id IS' => $cycleId])
//                    ->first();
//
//                if ($cycleRecord) {
//                    $currentCycleOrder = $cycleRecord->cycle_order;
//                    $currentLevelId = $cycleRecord->level_id;
//                    $currentLevelOrder = $cycleRecord->level_order;
//                } else {
//                    $currentCycleOrder = null;
//                    $currentLevelId = null;
//                    $currentLevelOrder = null;
//                }
//
//
//            $EducationSystems = TableRegistry::get('Education.EducationSystems');
//            $educationProgrammesTable = clone $this;
//            $educationProgrammesTable->setAlias('EducationProgrammesClone');
//
//            $excludedProgrammes = $educationProgrammesTable->find()
//                    ->innerJoin(['EducationCycles' => 'education_cycles'], [
//                        'EducationCycles.id = ' . $educationProgrammesTable->aliasField('education_cycle_id')
//                    ])
//                    ->select(1)
//                    ->where([
//                'EducationCycles.order <= ' . $currentCycleOrder,
//                'EducationCycles.education_level_id = ' . $currentLevelId
//            ]);
//
//            $nextProgrammeOptions = $EducationSystems
//                    ->find('list', [
//                        'keyField' => 'programme_id',
//                        'valueField' => 'cycle_programme_name'
//                    ])
//                    ->matching('EducationLevels.EducationCycles.EducationProgrammes')
//                    ->select(['cycle_programme_name' => $EducationSystems->find()->func()->concat([
//                            'EducationSystems.name' => 'literal',
//                            ' - ',
//                            'EducationCycles.name' => 'literal',
//                            ' - (',
//                            'EducationProgrammes.name' => 'literal',
//                            ')'
//                        ]), 'programme_id' => 'EducationProgrammes.id'])
//                    ->where([
//                        $EducationSystems->aliasField('academic_period_id') => $academic_period_id,
//                        'EducationLevels.order >= ' => $currentLevelOrder,
//                        'NOT EXISTS(' . $excludedProgrammes->where([$educationProgrammesTable->aliasField('id') . ' = ' . 'EducationProgrammes.id']) . ')'
//                    ])
//                    ->orderAsc('EducationSystems.order')
//                    ->orderAsc('EducationLevels.order')
//                    ->orderAsc('EducationCycles.order')
//                   ->orderAsc('EducationProgrammes.order')
//                    ->toArray();
//
//            $tableHeaders = [__('Cycle - (Programme)'), '', ''];
//            $tableCells = [];
//            $cellCount = 0;
//
//            $arrayNextProgrammes = [];
//            if ($this->request->is(['get'])) {
//                $educationProgramme = TableRegistry::get('Education.EducationProgrammes');
//                foreach ($nextProgrammeslist as $next_programme_id) {
//                    $programme = $educationProgramme->find()->where([$educationProgramme->aliasField('id') => $next_programme_id])->contain(['EducationCycles'])->first();
//                    $arrayNextProgrammes[] = [
//                        'id' => $programme->id,
//                        'education_programme_id' => $programme->education_programme_id,
//                        'next_programme_id' => $next_programme_id,
//                        'name' => $programme->cycle_programme_name
//                    ];
//                }
//            } else if ($this->request->is(['post', 'put'])) {
//                $requestData = $this->request->getData();
//                if (array_key_exists('education_next_programmes', $requestData[$this->getAlias()])) {
//                    foreach ($requestData[$this->getAlias()]['education_next_programmes'] as $key => $obj) {
//                        $arrayNextProgrammes[] = $obj['_joinData'];
//                    }
//                }
//                if (array_key_exists('next_programme_id', $requestData[$this->getAlias()])) {
//                    $nextProgrammeId = $requestData[$this->getAlias()]['next_programme_id'];
//                    $programmeObj = $this
//                            ->find()
//                            ->where([$this->aliasField('id') => $nextProgrammeId])
//                            ->first();
//
//                    // POCOR-4002 adding the checking to prevent adding empty next programme
//                    if (!empty($programmeObj)) {
//                        $arrayNextProgrammes[] = [
//                           // 'education_programme_id' => $entity->id,
//                            'next_programme_id' => $programmeObj->id,
//                            'name' => $programmeObj->cycle_programme_name,
//                        ];
//                    }
//                    // end POCOR-4002
//                }
//            }
//            $form->unlockField($attr['model'] . '.education_next_programmes');
//            foreach ($arrayNextProgrammes as $key => $obj) {
//                $fieldPrefix = $attr['model'] . '.education_next_programmes.' . $cellCount++;
//                $joinDataPrefix = $fieldPrefix . '._joinData';
//
//                $educationProgrammeId = $obj['next_programme_id'];
//                $nextProgrammeName = $obj['name'];
//
//                $cellData = "";
//                $cellData .= $form->hidden($fieldPrefix . ".id", ['value' => $educationProgrammeId]);
//                $cellData .= $form->hidden($joinDataPrefix . ".name", ['value' => $nextProgrammeName]);
//                $cellData .= $form->hidden($joinDataPrefix . ".education_programme_id", ['value' => $obj['education_programme_id']]);
//                $cellData .= $form->hidden($joinDataPrefix . ".next_programme_id", ['value' => $obj['next_programme_id']]);
//                if (isset($obj['id'])) {
//                    $cellData .= $form->hidden($joinDataPrefix . ".id", ['value' => $obj['id']]);
//                }
//
//                $rowData = [];
//                $rowData[] = $nextProgrammeName;
//                $rowData[] = $cellData;
//                $rowData[] = $this->getDeleteButton();
//
//                $tableCells[] = $rowData;
//                unset($nextProgrammeOptions[$obj['next_programme_id']]);
//            }
//
//            $attr['tableHeaders'] = $tableHeaders;
//            $attr['tableCells'] = $tableCells;
//
//            $nextProgrammeOptions[0] = "-- " . __('Add Next Programme') . " --";
//            ksort($nextProgrammeOptions);
//            $attr['options'] = $nextProgrammeOptions;
//            }
//            //POCOR-8644 end
//        } else if ($action == 'edit') {
//            if (isset($entity->id)) {
//                $nextProgrammeslist = $EducationProgrammesNextProgrammes
//                        ->find('list', ['keyField' => 'id', 'valueField' => 'next_programme_id'])
//                        ->where([$EducationProgrammesNextProgrammes->aliasField('education_programme_id') => $entity->id])
//                        ->toArray();
//                $form = $event->getSubject()->Form;
//                $nextProgrammeOptions = [];
//
//                $currentProgrammSystem = $this->find()->contain(['EducationCycles.EducationLevels.EducationSystems'])->where([$this->aliasField('id') => $entity->id])->first();
//                $academic_period_id = $currentProgrammSystem->education_cycle->education_level->education_system->academic_period_id;
//                //$systemId = id;
//                $currentCycleOrder = $currentProgrammSystem->education_cycle->order;
//                $currentLevelOrder = $currentProgrammSystem->education_cycle->education_level->order;
//                $currentLevelId = $currentProgrammSystem->education_cycle->education_level->id;
//
//                $EducationSystems = TableRegistry::get('Education.EducationSystems');
//
//
//                $educationProgrammesTable = clone $this;
//                $educationProgrammesTable->setAlias('EducationProgrammesClone');
//
//                $excludedProgrammes = $educationProgrammesTable->find()
//                        ->innerJoin(['EducationCycles' => 'education_cycles'], [
//                            'EducationCycles.id = ' . $educationProgrammesTable->aliasField('education_cycle_id')
//                        ])
//                        ->select(1)
//                        ->where([
//                    'EducationCycles.order <= ' . $currentCycleOrder,
//                    'EducationCycles.education_level_id = ' . $currentLevelId
//                ]);
//
//                $nextProgrammeOptions = $EducationSystems
//                        ->find('list', [
//                            'keyField' => 'programme_id',
//                            'valueField' => 'cycle_programme_name'
//                        ])
//                        ->matching('EducationLevels.EducationCycles.EducationProgrammes')
//                        ->select(['cycle_programme_name' => $EducationSystems->find()->func()->concat([
//                                'EducationSystems.name' => 'literal',
//                                ' - ',
//                                'EducationCycles.name' => 'literal',
//                                ' - (',
//                                'EducationProgrammes.name' => 'literal',
//                                ')'
//                            ]), 'programme_id' => 'EducationProgrammes.id'])
//                        ->where([
//                            $EducationSystems->aliasField('academic_period_id') => $academic_period_id,
//                            'EducationLevels.order >= ' => $currentLevelOrder,
//                            'NOT EXISTS(' . $excludedProgrammes->where([$educationProgrammesTable->aliasField('id') . ' = ' . 'EducationProgrammes.id']) . ')'
//                        ])
//                        ->orderAsc('EducationSystems.order')
//                        ->orderAsc('EducationLevels.order')
//                        ->orderAsc('EducationCycles.order')
//                        ->orderAsc('EducationProgrammes.order')
//                        ->toArray();
//
//                $tableHeaders = [__('Cycle - (Programme)'), '', ''];
//                $tableCells = [];
//                $cellCount = 0;
//
//                $arrayNextProgrammes = [];
//                if ($this->request->is(['get'])) {
//                    $educationProgramme = TableRegistry::get('Education.EducationProgrammes');
//                    foreach ($nextProgrammeslist as $next_programme_id) {
//                        $programme = $educationProgramme->find()->where([$educationProgramme->aliasField('id') => $next_programme_id])->contain(['EducationCycles'])->first();
//                        $arrayNextProgrammes[] = [
//                            'id' => $programme->id,
//                            'education_programme_id' => $programme->education_programme_id,
//                            'next_programme_id' => $next_programme_id,
//                            'name' => $programme->cycle_programme_name
//                        ];
//                    }
//                } else if ($this->request->is(['post', 'put'])) {
//                    $requestData = $this->request->getData();
//                    if (array_key_exists('education_next_programmes', $requestData[$this->getAlias()])) {
//                        foreach ($requestData[$this->getAlias()]['education_next_programmes'] as $key => $obj) {
//                            $arrayNextProgrammes[] = $obj['_joinData'];
//                        }
//                    }
//                    if (array_key_exists('next_programme_id', $requestData[$this->getAlias()])) {
//                        $nextProgrammeId = $requestData[$this->getAlias()]['next_programme_id'];
//                        $programmeObj = $this
//                                ->find()
//                                ->where([$this->aliasField('id') => $nextProgrammeId])
//                                ->first();
//
//                        // POCOR-4002 adding the checking to prevent adding empty next programme
//                        if (!empty($programmeObj)) {
//                            $arrayNextProgrammes[] = [
//                                'education_programme_id' => $entity->id,
//                                'next_programme_id' => $programmeObj->id,
//                                'name' => $programmeObj->cycle_programme_name,
//                            ];
//                        }
//                        // end POCOR-4002
//                    }
//                }
//                $form->unlockField($attr['model'] . '.education_next_programmes');
//                foreach ($arrayNextProgrammes as $key => $obj) {
//                    $fieldPrefix = $attr['model'] . '.education_next_programmes.' . $cellCount++;
//                    $joinDataPrefix = $fieldPrefix . '._joinData';
//
//                    $educationProgrammeId = $obj['next_programme_id'];
//                    $nextProgrammeName = $obj['name'];
//
//                    $cellData = "";
//                    $cellData .= $form->hidden($fieldPrefix . ".id", ['value' => $educationProgrammeId]);
//                    $cellData .= $form->hidden($joinDataPrefix . ".name", ['value' => $nextProgrammeName]);
//                    $cellData .= $form->hidden($joinDataPrefix . ".education_programme_id", ['value' => $obj['education_programme_id']]);
//                    $cellData .= $form->hidden($joinDataPrefix . ".next_programme_id", ['value' => $obj['next_programme_id']]);
//                    if (isset($obj['id'])) {
//                        $cellData .= $form->hidden($joinDataPrefix . ".id", ['value' => $obj['id']]);
//                    }
//
//                    $rowData = [];
//                    $rowData[] = $nextProgrammeName;
//                    $rowData[] = $cellData;
//                    $rowData[] = $this->getDeleteButton();
//
//                    $tableCells[] = $rowData;
//                    unset($nextProgrammeOptions[$obj['next_programme_id']]);
//                }
//
//                $attr['tableHeaders'] = $tableHeaders;
//                $attr['tableCells'] = $tableCells;
//
//                $nextProgrammeOptions[0] = "-- " . __('Add Next Programme') . " --";
//                ksort($nextProgrammeOptions);
//                $attr['options'] = $nextProgrammeOptions;
//            }
//        }
//
//        return $event->getSubject()->renderElement('Education.next_programmes', ['attr' => $attr]);
//    }

    public function onGetCustomNextProgrammeElement(Event $event, $action, $entity, $attr, $options = [])
    {
        $EducationProgrammesNextProgrammes = TableRegistry::get('Education.EducationProgrammesNextProgrammes');

        switch ($action) {
            case 'index':
                $attr['value'] = $this->countNextProgrammes($entity->id);
                break;

            case 'view':
                $attr = $this->buildViewTable($entity, $attr);
                break;

            case 'edit':
            case 'add':
                $attr = $this->buildEditAddTable($event, $entity, $attr);
                break;
        }

        return $event->getSubject()->renderElement('Education.next_programmes', ['attr' => $attr]);
    }

    private function countNextProgrammes(int $programmeId): int
    {
        return TableRegistry::get('Education.EducationProgrammesNextProgrammes')
            ->find()
            ->where(['education_programme_id' => $programmeId])
            ->count();
    }

    private function buildViewTable($entity, array $attr): array
    {
        $attr['tableHeaders'] = [__('Cycle - (Programme)')];
        $attr['tableCells'] = [];

        $nextProgrammes = $entity->education_next_programmes ?? [];

        foreach ($nextProgrammes as $item) {
            if (empty($item->_joinData?->next_programme_id)) {
                continue;
            }

            $programme = $this->find()
                ->contain(['EducationCycles'])
                ->where([$this->aliasField('id') => $item->_joinData->next_programme_id])
                ->first();

            if ($programme) {
                $attr['tableCells'][] = [$programme->cycle_programme_name];
            }
        }

        return $attr;
    }

    private function buildEditAddTable(Event $event, $entity, array $attr): array
    {
        $form = $event->getSubject()->Form;
        $cycleId = $this->request->getData('EducationProgrammes.education_cycle_id');
        if (empty($cycleId)) {
            return $attr;
        }

        [$academicPeriodId, $cycleInfo] = $this->getCycleAndLevelInfo($cycleId);
        if (empty($cycleInfo)) {
            return $attr;
        }

        $nextProgrammeOptions = $this->getNextProgrammeOptions($cycleInfo, $academicPeriodId);
        $arrayNextProgrammes = $this->collectSelectedNextProgrammes($entity);

        // Build table rows
        [$headers, $cells] = $this->buildProgrammeTableRows($form, $attr['model'], $arrayNextProgrammes, $nextProgrammeOptions);

        $attr['tableHeaders'] = $headers;
        $attr['tableCells'] = $cells;

        // Add "Add Next Programme" dropdown
        $nextProgrammeOptions[0] = "-- " . __('Add Next Programme') . " --";
        ksort($nextProgrammeOptions);
        $attr['options'] = $nextProgrammeOptions;

        return $attr;
    }

    private function getCycleAndLevelInfo($cycleId): array
    {
        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $EducationCycles  = TableRegistry::getTableLocator()->get('Education.EducationCycles');
        $EducationLevels  = TableRegistry::getTableLocator()->get('Education.EducationLevels');
        $EducationSystems = TableRegistry::getTableLocator()->get('Education.EducationSystems');

        // Get current academic period
        $academicPeriodId = $AcademicPeriods->getCurrent();

        // Build query explicitly (no contain)
        $cycleRecord = $EducationCycles->find()
            ->select([
                'education_level_id' => $EducationCycles->aliasField('education_level_id'),
                'cycle_order'        => $EducationCycles->aliasField('order'),
                'level_order'        => $EducationLevels->aliasField('order'),
                'level_id'           => $EducationLevels->aliasField('id'),
                'system_id'          => $EducationSystems->aliasField('id'),
            ])
            ->innerJoin(
                [$EducationLevels->getAlias() => $EducationLevels->getTable()],
                [
                    $EducationLevels->aliasField('id =') . $EducationCycles->aliasField('education_level_id'),
                    $EducationLevels->aliasField('visible') => 1,
                ]
            )
            ->innerJoin(
                [$EducationSystems->getAlias() => $EducationSystems->getTable()],
                [
                    $EducationSystems->aliasField('id =') . $EducationLevels->aliasField('education_system_id'),
                    $EducationSystems->aliasField('visible') => 1,
                ]
            )
            ->where([$EducationCycles->aliasField('id') => $cycleId])
            ->enableHydration(false)
            ->first();

        return [$academicPeriodId, $cycleRecord];
    }

    private function getNextProgrammeOptions($cycleInfo, $academicPeriodId): array
    {
        $EducationSystems   = TableRegistry::getTableLocator()->get('Education.EducationSystems');
        $EducationLevels    = TableRegistry::getTableLocator()->get('Education.EducationLevels');
        $EducationCycles    = TableRegistry::getTableLocator()->get('Education.EducationCycles');
        $EducationProgrammes = TableRegistry::getTableLocator()->get('Education.EducationProgrammes');
        $EducationProgrammes->setAlias('EducationProgrammesClone');

        // Build the excluded subquery
        $excluded = $EducationProgrammes->find()
            ->innerJoin(
                [$EducationCycles->getAlias() => $EducationCycles->getTable()],
                [
                    $EducationCycles->aliasField('id =') . $EducationProgrammes->aliasField('education_cycle_id'),
                ]
            )
            ->select([$EducationProgrammes->aliasField('id')])
            ->where([
                $EducationCycles->aliasField('order <=')        => $cycleInfo->cycle_order,
                $EducationCycles->aliasField('education_level_id') => $cycleInfo->level_id,
            ]);

        // Main query building next-programme options
        $query = $EducationSystems->find('list', [
            'keyField'   => 'programme_id',
            'valueField' => 'cycle_programme_name',
        ])
            ->find('visible')
            ->innerJoin(
                [$EducationLevels->getAlias() => $EducationLevels->getTable()],
                [
                    $EducationLevels->aliasField('education_system_id =') . $EducationSystems->aliasField('id'),
                    $EducationLevels->aliasField('visible') => 1,
                ]
            )
            ->innerJoin(
                [$EducationCycles->getAlias() => $EducationCycles->getTable()],
                [
                    $EducationCycles->aliasField('education_level_id =') . $EducationLevels->aliasField('id'),
                    $EducationCycles->aliasField('visible') => 1,
                ]
            )
            ->innerJoin(
                [$EducationProgrammes->getAlias() => $EducationProgrammes->getTable()],
                [
                    $EducationProgrammes->aliasField('education_cycle_id =') . $EducationCycles->aliasField('id'),
                    $EducationProgrammes->aliasField('visible') => 1,
                ]
            )
            ->select([
                'programme_id' => $EducationProgrammes->aliasField('id'),
                'cycle_programme_name' => $EducationSystems->find()->func()->concat([
                    $EducationSystems->aliasField('name'),
                    ' - ',
                    $EducationCycles->aliasField('name'),
                    ' - (',
                    $EducationProgrammes->aliasField('name'),
                    ')',
                ]),
            ])
            ->where([
                $EducationSystems->aliasField('academic_period_id') => $academicPeriodId,
                $EducationLevels->aliasField('order >=')            => $cycleInfo->level_order,
                'NOT EXISTS (' . $excluded->where([
                    $EducationProgrammes->aliasField('id') . ' = ' . $EducationProgrammes->aliasField('id'),
                ]) . ')',
            ])
            ->order([
                $EducationSystems->aliasField('order')    => 'ASC',
                $EducationLevels->aliasField('order')     => 'ASC',
                $EducationCycles->aliasField('order')     => 'ASC',
                $EducationProgrammes->aliasField('order') => 'ASC',
            ])
            ->enableHydration(false)
            ->toArray();

        return $query;
    }

    private function buildProgrammeTableRows($form, string $model, array $programmes, array &$options): array
    {
        $headers = [__('Cycle - (Programme)'), '', ''];
        $cells = [];
        $count = 0;

        foreach ($programmes as $obj) {
            $prefix = $model . ".education_next_programmes.{$count}";
            $joinPrefix = "{$prefix}._joinData";

            $id = $obj['next_programme_id'] ?? null;
            $name = $obj['name'] ?? null;

            $hidden = implode('', [
                $form->hidden("{$prefix}.id", ['value' => $id]),
                $form->hidden("{$joinPrefix}.name", ['value' => $name]),
                $form->hidden("{$joinPrefix}.education_programme_id", ['value' => $obj['education_programme_id'] ?? null]),
                $form->hidden("{$joinPrefix}.next_programme_id", ['value' => $obj['next_programme_id'] ?? null]),
            ]);

            $cells[] = [$name, $hidden, $this->getDeleteButton()];
            unset($options[$id]);
            $count++;
        }

        return [$headers, $cells];
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
        // to be revisit
        // $data[$this->alias()]['setVisible'] = true;
        // To handle when delete all programmes
        if (!array_key_exists('education_next_programmes', $data[$this->getAlias()])) {
            $data[$this->getAlias()]['education_next_programmes'] = [];
        }
        // Required by patchEntity for associated data
        $newOptions = [];
        $newOptions['associated'] = $this->_contain;

        $arrayOptions = $options->getArrayCopy();
        $arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
        $options->exchangeArray($arrayOptions);
    }

    public function viewEditBeforeQuery(Event $event, Query $query) {
        $query->contain(['EducationNextProgrammes']);
    }

    public function getEducationProgrammesList($educationLevelId) {
        return $this->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
                        ->find('visible')
                        ->contain(['EducationCycles'])
                        ->where([
                            'EducationCycles.education_level_id' => $educationLevelId
                        ])
                        ->order(['EducationCycles.order' => 'ASC', $this->aliasField('order') => 'ASC'])
                        ->toArray();
    }
    //POCOR-4746 start
    public function onUpdateFieldSameGradePromotion(Event $event, array $attr, $action, ServerRequest $request) {
        $options = [1 => "Enabled", 0 => "Disabled"];
        $attr['options'] = $options;
        $attr['onChangeReload'] = 'changeCurrent';
        return $attr;
    }

    public function onGetSameGradePromotion(Event $event, Entity $entity)
    {
       if($entity->same_grade_promotion==1){
          return $entity->same_grade_promotion="Enabled";
       }
       if($entity->same_grade_promotion==0){
          return $entity->same_grade_promotion="Disabled";
       }
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(Event $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'name') {
            return __('Name');
        }elseif ($field == 'code') {
            return __('Code');
        } elseif ($field == 'education_field_of_study_id') {
            return __('Education field of Study');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        }elseif ($field == 'duration') {
            return __('Duration');
        }elseif ($field == 'visible') {
            return __('Visible');
        }elseif ($field == 'education_cycle_id') {
            return __('Education Cycle');
        }elseif ($field == 'education_certification_id') {
            return __('Education Certifications');
        }elseif ($field == 'same_grade_promotion') {
            return __('Same Grade Promotion');
        }elseif ($field == 'next_programmes') {
            return __('Next Programme');
        }else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    // POCOR-8507
    public function onBeforeDelete(Event $event, Entity $entity, ArrayObject $extra) {
        if ($this->hasAssociatedRecords($this, $entity, $extra)) {
            $this->Alert->error('general.delete.restrictDeleteBecauseAssociation', ['reset' => true]);
            $event->stopPropagation();
            return $this->controller->redirect($this->url('remove'));
        }
    }
}
