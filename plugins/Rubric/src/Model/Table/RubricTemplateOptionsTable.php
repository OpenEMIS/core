<?php
namespace Rubric\Model\Table;

use ArrayObject;

use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;

class RubricTemplateOptionsTable extends AppTable
{
    private $selectedTemplate = null;

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsTo('RubricTemplates', ['className' => 'Rubric.RubricTemplates']);
        if ($this->behaviors()->has('Reorder')) {
            $this->behaviors()->get('Reorder')->setConfig([
                'filter' => 'rubric_template_id',
            ]);
        }
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';
        return $events;
    }

    public function getSearchableFields(EventInterface $event, ArrayObject $searchableFields)
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

    public function beforeAction(EventInterface $event)
    {
        //Setup special fields - Remarks: select fields are automatically handled using foreignkey therefore setup in addEditBeforeAction() only
        $this->fields['color']['type'] = 'color';
    }

    public function indexBeforeAction(EventInterface $event)
    {
        //Add controls filter to index page
        $toolbarElements = [
            ['name' => 'Rubric.controls', 'data' => [], 'options' => []]
        ];

        $this->controller->set('toolbarElements', $toolbarElements);
    }

    public function addEditBeforeAction(EventInterface $event)
    {
        //Setup fields
        list($templateOptions) = array_values($this->getSelectOptions());

        $this->fields['rubric_template_id']['type'] = 'select';
        $this->fields['rubric_template_id']['options'] = $templateOptions;

        $this->ControllerAction->setFieldOrder('rubric_template_id', 1);
    }

    public function addOnInitialize(EventInterface $event, Entity $entity)
    {
        //Initialize field values
        list(, $selectedTemplate) = array_values($this->getSelectOptions());
        $entity->rubric_template_id = $selectedTemplate;
        $entity->color = '#ff00ff';

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

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'rubric_template_id') {
            return __('Template');
        } elseif ($field == 'color') {
            return __('Color');
        } elseif ($field == 'weighting') {
            return __('Weighting');
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
}
