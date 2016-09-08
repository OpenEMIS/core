<?php
namespace Configuration\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Network\Session;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use ArrayObject;

class ConfigAdministrativeBoundariesTable extends ControllerActionTable {
    public function initialize(array $config)
    {
        $this->table('config_items');
        parent::initialize($config);
        $this->addBehavior('Configuration.ConfigItems');
        $this->toggle('remove', false);
        $this->toggle('add', false);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->add('name', 'ruleUnique', [
                'rule' => 'validateUnique',
                'provider' => 'table'
            ])
            ->add('value', 'invalidUrl', [
                'rule' => ['url', true],
                'last' => true // last mean it will not run the next validation, this will be last to run, same as break
            ])
            ->add('value', 'ruleValidateJsonAPI', [
                'rule' => 'validateJsonAPI',
            ])
            ->allowEmpty('value')
            ;
        return $validator;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('name', ['type' => 'readonly']);
        $this->field('visible', ['visible' => false]);
        $this->field('editable', ['visible' => false]);
        $this->field('field_type', ['visible' => false]);
        $this->field('option_type', ['visible' => false]);
        $this->field('code', ['visible' => false]);
        $this->field('default_value', ['visible' => ['view'=>false]]);
        $this->field('type', ['visible' => ['view'=>false, 'edit'=>false], 'type' => 'readonly']);
        $this->field('label', ['visible' => ['view'=>false, 'edit'=>false], 'type' => 'readonly']);
        $this->field('value', ['sort' => false]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->where([$this->aliasField('type') => 'Administrative Boundaries']);
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'value') {
            return __('URL');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
