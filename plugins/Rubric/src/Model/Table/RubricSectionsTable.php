<?php
namespace Rubric\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;

class RubricSectionsTable extends AppTable
{
    private $selectedTemplate = null;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('RubricTemplates', ['className' => 'Rubric.RubricTemplates']);
        $this->hasMany('RubricCriterias', ['className' => 'Rubric.RubricCriterias', 'dependent' => true, 'cascadeCallbacks' => true]);
        if ($this->behaviors()->has('Reorder')) {
            $this->behaviors()->get('Reorder')->config([
                'filter' => 'rubric_template_id',
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
        $query = $this->request->query;

        $templateOptions = $this->RubricTemplates->find('list')->toArray();
        $selectedTemplate = isset($query['template']) ? $query['template'] : key($templateOptions);

        return compact('templateOptions', 'selectedTemplate');
    }
}
