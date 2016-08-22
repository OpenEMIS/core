<?php
namespace Configuration\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use OpenEmis\Model\Traits\ProductListsTrait;

class ConfigProductListsTable extends ControllerActionTable {
    use ProductListsTrait;

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

        if ($this->action == 'index') {
            $productListData = $this
                ->find('list')
                ->toArray();
            $productListData[] = $this->controller->_productName;
            $productLists = $this->controller->getProductLists($productListData);

            foreach ($productLists as $product => $value) {
                $data = [
                    'name' => $product,
                    'url' => ''
                ];
                $entity = $this->newEntity($data);
                $this->save($entity);
            }
        }
    }
}
