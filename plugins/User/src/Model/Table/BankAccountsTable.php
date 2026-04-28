<?php
namespace User\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use App\Model\Traits\OptionsTrait;
use Cake\Http\ServerRequest;

use App\Model\Table\ControllerActionTable;

class BankAccountsTable extends ControllerActionTable
{
    use OptionsTrait;
    public function initialize(array $config): void
    {
        $this->setTable('user_bank_accounts');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('BankBranches', ['className' => 'FieldOption.BankBranches']);
        $this->addBehavior('User.UserTab', [
            'appliedAction' => ['BankAccounts' =>
                ['id'],
                
            ]
        ]);
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->fields['active']['type'] = 'select';
        $this->field('security_user_id', ['type' => 'hidden']);
        $this->fields['active']['options'] = $this->getSelectOptions('general.yesno');

        // Start POCOR-5188
        if($this->request->getParam('controller') == 'Staff'){
            $is_manual_exist = $this->getManualUrl('Institutions','Bank Accounts','Staff - Finance');       
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
        
                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }
        }elseif($this->request->getParam('controller') == 'Students'){
            $is_manual_exist = $this->getManualUrl('Institutions','Bank Accounts','Students - Finance');       
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
        
                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }

        }elseif($this->request->getParam('controller') == 'Directories' && $this->request->getParam('action') == 'StaffBankAccounts'){ 
            $is_manual_exist = $this->getManualUrl('Directory','Bank Accounts','Staff - Finance');       
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];

                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }

        }elseif($this->request->getParam('controller') == 'Directories' && $this->request->getParam('action') == 'StudentBankAccounts'){ 
            $is_manual_exist = $this->getManualUrl('Directory','Bank Accounts','Students - Finance');       
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];

                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }

        }

        // End POCOR-5188
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('bank_name', ['type' => 'select']);
        $this->field('bank_branch_id', ['type' => 'select']);

        $this->setFieldOrder(['bank_name', 'bank_branch_id', 'account_name', 'account_number', 'active']);
    }

    //POCOR-9300[START]
    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $paramsQuery = base64_decode($this->request->getParam('pass')[1]);
        $jsonEndPosition = strpos($paramsQuery, '}') + 1;
        $jsonData = substr($paramsQuery, 0, $jsonEndPosition);
        $paramsQuery = json_decode($jsonData, true);
        $userId = $paramsQuery['staff_id'] ?? $paramsQuery['student_id'] ?? null; //POCOR-9584: support both Staff and Student contexts
        $this->request = $this->request->withData('BankAccounts.security_user_id', $userId);
        $entity->security_user_id = $userId;
    }
    //POCOR-9300[END]

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('bank_name', ['type' => 'select', 'attr' => ['required' => true]]);
        $this->field('bank_branch_id', ['type' => 'select', 'attr' => ['required' => true]]);
        $this->field('account_name', ['attr' => ['required' => true]]);
        $this->field('account_number', ['attr' => ['required' => true]]);

        $this->setFieldOrder(['bank_name', 'bank_branch_id', 'account_name', 'account_number', 'active']);
    }

    public function addOnInitialize(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        //POCOR-9584: $request is immutable in CakePHP5; bank_option will simply be absent on fresh add load
        // original CakePHP3 code: $this->request->getQuery['bank_option'] = '';
    }

    public function editOnInitialize(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        if (empty($entity->bank_branch_id)) {
            return;
        }

        $branch = $this->BankBranches->find()
            ->select(['bank_id'])
            ->where([$this->BankBranches->aliasField('id') => $entity->bank_branch_id])
            ->first();
        if (!$branch || empty($branch->bank_id)) {
            return;
        }

        // CakePHP5 request is immutable; set bank_option explicitly for dependent dropdowns.
        $query = $this->request->getQueryParams();
        $query['bank_option'] = $branch->bank_id;
        $this->request = $this->request->withQueryParams($query);
    }


    public function viewBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('bank_name');
        $this->setFieldOrder(['account_name', 'account_number', 'active', 'bank_name', 'bank_branch_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->requirePresence('bank_name', 'create')
            ->notEmptyString('bank_name')
            ->requirePresence('bank_branch_id', 'create')
            ->notEmptyString('bank_branch_id')
            ->requirePresence('account_name', 'create')
            ->notEmptyString('account_name')
            ->requirePresence('account_number', 'create')
            ->notEmptyString('account_number');
    }

    public function onGetActive(EventInterface $event, Entity $entity)
    {
        $icons = [
            0 => '<i class="fa kd-cross red"></i>',
            1 => '<i class="fa kd-check green"></i>'
        ];
        return $icons[$entity->active];
    }

    private function setupTabElements()
    {
        
        switch ($this->controller->getName()) {
            case 'Students':
                $tabElements = $this->controller->getFinanceTabElements();
                $this->controller->set('tabElements', $tabElements);
                $this->controller->set('selectedAction', $this->getAlias());
                break;
            case 'Staff':
                $tabElements = $this->controller->getFinanceTabElements();
                $this->controller->set('tabElements', $tabElements);
                $this->controller->set('selectedAction', $this->getAlias());
                break;
            case 'Directories':
                $type = $this->request->getQuery('type');
                if ($type == 'student') {
                    $options = [
                        'type' => $type
                    ];
                    $tabElements = $this->controller->getFinanceTabElements($options);
                } else {
                    $tabElements = $this->controller->getStaffFinanceTabElements();
                }
                $this->controller->set('tabElements', $tabElements);
                $this->controller->set('selectedAction', $this->getAlias());
                break;
            case 'Profiles':
                $type = $this->request->getQuery('type');
                $options = [
                    'type' => $type
                ];
                if ($type == 'student') {
                    $tabElements = $this->controller->getFinanceTabElements($options);
                } else {
                    $tabElements = $this->controller->getStaffFinanceTabElements($options);
                }

                $this->controller->set('tabElements', $tabElements);
                $this->controller->set('selectedAction', $this->getAlias());
                break;
        }
    }

    public function afterAction(EventInterface $event, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    public function onUpdateFieldBankName(EventInterface $event, array $attr, $action, ServerRequest $request){
        if ($action == 'add' || $action == 'edit') {
            $bankId = $request->getQuery('bank_option'); //POCOR-9584: was $request->getQuery['bank_option'] (CakePHP3 syntax)

            $bankOptions = TableRegistry::getTableLocator()->get('FieldOption.Banks')
            ->find('list')
            ->find('order')
            ->toArray();

            $attr['options'] = $bankOptions;
            $attr['onChangeReload'] = 'changeBank';
            $attr['attr']['required'] = true;

            if (!is_null($bankId)) {
                $attr['attr']['value'] = $bankId;
            }
        }
        return $attr;
    }

    public function onUpdateFieldBankBranchId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            //POCOR-9584: was array_key_exists('bank_option', $request->getQuery) + $request->getQuery['key'] (CakePHP3 syntax)
            $bankId = $request->getQuery('bank_option');
            if ($bankId === null) {
                $posted = $request->getData($this->getAlias()) ?? [];
                $bankId = $posted['bank_name'] ?? null;
            }
            if ($bankId !== null) {
                $bankBranches = $this->BankBranches
                    ->find('list')
                    ->find('order')
                    ->where([$this->BankBranches->aliasField('bank_id') => $bankId])
                    ->toArray();
            } else {
                $bankBranches = [];
            }
            $attr['options'] = $bankBranches;
            if (!empty($request->getData($this->getAlias())['bank_branch_id'])) {
                $attr['attr']['value'] = $request->getData($this->getAlias())['bank_branch_id'];
            }
            $attr['attr']['required'] = true;
        }
        return $attr;
    }

    public function addEditOnChangeBank(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        //POCOR-9584: CakePHP5 request is immutable; bank_option query param is set via the reloadOnChange URL, not via property mutation
        // original CakePHP3 code used: unset($request->getQuery['bank_option']) and $request->getQuery['bank_option'] = ...
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                if (array_key_exists('bank_name', $request->getData()[$this->getAlias()])) {
                    $bankOption = $request->getData()[$this->getAlias()]['bank_name'];
                    $this->request = $request->withQueryParams(array_merge(
                        $request->getQueryParams(),
                        ['bank_option' => $bankOption]
                    )); //POCOR-9584: replace mutable CakePHP3 pattern with immutable withQueryParams
                }
            }
        }
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'bank_name') {
            return __('Bank Name');
        } elseif ($field == 'bank_branch_id') {
            return __('	Bank Branch');
        } elseif ($field == 'account_name') {
            return __('Account Name');
        } elseif ($field == 'account_number') {
            return __('Account Number');
        } elseif ($field == 'active') {
            return __('Active');
        } elseif ($field == 'validation_rule') {
            return __('Validation Rule');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

}
