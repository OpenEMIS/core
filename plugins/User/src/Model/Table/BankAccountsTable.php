<?php
namespace User\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Traits\OptionsTrait;

use App\Model\Table\ControllerActionTable;

class BankAccountsTable extends ControllerActionTable
{
    use OptionsTrait;
    public function initialize(array $config)
    {
        $this->table('user_bank_accounts');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('BankBranches', ['className' => 'FieldOption.BankBranches']);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['active']['type'] = 'select';
        $this->fields['active']['options'] = $this->getSelectOptions('general.yesno');
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('bank_name', ['type' => 'select']);
        $this->field('bank_branch_id', ['type' => 'select']);

        $this->setFieldOrder(['bank_name', 'bank_branch_id', 'account_name', 'account_number', 'active']);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('bank_name', ['type' => 'select']);
        $this->field('bank_branch_id', ['type' => 'select']);

        $this->setFieldOrder(['bank_name', 'bank_branch_id', 'account_name', 'account_number', 'active']);
    }

    public function addOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        //to clear the bank option when toolbar button (back or list) clicked
        $this->request->query['bank_option'] = '';
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        $bankId = $this->BankBranches->get($entity->bank_branch_id)->bank_id;
        $this->request->query['bank_option'] = $bankId;
    }


    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('bank_name');
        $this->setFieldOrder(['account_name', 'account_number', 'active', 'bank_name', 'bank_branch_id']);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator->requirePresence('bank_name');
    }

    public function onGetActive(Event $event, Entity $entity)
    {
        $icons = [
            0 => '<i class="fa kd-cross red"></i>',
            1 => '<i class="fa kd-check green"></i>'
        ];
        return $icons[$entity->active];
    }

    private function setupTabElements()
    {
        switch ($this->controller->name) {
            case 'Students':
                $tabElements = $this->controller->getFinanceTabElements();
                $this->controller->set('tabElements', $tabElements);
                $this->controller->set('selectedAction', $this->alias());
                break;
            case 'Staff':
                $tabElements = $this->controller->getFinanceTabElements();
                $this->controller->set('tabElements', $tabElements);
                $this->controller->set('selectedAction', $this->alias());
                break;
            case 'Directories':
            case 'Profiles':
                $type = $this->request->query('type');
                $options = [
                    'type' => $type
                ];
                if ($type == 'student') {
                    $tabElements = $this->controller->getFinanceTabElements($options);
                } else {
                    $tabElements = $this->controller->getStaffFinanceTabElements($options);
                }

                $this->controller->set('tabElements', $tabElements);
                $this->controller->set('selectedAction', $this->alias());
                break;
        }
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    public function onUpdateFieldBankName(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $bankId = $request->query('bank_option');

            $bankOptions = TableRegistry::get('FieldOption.Banks')
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

    public function onUpdateFieldBankBranchId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if (array_key_exists('bank_option', $request->query)) {
                $bankId = $request->query['bank_option'];
                $bankBranches = $this->BankBranches
                    ->find('list')
                    ->find('order')
                    ->where([$this->BankBranches->aliasField('bank_id') => $bankId])
                    ->toArray();
            } else {
                $bankBranches = [];
            }
            $attr['options'] = $bankBranches;
        }
        return $attr;
    }

    public function addEditOnChangeBank(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['bank_option']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('bank_name', $request->data[$this->alias()])) {
                    $request->query['bank_option'] = $request->data[$this->alias()]['bank_name'];
                }
            }
        }
    }
}
