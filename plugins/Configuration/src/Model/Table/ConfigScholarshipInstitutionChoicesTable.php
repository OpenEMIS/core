<?php
namespace Configuration\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;

use App\Model\Table\ControllerActionTable;

class ConfigScholarshipInstitutionChoicesTable extends ControllerActionTable
{
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('config_items');
        parent::initialize($config);
 
        $this->addBehavior('Configuration.ConfigItems');

        $this->toggle('add', false);
        $this->toggle('remove', false);

        $this->valueOptions = $this->getSelectOptions('ScholarshipInstitutionChoices.institution_field_type_selection');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('name', ['visible' => ['index' => true]]);
        $this->field('code', ['type' => 'hidden']);
        $this->field('type', ['visible' => ['view' => true, 'edit' => true], 'type' => 'readonly']);
        $this->field('label', ['visible' => ['view' => true, 'edit' => true], 'type' => 'readonly']);
        $this->field('default_value', ['visible' => ['view' => true]]);
        $this->field('editable', ['visible' => false]);
        $this->field('visible', ['visible' => false]);
        $this->field('field_type', ['visible' => false]);
        $this->field('option_type', ['visible' => false]);
    }
  

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->where([$this->aliasField('type') => 'Scholarship Institution Choices']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function onGetValue(Event $event, Entity $entity)
    {
        $value = array_key_exists($entity->value, $this->valueOptions) ? $this->valueOptions[$entity->value] : '';
        return $value;
    }

    public function onGetDefaultValue(Event $event, Entity $entity)
    {
        return __($this->valueOptions[$entity->default_value]);
    }


    public function onUpdateFieldValue(Event $event, array $attr, $action, Request $request)
    {
        $entity = $attr['entity'];

        if (in_array($action, ['edit', 'add'])) {
            if ($entity->has('code')) {
                switch ($entity->code) {
                    case 'scholarship_institution_choice_type':
                    $attr['options'] = $this->valueOptions;
                    $attr['type'] = 'select';
                    break;

                    default:
                        break;
                }
            }
        }
        return $attr;
    }

    private function setupFields(Entity $entity)
    {
        $this->field('value', [
            'entity' => $entity
        ]);
        $this->setFieldOrder([
            'type', 'label', 'value', 'default_value'
        ]);
    }

}
