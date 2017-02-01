<?php
namespace Competency\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Collection\Collection;
use Cake\Validation\Validator;
use Cake\View\Helper\UrlHelper;

use App\Model\Traits\OptionsTrait;
use App\Model\Traits\HtmlTrait;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;

class PeriodsTable extends ControllerActionTable {
    use MessagesTrait;
    use HtmlTrait;
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('competency_periods');

        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        // $this->belongsTo('Items',           ['className' => 'Competency.Items', 'foreignKey' => ['competency_item_id', 'academic_period_id']]);
        $this->belongsTo('Templates',       ['className' => 'Competency.Templates', 'foreignKey' => ['competency_template_id', 'academic_period_id']]);

        $this->belongsToMany('CompetencyItems', [
            'className' => 'Competency.Items',
            'joinTable' => 'competency_items_periods',
            'foreignKey' => ['competency_period_id', 'academic_period_id'],
            'targetForeignKey' => ['competency_item_id', 'academic_period_id'],
            'through' => 'Competency.ItemsPeriods',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->hasMany('StudentCompetencyResults', ['className' => 'Institution.StudentCompetencyResults', 'foreignKey' => ['competency_criteria_id', 'academic_period_id']]);
        
        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('code', [
                'ruleUniqueCode' => [
                    'rule' => ['validateUnique', ['scope' => 'academic_period_id']],
                    'provider' => 'table'
                ]
            ])
            ->requirePresence('competency_items')
            ->add('start_date', 'ruleCompareDate', [
                'rule' => ['compareDate', 'end_date', true]
            ])
            ->add('date_enabled', 'ruleCompareDate', [
                'rule' => ['compareDate', 'date_disabled', true]
            ]);
    }


    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $request = $this->request;

        //academic period filter
        list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));
        $extra['selectedPeriod'] = $selectedPeriod;
        $data['periodOptions'] = $periodOptions;
        $data['selectedPeriod'] = $selectedPeriod;

        //template filter
        $templateOptions = $this->Templates->getTemplateByAcademicPeriod($selectedPeriod);

        if ($templateOptions) {
            $templateOptions = array(-1 => __('-- Select Template --')) + $templateOptions;
        }

        if ($request->query('template')) {
            $selectedTemplate = $request->query('template');
        } else {
            $selectedTemplate = -1;
        }

        $extra['selectedTemplate'] = $selectedTemplate;
        $data['templateOptions'] = $templateOptions;
        $data['selectedTemplate'] = $selectedTemplate;

        //item filter
        if ($selectedPeriod && $selectedTemplate) {

            $itemOptions = $this->CompetencyItems->getItemByTemplateAcademicPeriod($selectedTemplate, $selectedPeriod);

            $itemOptions = array(-1 => __('-- Select Item --')) + $itemOptions;

            if ($request->query('item')) {
                $selectedItem = $request->query('item');
            } else {
                $selectedItem = -1;
            }

            $extra['selectedItem'] = $selectedItem;
            $data['itemOptions'] = $itemOptions;
            $data['selectedItem'] = $selectedItem;
        }
        
        $extra['elements']['control'] = [
            'name' => 'Competency.criterias_controls',
            'data' => $data,
            'order' => 3
        ];
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        if (array_key_exists('selectedPeriod', $extra)) {
            if ($extra['selectedPeriod']) {
                $conditions[] = $this->aliasField('academic_period_id = ') . $extra['selectedPeriod'];
            }
        }

        // if (array_key_exists('selectedItem', $extra)) {
        //     if ($extra['selectedItem']) {
        //         $conditions[] = $this->aliasField('competency_item_id = ') . $extra['selectedItem'];
        //     }
        // }

        $query->where([$conditions]);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'CompetencyItems','Templates'
        ]);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        //massage data to match many to many data format.
        if (array_key_exists($this->alias(), $requestData)) {
            if (array_key_exists('competency_items', $requestData[$this->alias()])) {
                foreach ($requestData[$this->alias()]['competency_items']['_ids'] as $key => $item) {
                    $requestData[$this->alias()]['competency_items'][$key]['id'] = $requestData[$this->alias()]['competency_items']['_ids'][$key];
                    $requestData[$this->alias()]['competency_items'][$key]['academic_period_id'] = $requestData[$this->alias()]['academic_period_id'];
                    $requestData[$this->alias()]['competency_items'][$key]['_joinData']['competency_item_id'] = $requestData[$this->alias()]['competency_items']['_ids'][$key];
                    $requestData[$this->alias()]['competency_items'][$key]['_joinData']['academic_period_id'] = $requestData[$this->alias()]['academic_period_id'];
                }
                unset($requestData[$this->alias()]['competency_items']['_ids']);
            }
        }
        // pr($requestData[$this->alias()]);die;
        $newOptions = ['associated' => ['CompetencyItems']]; //so during patch entity, it can get the necessary datas
        $arrayOptions = $patchOptions->getArrayCopy();
        $arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
        $patchOptions->exchangeArray($arrayOptions);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {

            list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

            if ($action == 'add') {
                $attr['default'] = $selectedPeriod;
                $attr['options'] = $periodOptions;
                $attr['onChangeReload'] = 'changeAcademicPeriod';
            } else if ($action == 'edit') {
                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $periodOptions[$attr['entity']->academic_period_id];
                $attr['value'] = $attr['entity']->academic_period_id;
            }
        }
        return $attr;
    }

    public function addEditOnChangeAcademicPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        $request->query['template'] = '-1';
        $request->query['item'] = '-1';
        
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }
            }
        }
    }

    public function onUpdateFieldCompetencyTemplateId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {

            if ($action == 'add') {

                list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

                $templateOptions = $this->Templates->getTemplateByAcademicPeriod($selectedPeriod);
                
                $attr['options'] = $templateOptions;
                $attr['onChangeReload'] = 'changeCompetencyTemplate';
                
            } else {
                $attr['type'] = 'readonly';
                $attr['value'] = $attr['entity']->competency_template_id;
                $attr['attr']['value'] = $attr['entity']->template->code_name;

            }
        }
        return $attr;
    }

    public function addEditOnChangeCompetencyTemplate(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        $request->query['item'] = '-1';

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }

                if (array_key_exists('competency_template_id', $request->data[$this->alias()])) {
                    $request->query['template'] = $request->data[$this->alias()]['competency_template_id'];
                }
            }
        }
    }

    public function onUpdateFieldCompetencyItems(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {

                list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

                $selectedTemplate = $request->query('template');
                    
                $itemOptions = [];
                if ($selectedTemplate) {
                    $itemOptions = $this->CompetencyItems->getItemByTemplateAcademicPeriod($selectedTemplate, $selectedPeriod);
                }
                
                $attr['options'] = $itemOptions;
                
            } else {
                $attr['type'] = 'element';
                $attr['element'] = 'Competency.competency_items';
            }
        }
        return $attr;
    }

    public function setupFields(Entity $entity)
    {
        $this->field('academic_period_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('competency_template_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('competency_items', [
            'type' => 'chosenSelect',
            'entity' => $entity
        ]);

        $this->setFieldOrder([
            'academic_period_id', 'competency_template_id', 'competency_items', 'code', 'name', 'start_date', 'end_date', 'date_enabled', 'date_disabled'
        ]);
    }

    public function getAcademicPeriodOptions($querystringPeriod)
    {
        $periodOptions = $this->AcademicPeriods->getYearList();

        if ($querystringPeriod) {
            $selectedPeriod = $querystringPeriod;
        } else {
            $selectedPeriod = $this->AcademicPeriods->getCurrent();
        }

        return compact('periodOptions', 'selectedPeriod');
    }

    // public function getPeriodByTemplateItemAcademicPeriod($selectedTemplate, $selectedItem, $selectedPeriod)
    // {
    //     return $this
    //             ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
    //             ->where([
    //                 $this->aliasField('academic_period_id') => $selectedPeriod,
    //                 $this->aliasField('competency_template_id') => $selectedTemplate,
    //                 $this->aliasField('competency_item_id') => $selectedItem,
    //             ])
    //             ->toArray();
    // }
}
