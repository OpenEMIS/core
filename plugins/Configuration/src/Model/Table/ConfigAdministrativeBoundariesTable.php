<?php
namespace Configuration\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Network\Session;
use Cake\ORM\Entity;
use ArrayObject;

class ConfigAdministrativeBoundariesTable extends ControllerActionTable {
    public function initialize(array $config)
    {
        $this->table('config_administrative_boundaries');
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
            ->add('url', 'invalidUrl', [
                'rule' => ['url', true]
            ])
            ->add('url', 'ruleValidateJsonAPI', [
                'rule' => 'validateJsonAPI'
            ])
            ->allowEmpty('url')
            ;
        return $validator;
    }

    public function beforeAction(Event $event)
    {
        $this->field('name', ['type' => 'readonly']);
        $this->field('url', ['type' => 'string']);
    }
}
