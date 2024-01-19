<?php
namespace Rubric\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class RubricSectionsTable extends ControllerActionTable
{
    private $selectedTemplate = null;

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsTo('RubricTemplates', ['className' => 'Rubric.RubricTemplates']);
        $this->hasMany('RubricCriterias', ['className' => 'Rubric.RubricCriterias', 'dependent' => true, 'cascadeCallbacks' => true]);
        if ($this->behaviors()->has('Reorder')) {
            $reorderBehavior = $this->behaviors()->get('Reorder');
            $reorderBehavior->setConfig('filter', 'rubric_template_id');

        }
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';
        return $events;
    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'name';
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->add('name', [
                'unique' => [
                    'rule' => ['validateUnique', ['scope' => 'rubric_template_id']],
                    'provider' => 'table',
                    'message' => 'This name is already exists in the system'
                ]
            ]);

        return $validator;
    }

    public function indexBeforeAction(Event $event)
    {
        //Add controls filter to index page
        $toolbarElements = [
            ['name' => 'Rubric.controls', 'data' => [], 'options' => []]
        ];

        $this->controller->set('toolbarElements', $toolbarElements);
    }

    public function addEditBeforeAction(Event $event)
    {
        //Setup fields
        list($templateOptions) = array_values($this->getSelectOptions());

        $this->fields['rubric_template_id']['type'] = 'select';
        $this->fields['rubric_template_id']['options'] = $templateOptions;

        $this->ControllerAction->setFieldOrder('rubric_template_id', 1);
    }

    public function addOnInitialize(Event $event, Entity $entity)
    {
        //Initialize field values
        list(, $selectedTemplate) = array_values($this->getSelectOptions());

        $entity->rubric_template_id = $selectedTemplate;

        return $entity;
    }

    public function getSelectOptions()
    {
        //Return all required options and their key
        $query = $this->request->getQuery();

        $templateOptions = $this->RubricTemplates->find('list')->toArray();
        $selectedTemplate = isset($query['template']) ? $query['template'] : key($templateOptions);

        return compact('templateOptions', 'selectedTemplate');
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'rubric_template_id') {
            return __('Template');
        }elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function beforeDelete(Event $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }
}
