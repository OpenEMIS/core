<?php

namespace Institution\Model\Table;

// POCOR-8873
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use Cake\I18n\Date;
use Cake\Log\Log;
use App\Model\Table\ControllerActionTable;
use Cake\Http\ServerRequest;
use Cake\ORM\ResultSet;

class InstitutionConsumablesTable extends ControllerActionTable
{

    public function initialize(array $config): void
    {
        $this->setTable('institution_consumables');
        parent::initialize($config);

        $this->belongsTo('StockUnits', ['className' => 'FieldOption.StockUnits', 'foreignKey' => 'stock_unit_id']);
        $this->belongsTo('ItemTypes', ['className' => 'FieldOption.ItemTypes', 'foreignKey' => 'item_type_id']);
        $this->hasMany('InstitutionConsumableTransactions', [
            'className' => 'Institution.InstitutionConsumableTransactions',
            'foreignKey' => 'institution_consumable_id',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

       
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['Consumables' => ['id']]
        ]);
    }

    public function beforeAction($event)
    {
        $this->field('stock_unit_id', ['type' => 'select', 'visible' => ['index' => true, 'view' => true, 'edit' => true]]);
        $this->field('item_type_id', ['type' => 'select', 'visible' => ['index' => true, 'view' => true, 'edit' => true]]);
        $this->field('balance', ['visible' => ['index' => true, 'view' => false, 'edit' => false]]);//POCOR-8979
       
        $this->setFieldOrder(['bin_no', 'item_type_id', 'stock_type_id', 'minimum', 'balance']);

    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            ->requirePresence('item_type_id')
            ->requirePresence('stock_unit_id')
            ->allowEmpty('minimum')
            ->allowEmpty('bin_no');
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $binNo = $entity->bin_no;
        $institutionId = $entity->institution_id ?? $this->getInstitutionID();

        //POCOR-9333 start
        $checkBinRecord = $this->find()
            ->where([
                $this->aliasField('bin_no') => $binNo,
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('id !=') => $entity->id ?? 0 // exclude self when editing
            ])
            ->first();
        if ($checkBinRecord) {
            $entity->setError('bin_no', __('This Bin Number already exists in this institution.'));
            return false; // stop saving
        } //POCOR-9333 end

        return $entity; // allow save
    }


    public function beforeDelete(EventInterface $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    private function setupTabElements()
    {
        $queryString = $this->ControllerAction->getQueryString();
        if (empty($queryString)) {
            $queryString = $this->getQueryString();
        }
        $id = $this->ControllerAction->getQueryString('id');
        $queryString['consumable_id'] = $id;
        $encodedQueryString = $this->ControllerAction->paramsEncode($queryString);
        $Url = ['plugin' => 'Institution', 'controller' => 'Institutions'];
       
        $tabElements = [
            'Overview' => [
                'url' => array_merge($Url, ['action' => 'Consumable', 'view',$encodedQueryString, $this->ControllerAction->paramsEncode(['institution_id' => $this->getInstitutionID()])]),
                'text' => __('Overview')
            ],
            'Transactions' => [
                'url' => array_merge($Url, ['action' => 'Transactions', 'index', $encodedQueryString, $this->ControllerAction->paramsEncode(['consumable_id' => $id, 'institution_id' => $this->getInstitutionID()])]),
                'text' => __('Transactions')
            ]
        ];
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Overview');
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        
        if ($field == 'item_type_id') {
            return  __('Item Type');
        } else if ($field == 'stock_unit_id') {
            return  __('Stock Unit');
        } else if ($field == 'bin_no') {
            return  __('Code(Bin no.)');
        } else if ($field == 'minimum') {
            return  __('Minimum');
        } else if ($field == 'balance') {
            return  __('Balance');
        }
        else if ($field == 'modified_user_id') {
            return __('Modified By');
        } else if ($field == 'modified') {
            return __('Modified On');
        } else if ($field == 'created_user_id') {
            return __('Created By');
        } else if ($field == 'created') {
            return __('Created On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    //POCOR-8979 start
    public function onGetBalance(EventInterface $event, Entity $entity)
    {
        $content = "";
        $InstitutionConsumableTransactions = TableRegistry::getTableLocator()->get('Institution.InstitutionConsumableTransactions');
        $transactions = $InstitutionConsumableTransactions->find()
        ->where(['InstitutionConsumableTransactions.institution_consumable_id' => $entity->id])
        ->order(['InstitutionConsumableTransactions.id' => 'DESC'])
        ->first();
        
        if ($transactions->balance < $entity->minimum) {
            $content .= '<span class="input string" style="color:red;">' . $transactions->balance . '</span>';
        }
        else{
            $content .= '<span class="input string">' . $transactions->balance . '</span>';
        }
        return $content;
    }
    //POCOR-8979 end

    public function onUpdateFieldItemTypeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {  
        if ($action == 'add' || $action == 'edit') {
            $attr['onChangeReload'] = 'StockUnitId';
            return $attr;
        }
        return $attr;
    }

    public function onUpdateFieldStockUnitId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $request = $this->request;

        if ($action == 'add' || $action == 'edit') {
            $ItemTypes = TableRegistry::getTableLocator()->get('FieldOption.ItemTypes');
            $itemTypeId = !is_null($request->getData($this->aliasField('item_type_id'))) ? $request->getData($this->aliasField('item_type_id')) : 0;

            if ($itemTypeId != 0) {
                $StockUnitOptions = $ItemTypes->find('list', [
                    'keyField' => 'stock_unit.id',
                    'valueField' => 'stock_unit.name'
                ])
                    ->contain(['StockUnit']) 
                    ->where(['ItemTypes.id' => $itemTypeId])
                    ->toArray();

                $attr['options'] = $StockUnitOptions;
                $attr['select'] = false;
                $attr['default'] = $StockUnitOptions[0];
            } else {
                return $attr;
            }
        }
        return $attr;
    }
}
