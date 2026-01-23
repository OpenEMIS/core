<?php

namespace Institution\Model\Table;

//POCOR-8873
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use Cake\I18n\Date;

use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;
use Cake\Http\ServerRequest;


class InstitutionConsumableTransactionsTable extends ControllerActionTable
{

    public function initialize(array $config): void
    {
        $this->setTable('institution_consumable_transactions');
        parent::initialize($config);

        $this->belongsTo('InstitutionConsumables', ['className' => 'Institution.InstitutionConsumables', 'foreignKey' => 'institution_consumable_id']);
       
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['Consumables' => ['id']]
        ]);
    }

    public function beforeAction($event)
    {
        $this->field('institution_consumable_id', ['type' => 'hidden']);
        $this->field('created_user_id', ['visible' => ['index' => true, 'add' => false, 'view' => true, 'edit' => false]]);

        $this->setupTabElements();
        $this->setFieldOrder(['date', 'received', 'issued', 'balance', 'created_user_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            ->requirePresence('date')
            ->requirePresence('received')
            ->requirePresence('issued')
            ->add('issued', 'balanceCheck', [
                'rule' => function ($value, $context) {
                    if (isset($context['data']['balance']) && $context['data']['balance'] <= 0) {
                        return false; // Fails validation when balance is 0 or negative
                    }
                    return true; // Passes validation
                },
                'message' => 'Update is unsuccessful as balance is lower than minimum.'
            ]);

       
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $data)
    {
        $entity->institution_consumable_id = $this->ControllerAction->getQueryString('consumable_id');

        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
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
        $id = $this->ControllerAction->getQueryString('consumable_id');
       
        $encodedQueryString = $this->ControllerAction->paramsEncode($queryString);
        $queryString['id'] = $id;
        $overviewQueryString = $this->ControllerAction->paramsEncode($queryString);

        $Url = ['plugin' => 'Institution', 'controller' => 'Institutions'];

        $tabElements = [
            'Overview' => [
                'url' => array_merge($Url, ['action' => 'Consumable', 'view', $overviewQueryString, $this->ControllerAction->paramsEncode(['institution_id' => $this->getInstitutionID()])]),
                'text' => __('Overview')
            ],
            'Transactions' => [
                'url' => array_merge($Url, ['action' => 'Transactions', 'index', $encodedQueryString, $this->ControllerAction->paramsEncode(['id' => $id, 'institution_id' => $this->getInstitutionID()])]),
                'text' => __('Transactions')
            ]
        ];
       
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Transactions');
        
    }

    public function addAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('balance', ['type' => 'hidden']);
    }

    public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('balance', ['type' => 'hidden']);
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $id = $this->ControllerAction->getQueryString('consumable_id');
       
        if ($id != null) {
            $query->where([$this->aliasField('institution_consumable_id') => $id]);
        }
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'item_type_id') {
            return  __('Type(Description)');
        } else if ($field == 'stock_unit_id') {
            return  __('Stock Unit');
        } else if ($field == 'bin_no') {
            return  __('Code(Bin no.)');
        } else if ($field == 'minimum') {
            return  __('Minimum');
        } else if ($field == 'balance') {
            return  __('Balance');
        }
        else if ($field == 'created_user_id') {
            return  __('Created By');
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

    private function setupFields($entity = null)
    {
        $this->field('item_type_id', ['type' => 'select', 'visible' => ['index' => true, 'view' => true, 'edit' => true]]);
        $this->field('stock_type_id', ['type' => 'select', 'visible' => ['index' => true, 'view' => true, 'edit' => true]]);
        $this->field('minimum', ['type' => 'select', 'visible' => ['index' => true, 'view' => true, 'edit' => true]]);
        $this->field('date', ['type' => 'hidden', 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('received', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('issued', ['attr' => ['label' => __('Amount')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('bin_no', ['attr' => ['label' => __('Code(Bin no.)')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('balance', ['attr' => ['label' => __('Amount')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->setFieldOrder(['bin_no', 'item_type_id', 'stock_type_id', 'minimum', 'balance']);
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        $institution_consumable_id = $this->ControllerAction->getQueryString('consumable_id');
        $transactions = TableRegistry::getTableLocator()->get('Institution.InstitutionConsumableTransactions');
        $lastBalance = $transactions->find()
            ->select(['balance'])
            ->where([$this->aliasField('institution_consumable_id') => $institution_consumable_id])
            ->order(['id' => 'DESC'])
            ->first();
        if ($lastBalance) {
            $data['balance'] = $lastBalance->balance + $data['received'] - $data['issued'];
        } else {
            $data['balance'] = $data['received'] - $data['issued'];
        }
    }


    public function onGetBalance(EventInterface $event, Entity $entity)
    {
        $content = "";
        if ($entity->balance < $entity->institution_consumable->minimum) {
            $content .= '<span class="input string" style="color:red;">' . $entity->balance . '</span>';
        }
        return $content;
    }
}
