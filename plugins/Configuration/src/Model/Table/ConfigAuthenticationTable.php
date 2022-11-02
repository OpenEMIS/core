<?php
namespace Configuration\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Validation\Validator;

class ConfigAuthenticationTable extends ControllerActionTable
{
    public $id;
    public $authenticationType;
    private $options = [];

    public function initialize(array $config)
    {
        $this->table('config_items');
        parent::initialize($config);
        $this->addBehavior('Configuration.Authentication');
        $this->toggle('remove', false);

        $authenticationRecord = $this
            ->find()
            ->where([$this->aliasField('type') => 'Authentication'])
            ->first();
        $id = $authenticationRecord->id;
        $this->id = $id;
        $this->authenticationType = $authenticationRecord->value;

        $optionTable = TableRegistry::get('Configuration.ConfigItemOptions');
        $this->options = $optionTable->find('list', ['keyField' => 'value', 'valueField' => 'option'])
            ->where([
                'ConfigItemOptions.option_type' => 'yes_no',
                'ConfigItemOptions.visible' => 1
            ])
            ->toArray();
    }

    public function validationDefault(Validator $validator)
    {
        return $validator->add('value', 'ruleLocalLogin', [
                    'rule' => 'checkLocalLogin'
                ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $extra['elements']['controls'] = $this->buildSystemConfigFilters();
        $extra['config']['selectedLink'] = ['controller' => 'Configurations', 'action' => 'index'];
        $this->field('visible', ['visible' => false]);
        $this->field('editable', ['visible' => false]);
        $this->field('field_type', ['visible' => false]);
        $this->field('option_type', ['visible' => false]);
        $this->field('code', ['visible' => false]);
        $this->field('name', ['visible' => ['index'=>true]]);
        $this->field('type', ['visible' => ['view'=>true, 'edit'=>true], 'type' => 'readonly']);
        $this->field('label', ['visible' => ['view'=>true, 'edit'=>true], 'type' => 'readonly']);
        $this->field('value', ['visible' => true]);
        $this->field('default_value', ['visible' => ['view'=>true]]);

        if ($this->action == 'index') {
            $url = $this->url('view');
            $url[1] = $this->paramsEncode(['id' => $this->id]);
            $this->controller->redirect($url);
        } elseif ($this->action == 'view') {
            if (isset($extra['toolbarButtons']['back'])) {
                unset($extra['toolbarButtons']['back']);
            }
            $extra['elements']['controls'] = $this->buildSystemConfigFilters();
            $this->checkController();
        }
        $this->checkController();
    }

    public function onUpdateFieldValue(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['edit', 'add'])) {
            $id = $this->id;
            if (!empty($id)) {
                $entity = $this->get($id);
                if ($entity->field_type == 'Dropdown') {
                    $attr['options'] = $this->options;
                    $attr['onChangeReload'] = true;
                }
            }
        }
        return $attr;
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->fields['type']['attr']['value'] = __($entity->type);
        $this->fields['label']['attr']['value'] = __($entity->label);
    }

    public function onGetType(Event $event, Entity $entity)
    {
        return __($entity->type);
    }

    public function onGetLabel(Event $event, Entity $entity)
    {
        return __($entity->label);
    }

    public function onGetValue(Event $event, Entity $entity)
    {
        return __($this->options[$entity->value]);
    }

    public function onGetDefaultValue(Event $event, Entity $entity)
    {
        return __($this->options[$entity->default_value]);
    }
}
