<?php

namespace Education\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Traits\HtmlTrait;
use App\Model\Table\ControllerActionTable;

class EducationProgrammesTable extends ControllerActionTable {

    use HtmlTrait;

    private $_contain = ['EducationNextProgrammes._joinData'];
    private $_fieldOrder = ['code', 'name', 'duration', 'visible', 'education_field_of_study_id', 'education_cycle_id', 'education_certification_id'];

    public function initialize(array $config) {
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
            $this->behaviors()->get('Reorder')->config([
                'filter' => 'education_cycle_id',
            ]);
        }

        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);
        return $validator
                        ->add('code', 'ruleUnique', [
                            'rule' => 'validateUnique',
                            'provider' => 'table'
                        ])
        ;
    }

    public function beforeAction(Event $event, ArrayObject $extra) {
        if ($this->action != 'index') {
            $this->field('next_programmes', ['type' => 'custom_next_programme', 'valueClass' => 'table-full-width']);
            $this->_fieldOrder[] = 'next_programmes';
        }
    }

    public function afterAction(Event $event, ArrayObject $extra) {
        $this->setFieldOrder($this->_fieldOrder);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra) {
        $this->fields['education_field_of_study_id']['sort'] = ['field' => 'EducationFieldOfStudies.name'];
        $this->fields['education_cycle_id']['sort'] = ['field' => 'EducationCycles.name'];
        $this->fields['education_certification_id']['sort'] = ['field' => 'EducationCertifications.name'];
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
        $id = $entity->id;
        $EducationProgrammesNextProgrammesTable = TableRegistry::get('Education.EducationProgrammesNextProgrammes');
        $EducationProgrammesNextProgrammesTable->deleteAll([
            $EducationProgrammesNextProgrammesTable->aliasField('next_programme_id') => $id
        ]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
        /*list($academicPeriodOptions, $selectedAcademicPeriod, $levelOptions, $selectedLevel, $cycleOptions, $selectedCycle) = array_values($this->getSelectOptions());
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod','levelOptions', 'selectedLevel', 'cycleOptions', 'selectedCycle'));
        $extra['elements']['controls'] = ['name' => 'Education.controls', 'data' => [], 'options' => [], 'order' => 1];
        $query->where([$this->aliasField('education_cycle_id') => $selectedCycle]);*/

        // Academic period filter
        $EducationSystems = TableRegistry::get('Education.EducationSystems');
        $academicPeriodOptions = $this->EducationCycles->EducationLevels->EducationSystems->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->EducationCycles->EducationLevels->EducationSystems->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$EducationSystems->aliasField('academic_period_id')] = $selectedAcademicPeriod;

        //level filter
        $levelOptions = $this->EducationCycles->EducationLevels->getEducationLevelOptions($selectedAcademicPeriod);
        if (!empty($levelOptions)) {
            $selectedLevel = !empty($this->request->query('level')) ? $this->request->query('level') : key($levelOptions);
        } else{
            $levelOptions = ['0' => '-- '.__('No Education Level').' --'] + $levelOptions;
            $selectedLevel = !empty($this->request->query('level')) ? $this->request->query('level') : 0;
        }
        
        $this->controller->set(compact('levelOptions', 'selectedLevel'));

        $cycleOptions = $this->EducationCycles
                ->find('list')
                ->find('visible')
                ->find('order')
                ->where([$this->EducationCycles->aliasField('education_level_id') => $selectedLevel])
                ->toArray();
        $selectedCycle = !is_null($this->request->query('cycle')) ? $this->request->query('cycle') : key($cycleOptions);

        $cycleOptions = $cycleOptions;
        if (!empty($cycleOptions)) {
            $selectedCycle = !empty($this->request->query('cycle')) ? $this->request->query('cycle') : key($cycleOptions);
        } else{
            $cycleOptions = ['0' => '-- '.__('No Education Cycle').' --'] + $cycleOptions;
            $selectedCycle = !empty($this->request->query('cycle')) ? $this->request->query('cycle') : 0;    
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
    }

    public function onUpdateFieldEducationCycleId(Event $event, array $attr, $action, Request $request) {
        //POCOR-5908 starts
        list(,,,, $cycleOptions, $selectedCycle) = array_values($this->getSelectOptions());
        //POCOR-5908 ends
        $attr['options'] = $cycleOptions;
        if ($action == 'add') {
            $attr['default'] = $selectedCycle;
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
                                [$EducationCycles->alias() => $EducationCycles->table()], [
                            $EducationCycles->aliasField('id =') . $this->aliasField('education_cycle_id'),
                            $EducationCycles->aliasField('visible') => 1
                                ]
                        )
                        ->innerJoin(
                                [$EducationLevels->alias() => $EducationLevels->table()], [
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
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->EducationCycles->EducationLevels->EducationSystems->AcademicPeriods->getCurrent();
        $where[$EducationSystems->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        
        //Return all required options and their key
        $levelOptions = $this->EducationCycles->EducationLevels->getLevelOptions($selectedAcademicPeriod);

        $selectedLevel = !is_null($this->request->query('level')) ? $this->request->query('level') : key($levelOptions);
         
        $cycleOptions = $this->EducationCycles
                ->find('list')
                ->find('visible')
                ->find('order')
                ->where([$this->EducationCycles->aliasField('education_level_id') => $selectedLevel])
                ->toArray();
        
        $selectedCycle = !is_null($this->request->query('cycle')) ? $this->request->query('cycle') : key($cycleOptions);
        
        return compact('academicPeriodOptions', 'selectedAcademicPeriod', 'levelOptions', 'selectedLevel', 'cycleOptions', 'selectedCycle');
    }

    public function onGetCustomNextProgrammeElement(Event $event, $action, $entity, $attr, $options = []) {
        $EducationProgrammesNextProgrammes = TableRegistry::get('Education.EducationProgrammesNextProgrammes');
        if ($action == 'index') {
            $value = $EducationProgrammesNextProgrammes
                    ->find()
                    ->where([$EducationProgrammesNextProgrammes->aliasField('education_programme_id') => $entity->id])
                    ->count();
            $attr['value'] = $value;
        } else if ($action == 'view') {
            $tableHeaders = [__('Cycle - (Programme)')];
            $tableCells = [];

            $educationNextProgrammes = $entity->extractOriginal(['education_next_programmes']);
            foreach ($educationNextProgrammes['education_next_programmes'] as $key => $obj) {
                if (!is_null($obj->_joinData)) {
                    $programe = $this->find()->where([$this->aliasField('id') => $obj->_joinData->next_programme_id])->contain(['EducationCycles'])->first();
                    $rowData = [];
                    $rowData[] = $programe->cycle_programme_name;
                    $tableCells[] = $rowData;
                }
            }

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
        } else if ($action == 'edit') {
            if (isset($entity->id)) {
                $nextProgrammeslist = $EducationProgrammesNextProgrammes
                        ->find('list', ['keyField' => 'id', 'valueField' => 'next_programme_id'])
                        ->where([$EducationProgrammesNextProgrammes->aliasField('education_programme_id') => $entity->id])
                        ->toArray()
                ;
                $form = $event->subject()->Form;
                $nextProgrammeOptions = [];

                $currentProgrammSystem = $this->find()->contain(['EducationCycles.EducationLevels.EducationSystems'])->where([$this->aliasField('id') => $entity->id])->first();
                $systemId = $currentProgrammSystem->education_cycle->education_level->education_system->id;
                $currentCycleOrder = $currentProgrammSystem->education_cycle->order;
                $currentLevelOrder = $currentProgrammSystem->education_cycle->education_level->order;
                $currentLevelId = $currentProgrammSystem->education_cycle->education_level->id;

                $EducationSystems = TableRegistry::get('Education.EducationSystems');
                $systems = $EducationSystems
                        ->find()
                        ->where([$EducationSystems->aliasField('id') => $systemId])
                        ->contain(['EducationLevels.EducationCycles.EducationProgrammes']);

                $educationProgrammesTable = clone $this;
                $educationProgrammesTable->alias('EducationProgrammesClone');

                $excludedProgrammes = $educationProgrammesTable->find()
                        ->innerJoin(['EducationCycles' => 'education_cycles'], [
                            'EducationCycles.id = ' . $educationProgrammesTable->aliasField('education_cycle_id')
                        ])
                        ->select(1)
                        ->where([
                    'EducationCycles.order <= ' . $currentCycleOrder,
                    'EducationCycles.education_level_id = ' . $currentLevelId
                ]);

                $nextProgrammeOptions = $EducationSystems
                        ->find('list', [
                            'keyField' => 'programme_id',
                            'valueField' => 'cycle_programme_name'
                        ])
                        ->matching('EducationLevels.EducationCycles.EducationProgrammes')
                        ->select(['cycle_programme_name' => $EducationSystems->find()->func()->concat([
                                'EducationCycles.name' => 'literal',
                                ' - (',
                                'EducationProgrammes.name' => 'literal',
                                ')'
                            ]), 'programme_id' => 'EducationProgrammes.id'])
                        ->where([
                            $EducationSystems->aliasField('id') => $systemId,
                            'EducationLevels.order >= ' => $currentLevelOrder,
                            'NOT EXISTS(' . $excludedProgrammes->where([$educationProgrammesTable->aliasField('id') . ' = ' . 'EducationProgrammes.id']) . ')'
                        ])
                        ->toArray();

                $tableHeaders = [__('Cycle - (Programme)'), '', ''];
                $tableCells = [];
                $cellCount = 0;

                $arrayNextProgrammes = [];
                if ($this->request->is(['get'])) {
                    $educationProgramme = TableRegistry::get('Education.EducationProgrammes');
                    foreach ($nextProgrammeslist as $next_programme_id) {
                        $programme = $educationProgramme->find()->where([$educationProgramme->aliasField('id') => $next_programme_id])->contain(['EducationCycles'])->first();
                        $arrayNextProgrammes[] = [
                            'id' => $programme->id,
                            'education_programme_id' => $programme->education_programme_id,
                            'next_programme_id' => $next_programme_id,
                            'name' => $programme->cycle_programme_name
                        ];
                    }
                } else if ($this->request->is(['post', 'put'])) {
                    $requestData = $this->request->data;
                    if (array_key_exists('education_next_programmes', $requestData[$this->alias()])) {
                        foreach ($requestData[$this->alias()]['education_next_programmes'] as $key => $obj) {
                            $arrayNextProgrammes[] = $obj['_joinData'];
                        }
                    }
                    if (array_key_exists('next_programme_id', $requestData[$this->alias()])) {
                        $nextProgrammeId = $requestData[$this->alias()]['next_programme_id'];
                        $programmeObj = $this
                                ->find()
                                ->where([$this->aliasField('id') => $nextProgrammeId])
                                ->first();

                        // POCOR-4002 adding the checking to prevent adding empty next programme
                        if (!empty($programmeObj)) {
                            $arrayNextProgrammes[] = [
                                'education_programme_id' => $entity->id,
                                'next_programme_id' => $programmeObj->id,
                                'name' => $programmeObj->cycle_programme_name,
                            ];
                        }
                        // end POCOR-4002
                    }
                }
                $form->unlockField($attr['model'] . '.education_next_programmes');
                foreach ($arrayNextProgrammes as $key => $obj) {
                    $fieldPrefix = $attr['model'] . '.education_next_programmes.' . $cellCount++;
                    $joinDataPrefix = $fieldPrefix . '._joinData';

                    $educationProgrammeId = $obj['next_programme_id'];
                    $nextProgrammeName = $obj['name'];

                    $cellData = "";
                    $cellData .= $form->hidden($fieldPrefix . ".id", ['value' => $educationProgrammeId]);
                    $cellData .= $form->hidden($joinDataPrefix . ".name", ['value' => $nextProgrammeName]);
                    $cellData .= $form->hidden($joinDataPrefix . ".education_programme_id", ['value' => $obj['education_programme_id']]);
                    $cellData .= $form->hidden($joinDataPrefix . ".next_programme_id", ['value' => $obj['next_programme_id']]);
                    if (isset($obj['id'])) {
                        $cellData .= $form->hidden($joinDataPrefix . ".id", ['value' => $obj['id']]);
                    }

                    $rowData = [];
                    $rowData[] = $nextProgrammeName;
                    $rowData[] = $cellData;
                    $rowData[] = $this->getDeleteButton();

                    $tableCells[] = $rowData;
                    unset($nextProgrammeOptions[$obj['next_programme_id']]);
                }

                $attr['tableHeaders'] = $tableHeaders;
                $attr['tableCells'] = $tableCells;

                $nextProgrammeOptions[0] = "-- " . __('Add Next Programme') . " --";
                ksort($nextProgrammeOptions);
                $attr['options'] = $nextProgrammeOptions;
            }
        }

        return $event->subject()->renderElement('Education.next_programmes', ['attr' => $attr]);
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
        // to be revisit
        // $data[$this->alias()]['setVisible'] = true;
        // To handle when delete all programmes
        if (!array_key_exists('education_next_programmes', $data[$this->alias()])) {
            $data[$this->alias()]['education_next_programmes'] = [];
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

}
