<?php

namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
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

// use App\Model\Traits\MessagesTrait;

class InstitutionConsumableTransactionsTable extends ControllerActionTable
{
    // use MessagesTrait;

    public function initialize(array $config): void
    {
        $this->setTable('institution_consumable_transactions');
        parent::initialize($config);
        // $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        // $this->belongsTo('StockUnits', ['className' => 'FieldOption.StockUnits', 'foreignKey' => 'stock_unit_id']);
        $this->belongsTo('InstitutionConsumables', ['className' => 'Institution.InstitutionConsumables', 'foreignKey' => 'institution_consumable_id']);
        // $this->addBehavior('ControllerAction.FileUpload', [
        //     'name' => 'file_name',
        //     'content' => 'file_content',
        //     'size' => '10MB',
        //     'contentEditable' => true,
        //     'allowable_file_types' => 'all',
        //     'useDefaultName' => true
        // ]);

        // $this->addBehavior('Excel', ['pages' => ['index']]);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['Consumables' => ['id']]
        ]);
    }

    public function beforeAction($event)
    {
        // print_r($id);die;
        $this->field('institution_consumable_id', ['type' => 'hidden']);
        // $this->field('balance', ['type' => 'hidden']);


        // $this->field('academic_period_id', ['type' => 'select', 'visible' => ['view'=>true, 'edit'=>true]]);
        // $this->field('stock_unit_id', ['type' => 'select', 'visible' => ['view' => true, 'edit' => true]]);
        // $this->field('item_type_id', ['type' => 'select', 'visible' => ['view' => true, 'edit' => true]]);
        // $this->field('balance', ['visible' => ['index' => true, 'add' => false, 'view' => true, 'edit' => false]]);
        // $this->field('minimum', ['visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        // unset($this->fields['received']);
        // unset($this->fields['date']);
        // unset($this->fields['issued']);
        // unset($this->fields['init']);
        // $this->field('date', ['visible' => ['index'=>true,'add' => true, 'view' => true, 'edit' => true]]);
        // $this->field('received', ['visible' => ['index'=>true,'add' => true, 'view' => true, 'edit' => true]]);
        // $this->field('issued', ['visible' => ['index'=>true,'add' => true, 'view' => true, 'edit' => true]]);
        // $this->field('balance', ['visible' => ['index' => true, 'view' => true, 'edit' => true]]);
        // $this->field('init', ['visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('created_user_id', ['visible' => ['index' => true, 'add' => false, 'view' => true, 'edit' => false]]);


        // $this->setFieldOrder(['date', 'received', 'issued', 'balance', 'init','created_user_id']);

        $this->setupTabElements();
        $this->setFieldOrder(['date', 'received', 'issued', 'balance', 'created_user_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            // ->requirePresence('item_type_id')
            // ->requirePresence('stock_unit_id')
            ->requirePresence('date')
            ->requirePresence('received')
            ->requirePresence('issued')
            // ->allowEmpty('balance')
            ->add('issued', 'balanceCheck', [
                'rule' => function ($value, $context) {
                    if (isset($context['data']['balance']) && $context['data']['balance'] <= 0) {
                        return false; // Fails validation when balance is 0 or negative
                    }
                    return true; // Passes validation
                },
                'message' => 'Update is unsuccessful as balance is lower than minimum.'
            ]);

        // ->requirePresence('balance');
        // ->requirePresence('init');
        // ->allowEmpty('minimum')
        // ->allowEmpty('bin_no');
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $data)
    {
        //$entity->institution_id = $this->request->getSession()->read('Institution.Institutions.id');
        // $entity->institution_id = $this->getInstitutionID();
        $entity->institution_consumable_id = $this->ControllerAction->getQueryString('consumable_id');
        $transactions = TableRegistry::get('Institution.InstitutionConsumableTransactions');
        $lastBalance = $transactions->find()
            ->select(['balance'])
            ->order(['id' => 'DESC'])
            ->first();
        if ($lastBalance) {
            $entity->balance = $lastBalance->balance + $entity->received - $entity->issued;
        } else {
            $entity->balance = $entity->received - $entity->issued;
        }
        // $validator = $this->validator();
        // $this->validator()->add('balance', 'minBalanceCheck', [
        //     'rule' => function ($value, $context) {
        //         return $value > 0; // If balance is 0 or less, validation fails
        //     },
        //     'message' => 'Update is unsuccessful as balance is lower than minimum.'
        // ]);

        // $validator = new Validator();
        // return $validator->setProvider('custom', $this) //POCOR-8082
        //     ->add('balance', 'minBalanceCheck', [
        //         'rule' => function ($value, $context) {
        //             return $value > 0; // If balance is 0 or less, validation fails
        //         },
        //         'message' => 'Update is unsuccessful as balance is lower than minimum.'
        //     ]);

        // print_r($entity);die;
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(Event $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // $this->setupFields($entity);
        $this->setupTabElements();
        // POCOR-8507 Start
        // POCOR-8507 End
        // $this->Navigation->addCrumb('Administrative Boundaries', ['plugin' => 'Area', 'controller' => 'Areas', 'action' => $model->alias]);
        // $this->Navigation->addCrumb($tabElements[$model->alias]['text']);

        // $this->set('contentHeader', $header);
    }

    private function setupTabElements()
    {
        $queryString = $this->ControllerAction->getQueryString();
        if (empty($queryString)) {
            $queryString = $this->getQueryString();
        }
        $id = $this->ControllerAction->getQueryString('consumable_id');
        // $queryString['consumable_id'] = $id;
        $encodedQueryString = $this->ControllerAction->paramsEncode($queryString);
        $queryString['id'] = $id;
        $overviewQueryString = $this->ControllerAction->paramsEncode($queryString);

        $Url = ['plugin' => 'Institution', 'controller' => 'Institutions'];

        // $options['type'] = 'student';
        $tabElements = [
            'Overview' => [
                'url' => array_merge($Url, ['action' => 'Consumable', 'view', $overviewQueryString, $this->ControllerAction->paramsEncode(['institution_id' => $this->getInstitutionID()])]),
                // 'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Overview'],
                'text' => __('Overview')
            ],
            'Transactions' => [
                // 'url' =>  $this->ControllerAction->setQueryString([
                //     'plugin' => 'Institution',
                //     'controller' => 'Institutions',
                //     'action' => 'Transactions',
                //     'index'],
                //     ['institution_id' => $this->getInstitutionID()]
                // ),
                'url' => array_merge($Url, ['action' => 'Transactions', 'index', $encodedQueryString, $this->ControllerAction->paramsEncode(['id' => $id, 'institution_id' => $this->getInstitutionID()])]),
                'text' => __('Transactions')
            ]
        ];
        // $tabElements = $this->controller->getAcademicTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Transactions');
        // $headers = 'Consumables';    
        // $this->Navigation->addCrumb($headers, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Transaction']);

    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // $this->setupFields($entity);
        $this->field('balance', ['type' => 'hidden']);

    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('balance', ['type' => 'hidden']);
       
    }

    public function indexBeforeAction($event)
    {
        // $this->setupFields($entity);
        // $this->field('bin_no', ['attr' => ['label' => __('Code(Bin no.)')], 'visible' => ['index' => true, 'add' => true, 'view' => true, 'edit' => true]]);


        // $this->setFieldOrder(['bin_no', 'item_type_id', 'stock_type_id', 'minimum', 'balance']);
        // unset($this->fields['academic_period_id']);
        // unset($this->fields['description']);
        // $this->setFieldOrder(['date', 'income_source_id', 'income_type_id', 'amount']);
    }

    // public function viewBeforeAction($event) {
    //     unset($this->fields['attachment']);
    //     unset($this->fields['description']);
    //     $this->setFieldOrder(['date', 'income_source_id', 'income_type_id', 'amount']);
    // }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $id = $this->ControllerAction->getQueryString('consumable_id');
        // echo $id;die;
        // $parentId = !is_null($this->request->getQuery('id')) ? $this->request->getQuery('id') : null;
        if ($id != null) {
            $query->where([$this->aliasField('institution_consumable_id') => $id]);
        }
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        /*if ($field == 'income_source_id') {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }*/
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
        // } else if ($field == 'issued') {
        //     return  __('Issued');
        // } else if ($field == 'received') {
        //     return  __('Received');
        // } else if ($field == 'date') {
        //     return  __('Date');
        // } else if ($field == 'init') {
        //     return  __('Init');
        // }
        else if ($field == 'created_user_id') {
            return  __('Created By');
        }
        //  else if ($field == 'amount' && $this->action == 'index') {
        //     if (!empty($module) && $module == 'InstitutionIncomes') {
        //         return __('Amount');
        //     } else {
        //         return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        //     }
        //     //return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        // } 
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

    // public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    // {
    //     $session = $this->request->getSession();
    //     //$institutionId = $session->read('Institution.Institutions.id');
    //     $institutionId = $this->getInstitutionID();
    //     $academyPeriodId = !empty($requestQuery['academic_period_id']) ? $requestQuery['academic_period_id'] : $this->AcademicPeriods->getCurrent();


    // 	$query
    // 	->select(['date' => 'InstitutionIncomes.date','amount' => 'InstitutionIncomes.amount', 'source' =>'IncomeSources.name', 'type' => 'IncomeTypes.name'])

    //     ->LeftJoin([$this->IncomeTypes->getAlias() => $this->IncomeTypes->getTable()],[
    //         $this->IncomeTypes->aliasField('id').' = ' . 'InstitutionIncomes.income_type_id'
    //     ])

    // 	->LeftJoin([$this->IncomeSources->getAlias() => $this->IncomeSources->getTable()],[
    // 		$this->IncomeSources->aliasField('id').' = ' . 'InstitutionIncomes.income_source_id'
    //     ]);
    // }

    // public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    // {

    //     $extraField[] = [
    //         'key' => 'InstitutionIncomes.date',
    //         'field' => 'date',
    //         'type' => 'date',
    //         'label' => __('Date')
    //     ];

    //     $extraField[] = [
    //         'key' => 'IncomeSources.name',
    //         'field' => 'source',
    //         'type' => 'string',
    //         'label' => __('Source')
    //     ];

    //     $extraField[] = [
    //         'key' => 'IncomeTypes.name',
    //         'field' => 'type',
    //         'type' => 'string',
    //         'label' => __('Type')
    //     ];

    //     $extraField[] = [
    //         'key' => 'InstitutionIncomes.amount',
    //         'field' => 'amount',
    //         'type' => 'integer',
    //         'label' => __('Amount')
    //     ];

    //     $fields->exchangeArray($extraField);
    // }

    public function onGetBalance(Event $event, Entity $entity)
    {
        $content = "";
        if ($entity->balance < $entity->institution_consumable->minimum) {
            $content .= '<span class="input string" style="color:red;">' . $entity->balance . '</span>';
        }
        return $content;
    }


}
