<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;
use Cake\Utility\Text;

class StudentCompetencyResultsTable extends ControllerActionTable {
    public function initialize(array $config) {
        $this->table('competency_results');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('CompetencyTemplates', ['className' => 'Competency.Templates', 'foreignKey' => ['competency_template_id', 'academic_period_id']]);
        $this->belongsTo('CompetencyItems', ['className' => 'Competency.Items', 'foreignKey' => ['competency_item_id', 'academic_period_id']]);
        $this->belongsTo('CompetencyCriterias', ['className' => 'Competency.Criterias', 'foreignKey' => ['competency_criteria_id', 'academic_period_id']]);
        $this->belongsTo('CompetencyPeriods', ['className' => 'Competency.Periods', 'foreignKey' => ['competency_period_id', 'academic_period_id']]);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Students', ['className' => 'User.Users', 'foreignKey' => 'student_id']);

        $this->classId = $this->getQueryString('class_id');
        $this->competencyTemplateId = $this->getQueryString('competency_template_id');
        $this->institutionId = $this->getQueryString('institution_id');
        $this->academicPeriodId = $this->getQueryString('academic_period_id');

        $this->InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $this->StudentClasses = TableRegistry::get('Student.StudentClasses');
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.viewResults'] = 'viewResults';
        $events['Model.InstitutionClassStudents.afterDelete'] = 'institutionClassStudentsAfterDelete';
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra) {
        $action = $this->action;
        switch ($action) {
            case 'viewResults':
                $extra['elements']['view'] = ['name' => 'OpenEmis.ControllerAction/view', 'order' => 5];
                break;
            default:
                break;
        }
    }

    public function viewResults(Event $event, ArrayObject $extra)
    {
        $request = $this->request;
        $this->fields = [];

        //competency items filter control
        $itemOptions = $this->CompetencyItems->getItemByTemplateAcademicPeriod($this->competencyTemplateId, $this->academicPeriodId);
        if (count($itemOptions)) {
            $itemOptions = array(-1 => __('-- Select Competency Item --')) + $itemOptions;
        }
        if ($request->query('item')) {
            $selectedItem = $request->query('item');
        } else {
            $selectedItem = -1;
        }
        $data['itemOptions'] = $itemOptions;
        $data['selectedItem'] = $selectedItem;

        //competency periods filter control
        $selectedPeriod = '';
        if ($selectedItem && $selectedItem > -1) {
            $periodOptions = $this->CompetencyPeriods->getPeriodByTemplateItemAcademicPeriod($this->competencyTemplateId, $selectedItem, $this->academicPeriodId);
            if (count($periodOptions)) {
                $periodOptions = array(-1 => __('-- Select Competency Period --')) + $periodOptions;
            } else {
                $periodOptions = array(-1 => __('No Competency Period')) + $periodOptions;
            }

            if ($request->query('period')) {
                $selectedPeriod = $request->query('period');
            } else {
                $selectedPeriod = -1;
            }

            $this->advancedSelectOptions($periodOptions, $selectedPeriod, [
                'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noResult')),
                'callable' => function($id) use ($selectedItem) {
                    if ($id > 0) {
                        $query = $this
                                ->find()
                                ->where([
                                    $this->aliasField('competency_item_id') => $selectedItem,
                                    $this->aliasField('academic_period_id') => $this->academicPeriodId,
                                    $this->aliasField('competency_template_id') => $this->competencyTemplateId,
                                    $this->aliasField('institution_id') => $this->institutionId,
                                    $this->aliasField('competency_period_id') => $id
                                ]);
                        return $query->count();
                    } else {
                        return true;
                    }
                }
            ]);

            $data['periodOptions'] = $periodOptions;
            $data['selectedPeriod'] = $selectedPeriod;
        }

        $extra['elements']['control'] = [
            'name' => 'Institution.Competencies/view_result_controls',
            'data' => $data,
            'order' => 3
        ];

        //setup fields to show.
        $this->setupFields($this->newEntity());

        $this->field('competency_item_id', ['type' => 'hidden']);
        $this->field('competency_period_id', ['type' => 'hidden']);

        //buttons
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $toolbarAttr = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];

        // back button
        $toolbarButtonsArray['back']['type'] = 'button';
        $toolbarButtonsArray['back']['label'] = '<i class="fa kd-back"></i>';
        $toolbarButtonsArray['back']['attr'] = $toolbarAttr;
        $toolbarButtonsArray['back']['attr']['title'] = __('Back');
        $toolbarButtonsArray['back']['url'] = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentCompetencies'];
        
        // edit button
        $toolbarButtonsArray['edit']['type'] = 'button';
        $toolbarButtonsArray['edit']['label'] = '<i class="fa kd-edit"></i>';
        $toolbarButtonsArray['edit']['attr'] = $toolbarAttr;
        $toolbarButtonsArray['edit']['attr']['title'] = __('Edit');
        $toolbarButtonsArray['edit']['url'] = [
            'plugin' => 'Institution', 
            'controller' => 'Institutions', 
            'action' => 'StudentCompetencyResults',
            '0' => 'add',
            'queryString' => $request->query('queryString'),
            'item' => $selectedItem,
            'period' => $selectedPeriod
        ];
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);

        //to remove warning that data is not exist.
        $this->controller->set('data', $this->newEntity());
        return true;
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);

        $this->field('competency_grading_option_id', ['visible' => false]);

        $extra['toolbarButtons']['back']['url'] = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentCompetencies'];
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'viewResults') {
            $attr['value'] = $this->AcademicPeriods->get($this->academicPeriodId)->name;
        } else {
            $attr['value'] = $this->academicPeriodId;
            $attr['attr']['value'] = $this->AcademicPeriods->get($this->academicPeriodId)->name;
        }   
        return $attr;
    }

    public function onUpdateFieldCompetencyTemplateId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'viewResults') {
            $attr['value'] = $this->CompetencyTemplates->get([$this->competencyTemplateId, $this->academicPeriodId])->code_name;
        } else {
            $attr['value'] = $this->competencyTemplateId;
            $attr['attr']['value'] = $this->CompetencyTemplates->get([$this->competencyTemplateId, $this->academicPeriodId])->code_name;
        }   
        return $attr;
    }

    public function onUpdateFieldCompetencyItemId(Event $event, array $attr, $action, Request $request)
    {
        $itemOptions = $this->CompetencyItems->getItemByTemplateAcademicPeriod($this->competencyTemplateId, $this->academicPeriodId);

        $attr['options'] = $itemOptions;
        $attr['onChangeReload'] = 'changeCompetencyItem';

        if (array_key_exists('item', $this->request->query) && ($this->request->query['item']) && ($this->request->query['item']>-1)) {
            $attr['default'] = $this->request->query['item'];
        }

        if (!count($itemOptions)) {
            $this->Alert->warning('StudentCompetencyResults.noCompetencyItems', ['reset'=>true]);
        }
        
        return $attr;
    }

    public function addEditOnChangeCompetencyItem(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        unset($request->query['item']);
        unset($request->query['period']);
        
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('competency_item_id', $request->data[$this->alias()])) {
                    $request->query['item'] = $request->data[$this->alias()]['competency_item_id'];
                }
            }
        }
    }

    public function onUpdateFieldCompetencyPeriodId(Event $event, array $attr, $action, Request $request)
    {
        $selectedCompetencyItem = '';
        if (array_key_exists('item', $this->request->query) && ($this->request->query['item'])) {
            $selectedCompetencyItem = $this->request->query['item'];
        }

        $periodOptions = [];
        if ($selectedCompetencyItem) {
            $periodOptions = $this->CompetencyPeriods->getPeriodByTemplateItemAcademicPeriod($this->competencyTemplateId, $selectedCompetencyItem, $this->academicPeriodId);
            if (count($periodOptions)) {
                $periodOptions = array(-1 => __('-- Select --')) + $periodOptions;
            }
        }

        if ($selectedCompetencyItem) {
            if ($request->query('period')) {
                $selectedPeriod = $request->query('period');
            } else {
                $selectedPeriod = -1;
            }

            $this->advancedSelectOptions($periodOptions, $selectedPeriod, [
                'message' => '{{label}} - ' . $this->getMessage($this->aliasField('periodNotAvailable')),
                'callable' => function($id) use ($selectedCompetencyItem) {
                    $query = $this->CompetencyPeriods
                            ->find()
                            ->where([
                                $this->CompetencyPeriods->aliasField('competency_item_id') => $selectedCompetencyItem,
                                $this->CompetencyPeriods->aliasField('date_enabled <= ') => date('Y-m-d'),
                                $this->CompetencyPeriods->aliasField('date_disabled > ') => date('Y-m-d')
                            ]);
                    if ($id > 0) {
                        $query = $query
                                ->where([
                                    $this->CompetencyPeriods->aliasField('id') => $id
                                ]);
                    }

                    return $query->count();
                }
            ]);
        }
        $attr['options'] = $periodOptions;
        $attr['onChangeReload'] = 'changeCompetencyPeriod';

        if (array_key_exists('period', $this->request->query) && ($this->request->query['period']) && ($this->request->query['period']>-1)) {
            $attr['default'] = $this->request->query['period'];
        }

        return $attr;
    }

    public function addEditOnChangeCompetencyPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        unset($request->query['period']);
        
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('competency_item_id', $request->data[$this->alias()])) {
                    $request->query['item'] = $request->data[$this->alias()]['competency_item_id'];
                }
            }

            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('competency_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['competency_period_id'];
                }
            }
        }
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'viewResults') {
            $attr['value'] = $this->Institutions->get([$this->institutionId])->code_name;
        } else {
            $attr['attr']['value'] = $this->Institutions->get([$this->institutionId])->code_name;
            $attr['value'] = $this->institutionId;
        }            
        return $attr;
    }

    public function onUpdateFieldInstitutionClassId(Event $event, array $attr, $action, Request $request)
    {
        $institutionClass = $this->InstitutionClasses->find()
                            ->where([
                                $this->InstitutionClasses->aliasField('id') => $this->classId,
                                $this->InstitutionClasses->aliasField('institution_id') => $this->institutionId,
                                $this->InstitutionClasses->aliasField('academic_period_id') => $this->academicPeriodId
                            ])
                            ->first();
        // pr($institutionClass->toArray());
        

        if ($action == 'viewResults') {
            $attr['value'] = $institutionClass['name'];
        } else {
            $attr['attr']['value'] = $institutionClass['name'];
            $attr['value'] = $institutionClass['id'];
        }

        return $attr;
    }

    public function onGetCustomCompetencyResultsElement(Event $event, $action, $entity, $attr, $options=[])
    {
        if (array_key_exists('item', $this->request->query) && ($this->request->query['item']) && ($this->request->query['item']>-1)) {

            $selectedCompetencyItem = $this->request->query['item'];

            $criteriaList = $this->CompetencyCriterias->getCompetencyCriterias($selectedCompetencyItem, $this->academicPeriodId);
            // pr($criteriaList);
            if (!empty($criteriaList)) {

                if (array_key_exists('period', $this->request->query) && ($this->request->query['period']) && ($this->request->query['period']>-1)) {

                    $selectedCompetencyPeriod = $this->request->query['period'];

                    //get existing result so saved value can be maintained
                    $existingCompetencyResult = $this->getExistingCompetencyResult($this->competencyTemplateId, $selectedCompetencyItem, $selectedCompetencyPeriod, $this->academicPeriodId, $this->institutionId);

                    //fix header
                    $tableHeaders[] = _('OpenEMIS ID');
                    $tableHeaders[] = _('Student');
                    //dynamic header based on the criterias set up.
                    foreach ($criteriaList as $key => $value) {
                        $tableHeaders[] = 
                            substr(__($value->name), 0, 30) . '...' .
                            "<div class='tooltip-desc' style='display: inline-block;'>
                                <i class='fa fa-info-circle fa-lg table-tooltip icon-blue' tooltip-placement='top' uib-tooltip='" .  __($value->name) . "' tooltip-append-to-body='true' tooltip-class='tooltip-blue'></i>
                            </div>";
                    }

                    $alias = $this->alias();

                    $fieldKey = 'competency_results';
                    $Form = $event->subject()->Form;

                    $studentList = $this->StudentClasses->getClassStudents($this->classId, $this->academicPeriodId, $this->institutionId);
                    
                    if ((!empty($studentList)) && ($selectedCompetencyPeriod > -1) && ($selectedCompetencyItem > -1)) {
                        $tableCells = [];
                        foreach ($studentList as $key => $value) { //loop through student
                            $studentId = $value->student_id;
                            $rowData = [];
                            
                            $rowData[] = $value->user->openemis_no;
                            $rowData[] = $value->user->name;
                            foreach ($criteriaList as $key1 => $value1) { //loop through criterias
                                $criteriaId = $value1->id;
                                $gradingOptions = $value1->grading_type->grading_options;
                                if (count($gradingOptions)){ //for grading type with option
                                    $optionList = [];
                                    foreach ($gradingOptions as $key2 => $value2) { //build up the options
                                        $optionList[$value2->id] = $value2->name;
                                    }
                                    $optionList = array(-1 => __('-- Select --')) + $optionList; //put select on top
                                    $selectedGradingOption = -1;
                                    if (count($optionList)) {
                                        //check existing array for saved value, set default selected grading option
                                        if (array_key_exists($studentId, $existingCompetencyResult)) {
                                            if (array_key_exists($criteriaId, $existingCompetencyResult[$studentId])) {
                                                $selectedGradingOption = $existingCompetencyResult[$studentId][$criteriaId]['grading_option_id'];
                                                // pr($selectedGradingOption);
                                            }
                                        }
                                        
                                        if ($action == 'view') {
                                            if ($selectedGradingOption == -1) {
                                                // $rowData[] = __($this->getMessage($this->aliasField('noResult')));
                                                $rowData[] = '-';
                                            } else {
                                                $rowData[] = $optionList[$selectedGradingOption];
                                            }
                                        
                                        } else if ($action == 'edit') {
                                            $rowData[] = $Form
                                                        ->input("$alias.$fieldKey.$studentId.$criteriaId.$selectedCompetencyPeriod.grading_option_id", [
                                                                'type' => 'select', 'label' => false, 
                                                                'options' => $optionList, 'default' => $selectedGradingOption
                                                        ]);
                                        }
                                    } 
                                }
                            }
                            $tableCells[] = $rowData;
                        }

                        $attr['tableHeaders'] = $tableHeaders;
                        $attr['tableCells'] = $tableCells;

                        return $event->subject()->renderElement('Institution.Competencies/'.$fieldKey, ['attr' => $attr]);
                    } else {
                        $this->Alert->warning('StudentCompetencyResults.noClassStudents', ['reset'=>true]);
                    }
                }
            } else {
                $this->Alert->warning('StudentCompetencyResults.noCompetencyCriterias', ['reset'=>true]);
            }
        }
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $options['validate'] = false; //remove all validation since insertion will be done manually.
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        //redefine after save redirect.
        $extra['redirect'] = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentCompetencies', 'index'];

        $process = function ($model, $entity) use ($data) {
            $newEntities = [];

            if (array_key_exists('competency_results', $data[$this->alias()])) {

                $dataResults = $data[$this->alias()];
                $results = $dataResults['competency_results'];

                //detect data without selected value.
                foreach ($results as $student => $result) {
                    foreach ($result as $criteria => $gradingOption) {
                        if ($gradingOption[$dataResults['competency_period_id']]['grading_option_id'] == -1) {
                            unset($results[$student][$criteria]); //it wont be saved

                            //check whether this record has value before, if yes then need to remove that record.
                            $existingCompetencyResult = $this->getExistingCompetencyResult($dataResults['competency_template_id'], $dataResults['competency_item_id'], $dataResults['competency_period_id'], $dataResults['academic_period_id'], $dataResults['institution_id']);
                            
                            if (array_key_exists($student, $existingCompetencyResult)) {
                                if (array_key_exists($criteria, $existingCompetencyResult[$student])) {
                                    //get ID of record to be removed.
                                    $resultId = $existingCompetencyResult[$student][$criteria]['id'];
                                    $this->deleteAll(['id' => $resultId]);
                                }
                            }
                        }
                    }
                }
                
                if (count($results)) {
                    foreach ($results as $student => $result) {
                        if (count($student)) { //if student has criteria filled.
                            foreach ($result as $criteria => $gradingOption) {
                                $obj['id'] = Text::uuid();
                                $obj['competency_grading_option_id'] = $gradingOption[$dataResults['competency_period_id']]['grading_option_id'];
                                $obj['student_id'] = $student;
                                $obj['competency_template_id'] = $dataResults['competency_template_id'];
                                $obj['competency_item_id'] = $dataResults['competency_item_id'];
                                $obj['competency_criteria_id'] = $criteria;
                                $obj['competency_period_id'] = $dataResults['competency_period_id'];
                                $obj['institution_id'] = $dataResults['institution_id'];
                                $obj['academic_period_id'] = $dataResults['academic_period_id'];
                                $newEntities[] = $obj;
                            }
                        }
                    }

                    $success = $this->connection()->transactional(function() use ($newEntities, $entity) {
                        $return = true;
                        foreach ($newEntities as $key => $newEntity) {

                            $studentCompetencyResultEntity = $this->newEntity($newEntity);

                            //check whether student still on the class

                            if (!$this->save($studentCompetencyResultEntity)) {
                                $return = false;
                            }
                        }
                        return $return;
                    });
                    return $success;
                }
            } else { //if no student result added and user try to save
                $entity->errors('competency_results', __('There are no results added'));
                $this->Alert->error('StudentCompetencyResults.noResultsAdded', ['reset'=>true]);
            }
        };
        return $process;
    }

    private function getExistingCompetencyResult($template, $item, $period, $academicPeriod, $institutionId)
    {
        $returnResults = [];
        $existingCompetencyResult = $this->find()
                                    ->where([
                                        $this->aliasField('competency_template_id') => $template,
                                        $this->aliasField('competency_item_id') => $item,
                                        $this->aliasField('competency_period_id') => $period,
                                        $this->aliasField('institution_id') => $institutionId,
                                        $this->aliasField('academic_period_id') => $academicPeriod
                                    ])
                                    ->toArray();

        if (!empty($existingCompetencyResult)) {
            //massage array so can be accessed easier later.
            foreach ($existingCompetencyResult as $key => $value) {
                $returnResults[$value->student_id][$value->competency_criteria_id]['grading_option_id'] = $value->competency_grading_option_id;
                $returnResults[$value->student_id][$value->competency_criteria_id]['id'] = $value->id;
            }
        }

        return $returnResults;
    }

    private function setupFields(Entity $entity)
    {
        $this->field('academic_period_id', [
            'type' => 'readonly', 
            'entity' => $entity
        ]);

        $this->field('competency_template_id', [
            'type' => 'readonly',
            'entity' => $entity
        ]);

        $this->field('competency_item_id', [
            'type' => 'select',
            'entity' => $entity
        ]);

        $this->field('competency_period_id', [
            'type' => 'select',
            'select' => false,
            'entity' => $entity
        ]);

        $this->field('institution_id', [
            'type' => 'readonly', 
            'entity' => $entity
        ]);

        $this->field('institution_class_id', [
            'type' => 'readonly', 
            'entity' => $entity
        ]);

        $this->field('competency_results', [
            'type' => 'custom_competency_results',
            'valueClass' => 'table-full-width'
        ]);

        $fieldOrder = [
            'academic_period_id', 'competency_template_id', 'competency_item_id', 'institution_id', 'institution_class_id'
        ];
    }

    public function institutionClassStudentsAfterDelete(Event $event, Entity $entity)
    {
        // $this->log($entity, 'debug');
        $removeCompetencyResults = $this->find()
                                    ->where([
                                        $this->aliasField('student_id') => $entity->student_id,
                                        $this->aliasField('institution_id') => $entity->institution_id,
                                        $this->aliasField('academic_period_id') => $entity->academic_period_id
                                    ])
                                    ->toArray();
        foreach ($removeCompetencyResults as $key => $value) {
            $this->delete($value);
        }
    }
}
