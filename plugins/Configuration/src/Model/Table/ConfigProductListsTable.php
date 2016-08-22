<?php
namespace Configuration\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Network\Session;
use Cake\ORM\Entity;
use ArrayObject;

class ConfigProductListsTable extends ControllerActionTable {

	public function initialize(array $config) {
		$this->table('config_product_lists');
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
            ->allowEmpty('url')
            ;
        return $validator;
    }

    public function beforeAction(Event $event)
    {
        $this->field('name', ['type' => 'readonly']);
        $this->field('url', ['type' => 'string']);
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $session = new Session();
        $session->delete('ConfigProductLists.list');
    }
}
