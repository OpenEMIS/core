<?php
namespace Configuration\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;


class ConfigCustomValidationTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('config_items');
        parent::initialize($config);
        $this->addBehavior('Configuration.ConfigItems');
        $this->toggle('add', false);
        $this->toggle('remove', false);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('visible', ['visible' => false]);
        $this->field('editable', ['visible' => false]);
        $this->field('field_type', ['visible' => false]);
        $this->field('option_type', ['visible' => false]);
        $this->field('code', ['visible' => false]);
        $this->field('name', ['visible' => ['index'=>true]]);
        $this->field('default_value', ['visible' => ['view'=>true]]);
        $this->field('type', ['visible' => ['view'=>true, 'edit'=>true], 'type' => 'readonly']);
        $this->field('label', ['visible' => ['view'=>true, 'edit'=>true], 'type' => 'readonly']);
        $this->field('value', ['visible' => true]);
        if ($this->action == 'index') {
            $extra['elements']['controls'] = $this->buildSystemConfigFilters();
            $this->checkController();
        }
    }

    // to trim white space "    aaaaa   " to "aaaaa"
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $entity->value = trim($entity->value);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->where([$this->aliasField('type') => 'Custom Validation']);
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'value') {
            return __('Validation Pattern');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
