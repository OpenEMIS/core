<?php

namespace Education\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
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
    private  $arrayNextProgrammes = []; // POCOR-9403
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
        $this->addBehavior('Configuration.CallWebhook', // POCOR-9403
            [
                'entity_create' => 'education_programme_create',
                'entity_delete' => 'education_programme_delete',
                'entity_update' => 'education_programme_update',
                'table_alias' => 'Education.EducationProgrammes',
                'contain' => ['EducationNextProgrammes']
            ]
        ); // for webhook

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

    public function beforeAction(EventInterface $event, ArrayObject $extra) {
        if ($this->action != 'index') {
            $this->field('next_programmes', ['type' => 'custom_next_programme', 'valueClass' => 'table-full-width','after'=>'same_grade_promotion']);
            $this->field('same_grade_promotion');//POCOR-4746
            $this->_fieldOrder[] =['next_programmes'];
        }
    }

    public function afterAction(EventInterface $event, ArrayObject $extra) {
        $this->setFieldOrder($this->_fieldOrder);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra) {
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


    public function afterDelete(EventInterface $event, Entity $entity, ArrayObject $options): void
    {
        // Always perform related child cleanup
        $this->deleteChildProgrammes($entity); // POCOR-9403 cleancoded

    }

    //
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

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra) {
        $serverRequest = $this->request;
        /*list($academicPeriodOptions, $selectedAcademicPeriod, $levelOptions, $selectedLevel, $cycleOptions, $selectedCycle) = array_values($this->getSelectOptions());
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod','levelOptions', 'selectedLevel', 'cycleOptions', 'selectedCycle'));
        $extra['elements']['controls'] = ['name' => 'Education.controls', 'data' => [], 'options' => [], 'order' => 1];
        $query->where([$this->aliasField('education_cycle_id') => $selectedCycle]);*/

        // Academic period filter
        $EducationSystems = TableRegistry::getTableLocator()->get('Education.EducationSystems');
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

    public function addEditBeforeAction(EventInterface $event, ArrayObject $extra) {
        $this->field('education_cycle_id');
        $this->fields['education_field_of_study_id']['type'] = 'select';
        $this->fields['education_certification_id']['type'] = 'select';
        $this->fields['same_grade_promotion']['type'] = 'select';//POCOR-4746
    }

    public function onUpdateFieldEducationCycleId(EventInterface $event, array $attr, $action, ServerRequest $request) {
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

    public function deleteOnInitialize(EventInterface $event, Entity $entity, Query $query, ArrayObject $extra) {
        $query->where([$this->aliasField('education_cycle_id') => $entity->education_cycle_id]);
    }

    public function findWithCycle(Query $query, array $options) {
        return $query
                        ->contain(['EducationCycles'])
                        ->order(['EducationCycles.order' => 'ASC', $this->aliasField('order') => 'ASC']);
    }

    public function findAvailableProgrammes(Query $query, array $options) {
        $EducationCycles = TableRegistry::getTableLocator()->get('Education.EducationCycles');
        $EducationLevels = TableRegistry::getTableLocator()->get('Education.EducationLevels');

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
        $EducationSystems = TableRegistry::getTableLocator()->get('Education.EducationSystems');
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

    // POCOR-9403 cleancoded

    public function onGetCustomNextProgrammeElement(EventInterface $event, $action, $entity, $attr, $options = [])
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

    private function buildEditAddTable(EventInterface $event, $entity, array $attr): array
    {
        $requestData = $this->request->getData()[$this->getAlias()] ?? [];
        $form = $event->getSubject()->Form;
        $cycleId = $requestData['education_cycle_id'];
        if (empty($cycleId)) {
            $cycleId = $entity->education_cycle_id;
        }
        if (empty($cycleId)) {
            return $attr;
        }

        [$academicPeriodId, $cycleInfo] = $this->getCycleAndLevelInfo($cycleId);
        if (empty($cycleInfo)) {

            return $attr;
        }

        $nextProgrammeOptions = $this->getNextProgrammeOptions($cycleInfo, $academicPeriodId);

        $arrayNextProgrammes = $this->collectSelectedNextProgrammes($entity);
//        dd($nextProgrammeOptions);
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

    /**
     * Collects selected next programmes from the entity or request data.
     * Supports cumulative additions across reloads.
     */
    /**
     * Collects selected next programmes from the entity or request data.
     * Supports cumulative additions and caches within request.
     */
    private function collectSelectedNextProgrammes($entity): array
    {
        // Initialize persistent cache per request
        if (!property_exists($this, 'arrayNextProgrammes')) {
            $this->arrayNextProgrammes = [];
        }

        $arrayNextProgrammes = $this->arrayNextProgrammes;

        $EducationProgrammes = TableRegistry::getTableLocator()->get('Education.EducationProgrammes');
        $EducationProgrammesNext = TableRegistry::getTableLocator()->get('Education.EducationProgrammesNextProgrammes');

        // Load existing ones from DB (for edit mode or first GET)
        if (empty($arrayNextProgrammes) && !empty($entity->id)) {
            $existingNextIds = $EducationProgrammesNext
                ->find('list', [
                    'keyField'   => 'id',
                    'valueField' => 'next_programme_id',
                ])
                ->where([
                    $EducationProgrammesNext->aliasField('education_programme_id') => $entity->id,
                ])
                ->toArray();

            if ($existingNextIds) {
                $existingProgrammes = $EducationProgrammes->find()
                    ->where([$EducationProgrammes->aliasField('id IN') => array_values($existingNextIds)])
                    ->contain(['EducationCycles'])
                    ->all();

                foreach ($existingProgrammes as $programme) {
                    $arrayNextProgrammes[$programme->id] = [
                        'id'                     => $programme->id,
                        'education_programme_id' => $entity->id,
                        'next_programme_id'      => $programme->id,
                        'name'                   => $programme->cycle_programme_name,
                    ];
                }
            }
            $this->arrayNextProgrammes = $arrayNextProgrammes;
        }

        // If POST / PUT — merge what's in the current form (hidden fields)
        if ($this->request->is(['post', 'put'])) {
            $requestData = (array)$this->request->getData();

            // Existing ones already shown in the table
            $posted = $requestData[$this->getAlias()]['education_next_programmes'] ?? [];
//            dd($requestData[$this->getAlias()]);
            foreach ($posted as $obj) {
                if (!empty($obj['_joinData'])) {
                    $joinData = $obj['_joinData'];
                    $id = $joinData['next_programme_id'] ?? null;
                    if ($id && !isset($arrayNextProgrammes[$id])) {
                        $arrayNextProgrammes[$id] = $joinData;
                    }
                }
            }

            // Add the new selection (dropdown)
            $nextProgrammeId = $requestData[$this->getAlias()]['next_programme_id'] ?? null;
            if (!empty($nextProgrammeId) && !isset($arrayNextProgrammes[$nextProgrammeId])) {
                $programmeObj = $EducationProgrammes->find()
                    ->where([$EducationProgrammes->aliasField('id') => $nextProgrammeId])
                    ->contain(['EducationCycles'])
                    ->first();

                if ($programmeObj) {
                    $arrayNextProgrammes[$programmeObj->id] = [
                        'education_programme_id' => $entity->id,
                        'next_programme_id'      => $programmeObj->id,
                        'name'                   => $programmeObj->cycle_programme_name,
                    ];
                }
            }
        }

        // Cache for next use within same request
        $this->arrayNextProgrammes = $arrayNextProgrammes;

        // Return flat array for table rendering
        return array_values($arrayNextProgrammes);
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
        // 1) Build excluded IDs as a simple array [1,2,3]
        $EducationSystems     = TableRegistry::getTableLocator()->get('Education.EducationSystems');
        $EducationLevels      = TableRegistry::getTableLocator()->get('Education.EducationLevels');
        $EducationCycles      = TableRegistry::getTableLocator()->get('Education.EducationCycles');
        $EducationProgrammes  = TableRegistry::getTableLocator()->get('Education.EducationProgrammes');

// If you previously aliased the table as "EducationProgrammesClone", keep it consistent
//        $EducationProgrammes->setAlias('EducationProgrammesClone');

        $excludedIds = $EducationProgrammes->find()
            ->innerJoin(
                [$EducationCycles->getAlias() => $EducationCycles->getTable()],
                [
                    $EducationCycles->aliasField('id =') . $EducationProgrammes->aliasField('education_cycle_id'),
                ]
            )
            ->select(['id' => $EducationProgrammes->aliasField('id')]) // alias to plain "id" for easy extract()
            ->where([
                $EducationCycles->aliasField('order <=')          => $cycleInfo['cycle_order'],
                $EducationCycles->aliasField('education_level_id') => $cycleInfo['level_id'],
            ])
            ->enableHydration(false)
            ->all()
            ->extract('id')
            ->toList(); // -> e.g. [1,2,3]

// 2) Main query using NOT IN (only if list is non-empty)
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
                'cycle_programme_name' => $EducationSystems->query()->newExpr()->add([
                    'CONCAT(' .
                    $EducationSystems->aliasField('name') . ', " - ", ' .
                    $EducationCycles->aliasField('name') . ', " - (", ' .
                    $EducationProgrammes->aliasField('name') . ', ")"' .
                    ')'
                ])
            ])
            ->where([
                $EducationSystems->aliasField('academic_period_id') => $academicPeriodId,
                $EducationLevels->aliasField('order >=')            => $cycleInfo['level_order'],
            ])
            ->order([
                $EducationSystems->aliasField('order')    => 'ASC',
                $EducationLevels->aliasField('order')     => 'ASC',
                $EducationCycles->aliasField('order')     => 'ASC',
                $EducationProgrammes->aliasField('order') => 'ASC',
            ]);

// Add NOT IN only when needed (avoid empty IN () SQL)
        if (!empty($excludedIds)) {
            $query->andWhere([
                $EducationProgrammes->aliasField('id NOT IN') => $excludedIds,
            ]);
        }

        $result = $query->enableHydration(false)->toArray();

        return $result;
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

    public function addEditBeforePatch(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
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

    public function viewEditBeforeQuery(EventInterface $event, Query $query) {
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
    public function onUpdateFieldSameGradePromotion(EventInterface $event, array $attr, $action, ServerRequest $request) {
        $options = [1 => "Enabled", 0 => "Disabled"];
        $attr['options'] = $options;
        $attr['onChangeReload'] = 'changeCurrent';
        return $attr;
    }

    public function onGetSameGradePromotion(EventInterface $event, Entity $entity)
    {
       if($entity->same_grade_promotion==1){
          return $entity->same_grade_promotion="Enabled";
       }
       if($entity->same_grade_promotion==0){
          return $entity->same_grade_promotion="Disabled";
       }
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(EventInterface $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
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
    public function onBeforeDelete(EventInterface $event, Entity $entity, ArrayObject $extra) {
        if ($this->hasAssociatedRecords($this, $entity, $extra)) {
            $this->Alert->error('general.delete.restrictDeleteBecauseAssociation', ['reset' => true]);
            $event->stopPropagation();
            return $this->controller->redirect($this->url('remove'));
        }
    }
}
