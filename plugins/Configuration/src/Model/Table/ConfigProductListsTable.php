<?php
namespace Configuration\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Configuration\Model\Traits\ProductListsTrait;

class ConfigProductListsTable extends ControllerActionTable {
    use ProductListsTrait;

	public function initialize(array $config) {
		$this->table('config_product_lists');
		parent::initialize($config);
        $this->addBehavior('Configuration.ConfigItems');
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
            ;
        return $validator;
    }

    public function beforeAction($event)
    {
        $this->field('name');
        $this->field('url', ['type' => 'string']);
    }

    public function onUpdateFieldName(Event $event, array $attr, $action, Request $request) {
        $currentProduct = $this->controller->_productName;
        $productKeys = array_keys($this->productLists);
        $productOptions = [];
        foreach ($productKeys as $value) {
            if ($value != $currentProduct) {
                $productOptions[$value] = __($value);
            }
        }

        $attr['type'] = 'select';
        $attr['options'] = $productOptions;

        return $attr;
    }

}
