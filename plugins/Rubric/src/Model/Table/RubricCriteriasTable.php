<?php
namespace Rubric\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Validation\Validator;

class RubricCriteriasTable extends AppTable
{
    private $selectedTemplate = null;
    private $selectedSection = null;
    private $criteriaType = array(
        1 => array('id' => 1, 'name' => 'Section Break'),
        2 => array('id' => 2, 'name' => 'Criteria')
    );
    private $_contain = ['RubricCriteriaOptions.RubricTemplateOptions'];

    public function initialize(array $config) {
        parent::initialize($config);
        $this->belongsTo('RubricSections', ['className' => 'Rubric.RubricSections']);
        $this->hasMany('RubricCriteriaOptions', ['className' => 'Rubric.RubricCriteriaOptions', 'dependent' => true, 'cascadeCallbacks' => true]);
        if ($this->behaviors()->has('Reorder')) {
            $this->behaviors()->get('Reorder')->config([
                'filter' => 'rubric_section_id',
            ]);
        }
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';
        return $events;
    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'name';
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->add('name', [
                'unique' => [
                    'rule' => ['validateUnique', ['scope' => 'rubric_section_id']],
                    'provider' => 'table',
                    'message' => 'This name is already exists in the system'
                ]
            ]);

        return $validator;
    }

    public function beforeAction(Event $event)
    {
        //Add new fields
        $this->ControllerAction->field('criterias', [
            'type' => 'element',
            'element' => 'Rubric.criterias',
            'visible' => false,
            'valueClass' => 'table-full-width'
        ]);
    }

    public function indexBeforeAction(Event $event)
    {
        //Add controls filter to index page
        $toolbarElements = [
            ['name' => 'Rubric.controls', 'data' => [], 'options' => []]
        ];

        $this->controller->set('toolbarElements', $toolbarElements);
    }

    public function viewBeforeAction(Event $event)
    {
        $this->setFieldOrder();
    }

    public function viewEditBeforeQuery(Event $event, Query $query)
    {
        $query->contain($this->_contain);
    }

    public function viewAfterAction(Event $event, Entity $entity)
    {
        $selectedCriteriaType = $entity->type;

        if ($selectedCriteriaType == 1) {   //1-> Section Break, 2 -> Dropdown
            $this->fields['criterias']['visible'] = false;
        } else if ($selectedCriteriaType == 2) {
            $this->fields['criterias']['visible'] = true;
        }

        return $entity;
    }

    public function addEditBeforeAction(Event $event)
    {
        //Setup fields
        list($sectionOptions, , $criteriaTypeOptions, ) = array_values($this->getSelectOptions());

        $this->fields['rubric_section_id']['type'] = 'select';
        $this->fields['rubric_section_id']['options'] = $sectionOptions;

        $this->fields['type']['type'] = 'select';
        $this->fields['type']['options'] = $criteriaTypeOptions;
        $this->fields['type']['onChangeReload'] = true;

        $this->setFieldOrder();
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        //Required by patchEntity for associated data
        $newOptions = [];
        $newOptions['associated'] = $this->_contain;

        $arrayOptions = $options->getArrayCopy();
        $arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
        $options->exchangeArray($arrayOptions);
    }

    public function addEditAfterAction(Event $event, Entity $entity)
    {
        $selectedCriteriaType = $entity->type;

        if ($selectedCriteriaType == 1) {   //1-> Section Break, 2 -> Dropdown
            $this->fields['criterias']['visible'] = false;
        } else if ($selectedCriteriaType == 2) {
            $this->fields['criterias']['visible'] = true;
        }

        return $entity;
    }

    public function addOnInitialize(Event $event, Entity $entity)
    {
        //Initialize field values
        list(, $selectedSection, , $selectedCriteriaType) = array_values($this->getSelectOptions());

        $entity->rubric_section_id = $selectedSection;
        $entity->type = $selectedCriteriaType;

        return $entity;
    }

    public function addEditOnReload(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $selectedSection = $data[$this->alias()]['rubric_section_id'];
        $selectedCriteriaType = $data[$this->alias()]['type'];
        $selectedTemplate = $this->RubricSections->find('all')->where([$this->RubricSections->aliasField('id') => $selectedSection])->first()->rubric_template_id;

        if ($selectedCriteriaType == 1) {   //1-> Section Break, 2 -> Dropdown
            //do nothing
        } else if ($selectedCriteriaType == 2) {
            if (count($entity->rubric_criteria_options) == 0) {
                $RubricTemplateOptions = $this->RubricCriteriaOptions->RubricTemplateOptions;
                $templateOptions = $RubricTemplateOptions->find('all')
                    ->find('order')
                    ->where([$RubricTemplateOptions->aliasField('rubric_template_id') => $selectedTemplate])
                    ->toArray();

                $criteriaOptions = [];
                foreach ($templateOptions as $key => $obj) {
                    $criteriaOptions[$key] = [
                        'name' => '',
                        'rubric_template_option_id' => $obj->id,
                        'rubric_template_option' => [
                            'name' => $obj->name,
                            'weighting' => $obj->weighting
                        ]
                    ];
                }

                $data[$this->alias()]['rubric_criteria_options'] = $criteriaOptions;
                //Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
                //$options['associated'] = ['RubricCriteriaOptions.RubricTemplateOptions'];
                $options['associated'] = [
                    'RubricCriteriaOptions' => ['validate' => false, 'associated' => 'RubricTemplateOptions']
                ];
            }
        }
    }

    public function onGetType(Event $event, Entity $entity)
    {
        list(, , $criteriaTypeOptions, ) = array_values($this->getSelectOptions());

        return $criteriaTypeOptions[$entity->type];
    }

    public function getSelectOptions()
    {
        //Return all required options and their key
        $query = $this->request->query;

        $templateOptions = $this->RubricSections->RubricTemplates->find('list')->toArray();
        $selectedTemplate = isset($query['template']) ? $query['template'] : key($templateOptions);

        $sectionOptions = $this->RubricSections->find('list')
            ->find('order')
            ->where([$this->RubricSections->aliasField('rubric_template_id') => $selectedTemplate])
            ->toArray();
        $selectedSection = isset($query['section']) ? $query['section'] : key($sectionOptions);

        $criteriaTypeOptions = [];
        foreach ($this->criteriaType as $key => $criteriaType) {
            $criteriaTypeOptions[$criteriaType['id']] = __($criteriaType['name']);
        }
        $selectedCriteriaType = key($criteriaTypeOptions);

        return compact('sectionOptions', 'selectedSection', 'criteriaTypeOptions', 'selectedCriteriaType');
    }

    public function setFieldOrder()
    {
        $this->ControllerAction->setFieldOrder([
            'rubric_section_id', 'name', 'type', 'criterias'
        ]);
    }
}
